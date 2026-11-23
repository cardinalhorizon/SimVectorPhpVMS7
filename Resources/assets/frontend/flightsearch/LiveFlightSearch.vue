<script setup>
import { computed, inject, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useScheduleCloud } from './lib/useScheduleCloud';
import { utcMs } from './lib/normalizeFlight';
import { useTripStore } from './stores/trip';
import OperatorPicker from './components/OperatorPicker.vue';
import AirportPairPicker from './components/AirportPairPicker.vue';
import BrowseCards from './components/BrowseCards.vue';
import TripBar from './components/TripBar.vue';
import FlightResultCard from './components/FlightResultCard.vue';

const props = defineProps({
  initialSaved: { type: Object, default: () => ({}) },
  user: { type: Object, default: () => ({}) },
  onlyFlightsFromCurrent: { type: Boolean, default: false },
  bidsUrl: { type: String, default: null },
});

const api = inject('api');
const trip = useTripStore();
const sc = useScheduleCloud();
const {
  airportsByIcao, routes, routesLoading, routesError, loadRoutes, buildPairIndex, distanceBetween,
  flights, loading, loadingMore, deepPending, error, hasSearched, hasMore, search, loadMore, reset,
} = sc;

const currentAirport = props.user?.current_airport?.icao ?? null;
const homeAirport = props.user?.home_airport?.icao ?? null;

const departure = ref(null);
const arrival = ref(null);
const operators = ref([]);
const operatorIcao = ref(null);
const skipOperator = ref(false);
const afterArrival = ref(true);
const saved = ref({ ...props.initialSaved });
const busy = ref(new Set());
const sortParams = ref(null);

const selectedOperator = computed(() => operators.value.find((o) => o.icao === operatorIcao.value) || null);
const hasRoutes = computed(() => routes.value.length > 0);
const pairIndex = computed(() => buildPairIndex(operatorIcao.value));
const pairDistance = computed(() => distanceBetween(departure.value, arrival.value));
const showBrowse = computed(() => hasRoutes.value && !departure.value && !arrival.value);
const browseStage = computed(() => (operatorIcao.value || skipOperator.value ? 'airport' : 'operator'));

const SORTS = [
  ['callsign', 'Callsign'], ['departure', 'DEP'], ['arrival', 'ARR'],
  ['departureTime', 'Schedule'], ['duration', 'Duration'], ['aircraft', 'Aircraft'],
];

const sortedFlights = computed(() => {
  let data = [...flights.value];
  const landedAt = trip.lastArrivalMs;
  if (afterArrival.value && landedAt !== null && departure.value === trip.nextDeparture) {
    data = data.filter((f) => {
      const d = utcMs(f.departureDate, f.departureTime);
      return d === null || d >= landedAt;
    });
  }
  const s = sortParams.value;
  if (!s) return data;
  const val = (f) => ({
    callsign: f.callsign, departure: f.departure, arrival: f.arrival,
    departureTime: f.departureTime || '',
    duration: String(f.durationMinutes ?? Number.MAX_SAFE_INTEGER),
    aircraft: f.aircraft?.[0] || '',
  })[s.by];
  data.sort((a, b) => val(a).localeCompare(val(b), undefined, { numeric: true, sensitivity: 'base' }) * s.direction);
  return data;
});

const resultCountLabel = computed(() => {
  const n = sortedFlights.value.length;
  if (n === 0) return 'No Flights Found';
  const more = hasMore.value ? '+' : '';
  return n === 1 && !more ? '1 Flight' : `${n}${more} Flights`;
});

function sortBy(by) {
  const c = sortParams.value;
  if (!c || c.by !== by) sortParams.value = { by, direction: 1 };
  else if (c.direction === 1) sortParams.value = { by, direction: -1 };
  else sortParams.value = null;
}
const sortSymbol = (by) => (sortParams.value?.by !== by ? '' : sortParams.value.direction === 1 ? ' ↑' : ' ↓');

function canBid(f) {
  return !(props.onlyFlightsFromCurrent && currentAirport && f.departure !== currentAirport);
}

function clearOperator() { operatorIcao.value = null; skipOperator.value = false; }
function onBrowseOperator(op) { if (op) operatorIcao.value = op.icao; else skipOperator.value = true; }

function resetFilters() {
  departure.value = null; arrival.value = null; clearOperator(); sortParams.value = null; reset();
}
function continueFrom(icao) {
  if (!icao) return;
  departure.value = icao; arrival.value = null; sortParams.value = null;
}

async function fetchOperators() {
  try {
    const res = await api.get('/simvector/operators');
    operators.value = Array.isArray(res.data?.data) ? res.data.data : [];
  } catch { /* non-critical */ }
}

/* Search runs automatically whenever the pair or operator changes */
let timer = null;
watch([departure, arrival, operatorIcao], () => {
  if (timer) clearTimeout(timer);
  timer = setTimeout(() => {
    if (!departure.value && !arrival.value) { reset(); return; }
    void search({ departure: departure.value, arrival: arrival.value }, operatorIcao.value);
  }, 150);
});

