<script setup>
import { computed } from 'vue';

const props = defineProps({
  flight: { type: Object, required: true },
  saved: { type: Boolean, default: false },
  canBid: { type: Boolean, default: true },
  busy: { type: Boolean, default: false },
});
defineEmits(['bid', 'unbid']);

const durationText = computed(() => {
  const m = props.flight.durationMinutes;
  if (typeof m !== 'number' || Number.isNaN(m)) return null;
  return `${Math.floor(m / 60)}h ${String(m % 60).padStart(2, '0')}m`;
});
const distanceText = computed(() => (typeof props.flight.distanceNm === 'number' ? `${props.flight.distanceNm.toLocaleString()} nm` : null));
const summaryText = computed(() => [durationText.value, distanceText.value].filter(Boolean).join(' / '));

const aircraftText = computed(() => {
  const a = props.flight.aircraft;
  if (!a?.length) return 'Any Aircraft';
  return a.length > 3 ? `${a.slice(0, 3).join(', ')} +${a.length - 3}` : a.join(', ');
});

/** 'YYYY-MM-DD' (UTC) -> 'Sun, Nov 22' */
function prettyDate(d) {
  if (!d) return '';
  const date = new Date(`${d}T00:00:00Z`);
  if (Number.isNaN(date.getTime())) return d;
  return date.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric', timeZone: 'UTC' });
}
// Arrival on a later calendar day than departure -> "+1"
const dayOffset = computed(() => {
  const { departureDate: d, arrivalDate: a } = props.flight;
  if (!d || !a) return 0;
  return Math.round((Date.parse(`${a}T00:00:00Z`) - Date.parse(`${d}T00:00:00Z`)) / 86400000);
});
</script>

<template>
  <div class="card mb-3 shadow-sm">
    <div class="card-body">
      <!-- Airline + source -->
      <div class="d-flex justify-content-between align-items-start small text-muted">
        <span class="d-flex align-items-center gap-2">
          <img v-if="flight.airlineLogo" :src="flight.airlineLogo" alt="" style="height: 1.5rem; width: 2rem; object-fit: contain" />
          {{ flight.airlineName || 'Virtual Airline' }}
        </span>
        <span v-if="flight.source === 'live'" class="badge bg-success">Live Flight</span>
        <span v-else class="badge bg-primary">Schedule Cloud</span>
      </div>

      <!-- Callsign | ident -->
      <div class="fs-3 fw-semibold text-center lh-sm mt-1">
        {{ flight.callsign }}<span v-if="flight.ident" class="text-muted fw-normal"> | {{ flight.ident }}</span>
      </div>

      <!-- Route -->
      <div class="row align-items-start text-center g-2 mt-2">
        <div class="col-5">
          <div class="display-6 fw-semibold lh-1">{{ flight.departure }}</div>
          <div class="small text-muted lh-sm mt-1">{{ flight.departureName || ' ' }}</div>
          <div class="fs-4 mt-2 lh-1">{{ flight.departureTime || '--:--' }}<small class="fs-6 text-muted"> UTC</small></div>
          <div class="small text-muted">{{ prettyDate(flight.departureDate) }}</div>
        </div>

        <div class="col-2 pt-2">
          <div class="fs-5 text-muted lh-1"><i class="bi bi-airplane-fill d-inline-block" style="transform: rotate(90deg)" aria-hidden="true"></i><span class="visually-hidden">to</span></div>
          <div v-if="summaryText" class="small text-muted mt-2 lh-sm">{{ summaryText }}</div>
        </div>

        <div class="col-5">
          <div class="display-6 fw-semibold lh-1">{{ flight.arrival }}</div>
          <div class="small text-muted lh-sm mt-1">{{ flight.arrivalName || ' ' }}</div>
          <div class="fs-4 mt-2 lh-1">
            {{ flight.arrivalTime || '--:--' }}<small class="fs-6 text-muted"> UTC</small>
            <sup v-if="dayOffset > 0" class="small text-muted">+{{ dayOffset }}</sup>
          </div>
          <div class="small text-muted">{{ prettyDate(flight.arrivalDate) }}</div>
        </div>
      </div>

      <!-- Aircraft + action -->
      <div class="d-flex justify-content-between align-items-center mt-3">
        <div class="text-muted"><i class="bi bi-airplane-fill" aria-hidden="true"></i> {{ aircraftText }}</div>
        <div v-if="canBid">
          <button v-if="saved" type="button" class="btn btn-sm btn-warning" :disabled="busy" @click="$emit('unbid', flight)">
            <i class="bi bi-bookmark-x-fill" aria-hidden="true"></i> Remove Bid
          </button>
          <button v-else type="button" class="btn btn-sm btn-success" :disabled="busy" @click="$emit('bid', flight)">
            <i class="bi bi-bookmark-plus-fill" aria-hidden="true"></i> Add Bid
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
