// Normalises a Schedule Cloud flight (format=schedule_cloud) for display.
export function normalizeTime(value) {
  const m = value ? String(value).match(/(\d{2}:\d{2})/) : null;
  return m ? m[1] : undefined;
}

export function normalizeDate(value) {
  const m = value ? String(value).match(/^(\d{4}-\d{2}-\d{2})/) : null;
  return m ? m[1] : undefined;
}

export function parseDurationMinutes(raw) {
  if (typeof raw === 'number' && Number.isFinite(raw)) return raw;
  if (typeof raw === 'string' && raw.trim()) {
    const t = raw.trim();
    if (/^\d+$/.test(t)) return Number(t);
    const hhmm = t.match(/^(\d{1,2}):(\d{2})$/);
    if (hhmm) return Number(hhmm[1]) * 60 + Number(hhmm[2]);
  }
  return null;
}

function airportCode(a) {
  if (!a) return '----';
  if (typeof a === 'string') return a.trim().toUpperCase() || '----';
  return (a.icao || a.iata || '').trim().toUpperCase() || '----';
}

/** Great-circle distance in nautical miles */
export function greatCircleNm(lat1, lon1, lat2, lon2) {
  const rad = (d) => (d * Math.PI) / 180;
  const dLat = rad(lat2 - lat1);
  const dLon = rad(lon2 - lon1);
  const a = Math.sin(dLat / 2) ** 2 + Math.cos(rad(lat1)) * Math.cos(rad(lat2)) * Math.sin(dLon / 2) ** 2;
  return 2 * 3440.065 * Math.asin(Math.min(1, Math.sqrt(a)));
}

/** UTC ms for date + HH:mm, or null */
export function utcMs(date, time) {
  if (!date || !time) return null;
  const ms = Date.parse(`${date}T${time}:00Z`);
  return Number.isNaN(ms) ? null : ms;
}

export function normalizeFlight(item, distanceNm = null) {
  const flightIcao = String(item.flight_icao || '').trim();
  const callsign = String(item.atc_callsign || flightIcao).trim() || 'UNKN';
  const iata = item.operator?.iata;
  const ident = iata && item.flight_number != null && item.flight_number !== ''
    ? `${iata}${item.flight_number}` : flightIcao || undefined;
  const aircraft = Array.isArray(item.known_aircraft) && item.known_aircraft.length
    ? item.known_aircraft.map(String).filter(Boolean)
    : item.aircraft ? [String(item.aircraft)] : [];

  return {
    id: item.natural_key,
    callsign,
    ident,
    airlineName: item.operator?.name ?? undefined,
    airlineLogo: item.operator?.logo_url ?? undefined,
    departure: airportCode(item.departure_airport),
    departureName: typeof item.departure_airport === 'object' ? item.departure_airport?.name : undefined,
    departureTime: normalizeTime(item.departure_time_utc),
    departureDate: normalizeDate(item.departure_time_utc),
    arrival: airportCode(item.arrival_airport),
    arrivalName: typeof item.arrival_airport === 'object' ? item.arrival_airport?.name : undefined,
    arrivalTime: normalizeTime(item.arrival_time_utc),
    arrivalDate: normalizeDate(item.arrival_time_utc),
    durationMinutes: parseDurationMinutes(item.duration),
    distanceNm,
    aircraft,
    source: item.is_live_data ? 'live' : 'cloud',
  };
}
