<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
  stage: { type: String, required: true }, // 'operator' | 'airport'
  routes: { type: Array, required: true },
  operators: { type: Array, default: () => [] },
  airportsByIcao: { type: Map, required: true },
  operatorIcao: { type: String, default: null },
  operatorName: { type: String, default: null },
  currentAirport: { type: String, default: null },
  homeAirport: { type: String, default: null },
});
defineEmits(['back', 'select-operator', 'select-airport']);

const query = ref('');

// Stock-Bootstrap hover affordance: lift + theme-coloured border on the hovered card
const hovered = ref(null);
// Logos that fail to load fall back to the airline code instead of alt text
const brokenLogos = ref({});
const cardClass = (key, extra = '') => ['card h-100 w-100 text-start text-body', hovered.value === key ? 'shadow border-primary' : 'shadow-sm', extra];
watch(() => props.stage, () => { query.value = ''; });

const plural = (n, w) => `${n} ${w}${n === 1 ? '' : 's'}`;
const matches = (q, ...fields) => fields.some((f) => (f ?? '').toLowerCase().includes(q));
const operatorLabel = computed(() => props.operatorName || props.operatorIcao || 'All airports');

const operatorCards = computed(() => {
  const byOp = new Map();
  for (const r of props.routes) {
    let e = byOp.get(r.operator_icao);
    if (!e) byOp.set(r.operator_icao, (e = { airports: new Set(), routes: 0, deps: new Set() }));
    e.airports.add(r.departure_airport); e.airports.add(r.arrival_airport);
    e.deps.add(r.departure_airport); e.routes++;
  }
  const known = new Map(props.operators.map((o) => [String(o.icao).toUpperCase(), o]));
  const q = query.value.trim().toLowerCase();
  const here = [props.currentAirport, props.homeAirport].filter(Boolean);
  return [...byOp].map(([icao, e]) => {
    const operator = known.get(icao) ?? { icao, name: icao };
    return { icao, iata: operator.iata ?? null, name: operator.name || icao, logo: operator.logo_url ?? null,
      airportCount: e.airports.size, routeCount: e.routes, nearYou: here.find((a) => e.deps.has(a)) ?? null, operator };
  }).filter((c) => !q || matches(q, c.name, c.icao, c.iata))
    .sort((a, b) => Number(!!b.nearYou) - Number(!!a.nearYou) || b.airportCount - a.airportCount || a.name.localeCompare(b.name));
});

const airportCards = computed(() => {
  const dests = new Map();
  for (const r of props.routes) {
    if (props.operatorIcao && r.operator_icao !== props.operatorIcao) continue;
    let e = dests.get(r.departure_airport);
    if (!e) dests.set(r.departure_airport, (e = { to: new Set(), flights: 0 }));
    e.to.add(r.arrival_airport); e.flights += r.flight_number_count;
  }
  return [...dests].map(([icao, e]) => {
    const a = props.airportsByIcao.get(icao);
    return { icao, iata: a?.iata, name: a?.name, destinations: e.to.size, flightNumbers: e.flights };
  });
});

const filteredAirports = computed(() => {
  const q = query.value.trim().toLowerCase();
  return airportCards.value.filter((c) => !q || matches(q, c.icao, c.iata, c.name));
});

const hereCards = computed(() => {
  const out = []; const seen = new Set();
  for (const [icao, label] of [[props.currentAirport, 'Current airport'], [props.homeAirport, 'Home airport']]) {
    if (!icao || seen.has(icao)) continue;
    const card = filteredAirports.value.find((c) => c.icao === icao);
    if (!card) continue;
    seen.add(icao); out.push({ ...card, hereLabel: label });
  }
  return out;
});

const otherCards = computed(() => {
  const pinned = new Set(hereCards.value.map((c) => c.icao));
  return filteredAirports.value.filter((c) => !pinned.has(c.icao))
    .sort((a, b) => b.destinations - a.destinations || a.icao.localeCompare(b.icao));
});
</script>