/* Bids */
async function addBid(f) {
  busy.value = new Set(busy.value).add(f.id);
  try {
    await api.post('/simvector/bids', { flight_id: f.id });
    saved.value = { ...saved.value, ['SV_' + f.id]: true };
    trip.addLeg(f);
  } catch (e) {
    alert(e?.response?.data?.message || e?.response?.data?.error || 'Error adding bid. Please try again.');
  } finally {
    const n = new Set(busy.value); n.delete(f.id); busy.value = n;
  }
}
async function removeBid(f) {
  busy.value = new Set(busy.value).add(f.id);
  try {
    await api.post('/simvector/bids', { _method: 'DELETE', flight_id: 'SV_' + f.id });
    const { ['SV_' + f.id]: _gone, ...rest } = saved.value;
    saved.value = rest;
    trip.removeLeg(f.id);
  } catch {
    alert('Error removing bid. Please try again.');
  } finally {
    const n = new Set(busy.value); n.delete(f.id); busy.value = n;
  }
}

/* Infinite scroll */
const sentinelRef = ref(null);
let observer = null;
function attachObserver() {
  observer?.disconnect();
  observer = null;
  if (!sentinelRef.value || typeof IntersectionObserver === 'undefined') return;
  observer = new IntersectionObserver((entries) => {
    if (entries.some((e) => e.isIntersecting)) void loadMore();
  }, { rootMargin: '200px' });
  observer.observe(sentinelRef.value);
}
watch([sentinelRef, flights, hasMore], async () => { await nextTick(); attachObserver(); });

onMounted(async () => {
  void fetchOperators();
  await loadRoutes();
  const from = new URLSearchParams(location.search).get('from');
  if (from && !departure.value && !arrival.value) departure.value = from.trim().toUpperCase();
});
onBeforeUnmount(() => { observer?.disconnect(); if (timer) clearTimeout(timer); reset(); });
</script>

<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="mb-0">Live Flights</h2>
      <a v-if="bidsUrl" :href="bidsUrl" class="btn btn-outline-primary">My Bids</a>
    </div>

    <div class="card mb-3 shadow-sm">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <span><i class="bi bi-search" aria-hidden="true"></i> Find Flights</span>
        <button type="button" class="btn btn-sm btn-outline-light" @click="resetFilters">Reset</button>
      </div>
      <div class="card-body">
        <div class="row g-3 align-items-start">
          <div class="col-12 col-md-3">
            <OperatorPicker v-model="operatorIcao" :operators="operators" />
          </div>
          <div class="col-12 col-md">
            <AirportPairPicker v-model:departure="departure" v-model:arrival="arrival"
                               :pair-index="pairIndex" :airports-by-icao="airportsByIcao" :distance-nm="pairDistance" />
          </div>
        </div>
      </div>
    </div>

    <TripBar v-model:after-arrival="afterArrival" @continue="continueFrom(trip.nextDeparture)" />

    <div v-if="hasSearched && !showBrowse" class="d-flex align-items-center flex-wrap gap-2 mb-3">
      <span class="fs-5 fw-semibold">{{ resultCountLabel }}</span>
      <small v-if="deepPending" class="text-muted">Checking latest schedule…</small>
      <div class="ms-auto d-flex align-items-center flex-wrap gap-2">
        <small class="text-muted">Sort</small>
        <div class="btn-group btn-group-sm" role="group" aria-label="Sort flights">
          <button v-for="[key, label] in SORTS" :key="key" type="button"
                  :class="['btn btn-outline-secondary', sortParams?.by === key && 'active']" @click="sortBy(key)">{{ label }}{{ sortSymbol(key) }}</button>
        </div>
      </div>
    </div>

    <div v-if="routesError && !hasRoutes" class="alert alert-danger">{{ routesError }}</div>

    <BrowseCards v-else-if="showBrowse" :stage="browseStage" :routes="routes" :operators="operators"
                 :airports-by-icao="airportsByIcao" :operator-icao="operatorIcao" :operator-name="selectedOperator?.name ?? null"
                 :current-airport="currentAirport" :home-airport="homeAirport"
                 @back="clearOperator" @select-operator="onBrowseOperator" @select-airport="(i) => (departure = i)" />

    <div v-else-if="!hasSearched" class="text-center text-muted py-5">
      {{ routesLoading ? 'Loading routes…' : 'Pick a departure and arrival airport above to see flights' }}
    </div>

    <div v-else-if="loading" class="text-center my-4">
      <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
    </div>

    <div v-else-if="error && sortedFlights.length === 0" class="alert alert-danger">{{ error }}</div>

    <div v-else-if="sortedFlights.length === 0" class="alert alert-info">
      {{ deepPending ? 'Checking the latest schedule for this route…' : 'No flights are currently scheduled for this selection.' }}
    </div>

    <div v-else>
      <FlightResultCard v-for="f in sortedFlights" :key="String(f.id)" :flight="f"
                        :saved="saved['SV_' + f.id] !== undefined" :can-bid="canBid(f)" :busy="busy.has(f.id)"
                        @bid="addBid" @unbid="removeBid" />
      <div ref="sentinelRef" class="text-center small text-muted" style="min-height: 1.5rem">
        <span v-if="loadingMore">Loading more…</span>
      </div>
    </div>
  </div>
</template>
