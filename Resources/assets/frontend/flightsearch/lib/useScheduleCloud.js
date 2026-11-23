import { computed, inject, ref, shallowRef } from 'vue';
import { greatCircleNm, normalizeFlight } from './normalizeFlight';

const PAGE_SIZE = 50;
const DEEP_TTL_MS = 60 * 60 * 1000; // the server pulls a pair at most once per hour
const ROUTES_CACHE_KEY = 'svmod.routes.v1';
const DEEP_CACHE_KEY = 'svmod.deep.v1';

function readJson(key) {
  try {
    const raw = localStorage.getItem(key);
    return raw ? JSON.parse(raw) : null;
  } catch { return null; }
}
function writeJson(key, value) {
  try { localStorage.setItem(key, JSON.stringify(value)); } catch { /* storage unavailable */ }
}

/** Module endpoints answer {error:{code,message}} */
export function describeApiError(err) {
  if (err?.code === 'ERR_CANCELED') return '';
  const e = err?.response?.data?.error;
  switch (e?.code) {
    case 'subscription_required': return "This community's SimVector plan does not include Schedule Cloud.";
    case 'community_required': return 'The SimVector API key is not tied to a community.';
  }
  return e?.message || err?.message || 'Failed to fetch flights';
}

export function useScheduleCloud() {
  const api = inject('api');

  /* ---------- Routes index ---------- */
  const airports = shallowRef([]);
  const routes = shallowRef([]);
  const routesLoading = ref(false);
  const routesError = ref(null);

  async function loadRoutes() {
    routesLoading.value = true;
    routesError.value = null;
    const cached = readJson(ROUTES_CACHE_KEY);
    if (cached?.data) {
      airports.value = cached.data.airports || [];
      routes.value = cached.data.routes || [];
    }
    try {
      const res = await api.get('/simvector/routes', {
        headers: cached?.etag ? { 'If-None-Match': cached.etag } : undefined,
        validateStatus: (s) => (s >= 200 && s < 300) || s === 304,
      });
      if (res.status === 200 && res.data?.data) {
        airports.value = res.data.data.airports || [];
        routes.value = res.data.data.routes || [];
        writeJson(ROUTES_CACHE_KEY, { etag: res.headers?.etag, data: res.data.data });
      }
    } catch (err) {
      if (!cached?.data) routesError.value = describeApiError(err);
    } finally {
      routesLoading.value = false;
    }
  }

  const airportsByIcao = computed(() => new Map(airports.value.map((a) => [a.icao, a])));

  function routesFor(op) {
    if (!op) return routes.value;
    const o = op.toUpperCase();
    return routes.value.filter((r) => r.operator_icao === o);
  }

  /** dep icao -> Map(arr icao -> {operators, flightNumberCount}) */
  function buildPairIndex(op) {
    const pairs = new Map();
    for (const r of routesFor(op)) {
      let arrivals = pairs.get(r.departure_airport);
      if (!arrivals) pairs.set(r.departure_airport, (arrivals = new Map()));
      const info = arrivals.get(r.arrival_airport) || { operators: [], flightNumberCount: 0 };
      if (!info.operators.includes(r.operator_icao)) info.operators.push(r.operator_icao);
      info.flightNumberCount += r.flight_number_count;
      arrivals.set(r.arrival_airport, info);
    }
    return pairs;
  }

  function distanceBetween(dep, arr) {
    if (!dep || !arr) return null;
    const a = airportsByIcao.value.get(dep);
    const b = airportsByIcao.value.get(arr);
    if (a?.latitude == null || a?.longitude == null || b?.latitude == null || b?.longitude == null) return null;
    return Math.round(greatCircleNm(a.latitude, a.longitude, b.latitude, b.longitude));
  }

  /* ---------- Search: shallow first, deep behind it ---------- */
  const flights = ref([]);
  const loading = ref(false);
  const loadingMore = ref(false);
  const deepPending = ref(false);
  const error = ref(null);
  const hasSearched = ref(false);
  const nextCursor = ref(null);

  let seq = 0;
  let controller = null;
  let current = null;

  function mergeFlights(incoming) {
    const byId = new Map(flights.value.map((f) => [f.id, f]));
    for (const f of incoming) byId.set(f.id, f);
    flights.value = [...byId.values()].sort((a, b) =>
      `${a.departureDate ?? ''} ${a.departureTime ?? ''}`.localeCompare(`${b.departureDate ?? ''} ${b.departureTime ?? ''}`));
  }

  function baseParams(pair, operator) {
    const p = { limit: PAGE_SIZE };
    if (pair.departure) p.departure_icao = pair.departure;
    if (pair.arrival) p.arrival_icao = pair.arrival;
    if (operator) p.operators = operator;
    return p;
  }

  const deepKey = (pair, op) => `${pair.departure}-${pair.arrival}-${op ?? ''}`;
  function deepRecentlyDone(key) {
    const at = readJson(DEEP_CACHE_KEY)?.[key];
    return typeof at === 'number' && Date.now() - at < DEEP_TTL_MS;
  }
  function markDeepDone(key) {
    const all = readJson(DEEP_CACHE_KEY) || {};
    const now = Date.now();
    for (const k of Object.keys(all)) if (now - all[k] > DEEP_TTL_MS) delete all[k];
    all[key] = now;
    writeJson(DEEP_CACHE_KEY, all);
  }

  const toDisplay = (res, distance) =>
    (Array.isArray(res?.data) ? res.data : []).map((i) => normalizeFlight(i, distance));

  function reset() {
    seq++;
    controller?.abort();
    controller = null;
    current = null;
    flights.value = [];
    nextCursor.value = null;
    error.value = null;
    loading.value = false;
    loadingMore.value = false;
    deepPending.value = false;
    hasSearched.value = false;
  }

  async function search(pair, operator = null) {
    if (!pair.departure && !pair.arrival) { reset(); return; }
    const mySeq = ++seq;
    controller?.abort();
    controller = new AbortController();
    const signal = controller.signal;
    const distance = distanceBetween(pair.departure, pair.arrival);
    current = { pair, operator, distance };

    flights.value = [];
    nextCursor.value = null;
    error.value = null;
    loading.value = true;
    loadingMore.value = false;
    deepPending.value = false;
    hasSearched.value = true;

    const isCurrent = () => mySeq === seq;
    const params = baseParams(pair, operator);

    const shallow = api.get('/simvector/flights', { params: { ...params, depth: 'shallow' }, signal })
      .then((res) => {
        if (!isCurrent()) return;
        mergeFlights(toDisplay(res.data, distance));
        nextCursor.value = res.data?.meta?.next_cursor ?? null;
      })
      .catch((err) => { if (isCurrent() && !signal.aborted) error.value = describeApiError(err); })
      .finally(() => { if (isCurrent()) loading.value = false; });

    const key = deepKey(pair, operator);
    if (pair.departure && pair.arrival && !deepRecentlyDone(key)) {
      deepPending.value = true;
      const deep = api.get('/simvector/flights', { params: { ...params, depth: 'deep' }, signal })
        .then((res) => {
          if (!isCurrent()) return;
          markDeepDone(key);
          mergeFlights(toDisplay(res.data, distance));
          if (flights.value.length > 0) error.value = null;
          if (!nextCursor.value && error.value === null && !loading.value && res.data?.meta?.next_cursor) {
            nextCursor.value = res.data.meta.next_cursor;
          }
        })
        .catch(() => { /* deep never blocks: shallow results are the fallback */ })
        .finally(() => { if (isCurrent()) deepPending.value = false; });
      await Promise.all([shallow, deep]);
    } else {
      await shallow;
    }
  }

  /** Cursor pages never trigger provider pulls. */
  async function loadMore() {
    if (!current || !nextCursor.value || loadingMore.value || loading.value) return;
    const mySeq = seq;
    const { pair, operator, distance } = current;
    loadingMore.value = true;
    try {
      const res = await api.get('/simvector/flights', {
        params: { ...baseParams(pair, operator), depth: 'shallow', cursor: nextCursor.value },
        signal: controller?.signal,
      });
      if (mySeq !== seq) return;
      mergeFlights(toDisplay(res.data, distance));
      nextCursor.value = res.data?.meta?.next_cursor ?? null;
    } catch (err) {
      if (mySeq !== seq) return;
      const code = err?.response?.data?.error?.code;
      if (code === 'invalid_cursor' || code === 'cursor_requires_limit') {
        nextCursor.value = null;
        void search(pair, operator);
      } else if (err?.code !== 'ERR_CANCELED') {
        nextCursor.value = null;
      }
    } finally {
      if (mySeq === seq) loadingMore.value = false;
    }
  }

  const hasMore = computed(() => nextCursor.value !== null);

  return {
    airports, routes, routesLoading, routesError, airportsByIcao, loadRoutes, routesFor, buildPairIndex, distanceBetween,
    flights, loading, loadingMore, deepPending, error, hasSearched, hasMore, search, loadMore, reset,
  };
}