<template>
  <div>
    <div class="d-flex align-items-center gap-2 mb-3">
      <nav class="d-flex align-items-center gap-2" aria-label="Browse steps">
        <span v-if="stage === 'operator'" class="fw-semibold"><i class="bi bi-airplane-engines" aria-hidden="true"></i> All airlines</span>
        <button v-else type="button" class="btn btn-sm btn-outline-primary" @click="$emit('back')">
          <i class="bi bi-airplane-engines" aria-hidden="true"></i> All airlines
        </button>
        <template v-if="stage === 'airport'">
          <span class="text-muted">›</span>
          <span class="fw-semibold">{{ operatorLabel }}</span>
        </template>
      </nav>
      <input v-model="query" type="text" class="form-control form-control-sm ms-auto" style="max-width: 14rem"
             :placeholder="stage === 'operator' ? 'Filter airlines…' : 'Filter airports…'" />
    </div>

    <!-- Step 1: airlines -->
    <template v-if="stage === 'operator'">
      <p class="text-muted">Choose an airline to see where it flies.</p>
      <div v-if="operatorCards.length" class="row g-3">
        <div v-for="c in operatorCards" :key="c.icao" class="col-12 col-sm-6 col-lg-4">
          <button type="button" :class="cardClass('op-' + c.icao)" @mouseenter="hovered = 'op-' + c.icao" @mouseleave="hovered = null"
                  @click="$emit('select-operator', c.operator)">
            <div class="card-body text-center w-100">
              <div class="d-flex align-items-center justify-content-center mb-2" style="height: 3.5rem">
                <img v-if="c.logo && !brokenLogos[c.icao]" :src="c.logo" :alt="c.name" style="max-height: 3.5rem; max-width: 100%; object-fit: contain"
                     @error="brokenLogos = { ...brokenLogos, [c.icao]: true }" />
                <span v-else class="display-6 fw-semibold">{{ c.iata || c.icao }}</span>
              </div>
              <div class="fs-5 fw-semibold lh-sm">{{ c.name }}</div>
              <div class="small text-muted">{{ [c.iata, c.icao].filter(Boolean).join(' | ') }}</div>
              <div class="small text-muted mt-2">{{ plural(c.airportCount, 'airport') }} · {{ plural(c.routeCount, 'route') }}</div>
              <span v-if="c.nearYou" class="badge rounded-pill bg-success mt-2"><i class="bi bi-geo-alt-fill" aria-hidden="true"></i> Flies from {{ c.nearYou }}</span>
            </div>
          </button>
        </div>
      </div>
      <p v-else class="text-muted">No airlines match that filter.</p>
      <button type="button" class="btn btn-link btn-sm px-0 mt-3" @click="$emit('select-operator', null)">Skip — show every airline's airports</button>
    </template>

    <!-- Step 2: airports -->
    <template v-else>
      <template v-if="hereCards.length">
        <h3 class="fs-6 text-uppercase text-muted fw-semibold mb-2">You are here</h3>
        <div class="row g-3 mb-4">
          <div v-for="c in hereCards" :key="'here-' + c.icao" class="col-12 col-sm-6 col-lg-4">
            <button type="button" :class="cardClass('here-' + c.icao, 'border-success')" @mouseenter="hovered = 'here-' + c.icao" @mouseleave="hovered = null"
                    @click="$emit('select-airport', c.icao)">
              <div class="card-body text-center w-100">
                <div class="display-6 fw-semibold lh-1">{{ c.icao }}</div>
                <div class="small text-muted lh-sm mt-1">{{ c.name || '\u00a0' }}</div>
                <div class="small text-muted mt-2">{{ plural(c.destinations, 'destination') }} · {{ plural(c.flightNumbers, 'flight') }}</div>
                <span class="badge rounded-pill bg-success mt-2"><i class="bi bi-geo-alt-fill" aria-hidden="true"></i> {{ c.hereLabel }}</span>
              </div>
            </button>
          </div>
        </div>
      </template>

      <h3 class="fs-6 text-uppercase text-muted fw-semibold mb-2">Departure airports <span class="text-lowercase fw-normal">({{ otherCards.length }})</span></h3>
      <div v-if="otherCards.length" class="row g-3">
        <div v-for="c in otherCards" :key="c.icao" class="col-12 col-sm-6 col-lg-4">
          <button type="button" :class="cardClass('ap-' + c.icao)" @mouseenter="hovered = 'ap-' + c.icao" @mouseleave="hovered = null"
                  @click="$emit('select-airport', c.icao)">
            <div class="card-body text-center w-100">
              <div class="display-6 fw-semibold lh-1">{{ c.icao }}</div>
              <div class="small text-muted lh-sm mt-1">{{ c.name || '\u00a0' }}</div>
              <div class="small text-muted mt-2">{{ plural(c.destinations, 'destination') }} · {{ plural(c.flightNumbers, 'flight') }}</div>
            </div>
          </button>
        </div>
      </div>
      <p v-else class="text-muted">No airports match that filter.</p>
    </template>
  </div>
</template>
