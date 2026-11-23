<script setup>
import { computed, watch } from 'vue';
import AirportCombobox from './AirportCombobox.vue';

const RECENTS_KEY = 'svmod.recentPairs.v1';
const MAX_RECENTS = 8;

const props = defineProps({
  departure: { type: String, default: null },
  arrival: { type: String, default: null },
  pairIndex: { type: Map, required: true },
  airportsByIcao: { type: Map, required: true },
  distanceNm: { type: Number, default: null },
});
const emit = defineEmits(['update:departure', 'update:arrival']);

const departureModel = computed({ get: () => props.departure, set: (v) => emit('update:departure', v) });
const arrivalModel = computed({ get: () => props.arrival, set: (v) => emit('update:arrival', v) });

const plural = (n, w) => `${n} ${w}${n === 1 ? '' : 's'}`;
function opt(icao, hint) {
  const a = props.airportsByIcao.get(icao);
  return { icao, iata: a?.iata, name: a?.name, hint };
}

const reverseIndex = computed(() => {
  const rev = new Map();
  for (const [dep, arrivals] of props.pairIndex) {
    for (const [arr, info] of arrivals) {
      let deps = rev.get(arr);
      if (!deps) rev.set(arr, (deps = new Map()));
      deps.set(dep, info);
    }
  }
  return rev;
});

const departureOptions = computed(() => {
  const list = props.arrival
    ? [...(reverseIndex.value.get(props.arrival) ?? new Map())].map(([i, info]) => opt(i, plural(info.flightNumberCount, 'flight')))
    : [...props.pairIndex].map(([i, arrs]) => opt(i, plural(arrs.size, 'destination')));
  return list.sort((a, b) => a.icao.localeCompare(b.icao));
});

const arrivalOptions = computed(() => {
  const list = props.departure
    ? [...(props.pairIndex.get(props.departure) ?? new Map())].map(([i, info]) =>
        opt(i, `${plural(info.flightNumberCount, 'flight')} · ${info.operators.join(', ')}`))
    : [...reverseIndex.value].map(([i, deps]) => opt(i, plural(deps.size, 'origin')));
  return list.sort((a, b) => a.icao.localeCompare(b.icao));
});

// Drop selections that stopped being valid (operator filter changed, routes refreshed)
watch(() => [props.pairIndex, props.departure, props.arrival], () => {
  if (props.pairIndex.size === 0) return;
  if (props.departure && !props.pairIndex.has(props.departure)) { emit('update:departure', null); return; }
  if (props.departure && props.arrival && !props.pairIndex.get(props.departure)?.has(props.arrival)) emit('update:arrival', null);
});

const canSwap = computed(() => !!props.departure && !!props.arrival && !!props.pairIndex.get(props.arrival)?.has(props.departure));
const swapTitle = computed(() => (!props.departure || !props.arrival) ? 'Select both airports to swap'
  : canSwap.value ? 'Swap airports' : 'No flights on the return route');
function swap() {
  if (!canSwap.value) return;
  const d = props.departure; const a = props.arrival;
  emit('update:departure', a); emit('update:arrival', d);
}

const pairSummary = computed(() => {
  if (!props.departure || !props.arrival) return '';
  const info = props.pairIndex.get(props.departure)?.get(props.arrival);
  const parts = [];
  if (props.distanceNm) parts.push(`${props.distanceNm.toLocaleString()} nm`);
  if (info) { parts.push(plural(info.flightNumberCount, 'flight number')); parts.push(info.operators.join(', ')); }
  return parts.join(' · ');
});

function readRecents() {
  try {
    const p = JSON.parse(localStorage.getItem(RECENTS_KEY) || '[]');
    return Array.isArray(p) ? p.filter((x) => typeof x === 'string') : [];
  } catch { return []; }
}
function pushRecent(dep, arr) {
  const key = `${dep}-${arr}`;
  const next = [key, ...readRecents().filter((k) => k !== key)].slice(0, MAX_RECENTS);
  try { localStorage.setItem(RECENTS_KEY, JSON.stringify(next)); } catch { /* ignore */ }
}
watch(() => [props.departure, props.arrival], ([d, a]) => { if (d && a) pushRecent(d, a); }, { immediate: true });

const chips = computed(() => {
  if (props.departure && !props.arrival) {
    const arrivals = props.pairIndex.get(props.departure);
    if (!arrivals) return [];
    return [...arrivals].sort((a, b) => b[1].flightNumberCount - a[1].flightNumberCount).slice(0, 6)
      .map(([icao]) => ({ key: icao, label: icao, apply: () => emit('update:arrival', icao) }));
  }
  if (!props.departure && !props.arrival) {
    return readRecents().map((k) => k.split('-')).filter(([d, a]) => props.pairIndex.get(d)?.has(a))
      .map(([d, a]) => ({ key: `${d}-${a}`, label: `${d} → ${a}`,
        apply: () => { emit('update:departure', d); emit('update:arrival', a); } }));
  }
  return [];
});
const chipsLabel = computed(() => (props.departure ? 'Popular from here:' : 'Recent:'));
</script>

<template>
  <div>
    <div class="d-flex align-items-end gap-2">
      <AirportCombobox v-model="departureModel" :options="departureOptions" label="From" placeholder="Departure airport" class="flex-fill" />
      <button type="button" class="btn btn-sm btn-outline-secondary mb-0" :disabled="!canSwap" :title="swapTitle" aria-label="Swap airports" @click="swap">⇄</button>
      <AirportCombobox v-model="arrivalModel" :options="arrivalOptions" label="To" placeholder="Arrival airport" class="flex-fill" />
    </div>
    <p v-if="pairSummary" class="small text-muted mt-2 mb-0">{{ pairSummary }}</p>
    <div v-else-if="chips.length" class="d-flex flex-wrap align-items-center gap-1 mt-2">
      <small class="text-muted">{{ chipsLabel }}</small>
      <button v-for="c in chips" :key="c.key" type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-0" @click="c.apply()">{{ c.label }}</button>
    </div>
  </div>
</template>
