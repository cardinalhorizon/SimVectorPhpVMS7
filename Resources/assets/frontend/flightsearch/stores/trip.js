import { defineStore } from 'pinia';
import { utcMs } from '../lib/normalizeFlight';

const TRIP_KEY = 'svmod.trip.v1';

function load() {
  try {
    const parsed = JSON.parse(localStorage.getItem(TRIP_KEY) || '[]');
    return Array.isArray(parsed) ? parsed : [];
  } catch { return []; }
}
function save(legs) {
  try { localStorage.setItem(TRIP_KEY, JSON.stringify(legs)); } catch { /* session only */ }
}

export const useTripStore = defineStore('trip', {
  state: () => ({ legs: load() }),
  getters: {
    lastLeg: (s) => s.legs[s.legs.length - 1] ?? null,
    nextDeparture() { return this.lastLeg?.arrival ?? null; },
    lastArrivalMs() { const l = this.lastLeg; return l ? utcMs(l.arrivalDate, l.arrivalTime) : null; },
    connectionNotes: (s) => s.legs.map((leg, i) => {
      if (i === 0) return '';
      const prev = s.legs[i - 1];
      if (prev.arrival !== leg.departure) return `Starts at ${leg.departure}, but the previous leg lands at ${prev.arrival}`;
      const landed = utcMs(prev.arrivalDate, prev.arrivalTime);
      const departs = utcMs(leg.departureDate, leg.departureTime);
      if (landed !== null && departs !== null && departs < landed) return 'Departs before the previous leg lands';
      return '';
    }),
  },
  actions: {
    addLeg(flight) {
      if (this.legs.some((l) => l.id === flight.id)) return;
      this.legs.push({ ...flight });
      save(this.legs);
    },
    removeLeg(id) { this.legs = this.legs.filter((l) => l.id !== id); save(this.legs); },
    clear() { this.legs = []; save(this.legs); },
  },
});
