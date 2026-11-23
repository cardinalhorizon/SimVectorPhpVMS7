<script setup>
import { useTripStore } from '../stores/trip';

defineProps({ afterArrival: { type: Boolean, default: true } });
defineEmits(['continue', 'update:afterArrival']);
const trip = useTripStore();
</script>

<template>
  <div v-if="trip.legs.length" class="card mb-3 shadow-sm border-primary">
    <div class="card-body py-2 d-flex align-items-center flex-wrap gap-2">
      <span class="badge bg-primary text-uppercase"><i class="bi bi-signpost-split-fill" aria-hidden="true"></i> Trip</span>
      <template v-for="(leg, i) in trip.legs" :key="String(leg.id)">
        <strong v-if="i === 0">{{ leg.departure }}</strong>
        <span :class="trip.connectionNotes[i] ? 'text-warning' : 'text-muted'" :title="trip.connectionNotes[i] || undefined">
          <span v-if="trip.connectionNotes[i]">⚠ </span>→
        </span>
        <span class="badge rounded-pill bg-body text-body border d-inline-flex align-items-center gap-1 fw-normal">
          <strong>{{ leg.arrival }}</strong>
          <small class="text-muted">{{ leg.callsign }}</small>
          <button type="button" class="btn-close" style="font-size: .5rem" :aria-label="`Remove ${leg.callsign} from trip`" @click="trip.removeLeg(leg.id)"></button>
        </span>
      </template>
      <div class="ms-auto d-flex align-items-center gap-2">
        <label class="form-check-label small text-muted">
          <input type="checkbox" class="form-check-input me-1" :checked="afterArrival" @change="$emit('update:afterArrival', $event.target.checked)" />
          Only flights after I land
        </label>
        <button type="button" class="btn btn-sm btn-outline-secondary py-0" @click="$emit('continue')">Continue from {{ trip.nextDeparture }}</button>
        <button type="button" class="btn btn-sm btn-link text-danger py-0" @click="trip.clear()">Clear</button>
      </div>
    </div>
  </div>
</template>
