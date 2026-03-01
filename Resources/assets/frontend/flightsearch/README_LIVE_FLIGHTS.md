# Live Flight Search Vue Component

A Vue 3 component system for displaying and bidding on live flights from SimVector Schedule Cloud, built with Bootstrap 5.

## Component Structure

### Main Component
- **LiveFlightSearch.vue** - Main container component for live flights

### Child Components
- **LiveFlightSearchForm.vue** - Simplified search form for live flights
- **LiveFlightTable.vue** - Displays sortable flight table headers and flight cards
- **LiveFlightCard.vue** - Individual flight card optimized for live flight data
- **FlightPagination.vue** - Reused Bootstrap 5 pagination component

## Live Flight Data Structure

The component expects flight data in the following format from SimVector Schedule Cloud:

```json
{
  "id": 2706681,
  "natural_key": "DAL7LAXHND260105",
  "operator_icao": "DAL",
  "flight_number": "7",
  "departure_airport": "KLAX",
  "arrival_airport": "RJTT",
  "departure_time": "2026-01-05 17:25:00",
  "arrival_time": "2026-01-06 05:00:00",
  "duration": 695,
  "aircraft_type": "A359",
  "known_aircraft": ["A359"],
  "notes": "SimVector Schedule Cloud || DEP TERM: N/A // DEP GATE: N/A // ARR TERM: N/A // ARR GATE: N/A"
}
```

## Features

### Simplified for Live Flights
- **No SimBrief integration** - Live flights don't support SimBrief
- **No vmsACARS integration** - Live flights are server-based
- **No PIREP creation** - Live flights can only be bid on
- **Bidding only** - Simple add/remove bid functionality

### Live Flight Search Form
- Airline Code search (e.g., DAL, UAL)
- Flight Number search
- Departure Airport search (ICAO code)
- Arrival Airport search (ICAO code)
- Aircraft Type search

### Flight Display
- Sortable columns (operator, flight number, departure, arrival, times, duration, aircraft)
- Card-based display with:
  - Operator ICAO and flight number
  - Departure/Arrival airports with times and dates
  - Flight duration
  - Aircraft type with known aircraft list
  - Flight notes
- Client-side sorting for fast UX
- Responsive design (mobile accordion, desktop sidebar)

### Flight Actions
- Add/Remove bid (respects `only_flights_from_current` setting)

### Data Hydration
- Initial data loaded from Blade template via `data-app` attribute
- Supports pagination from server
- Can reload data via API calls

## Blade Template Integration

The Blade template hydrates the Vue component with initial data:

```blade
@section('content')
    <div id="app" data-app='@json([
        "flights" => $flights->items(),
        "saved" => $saved ?? [],
        "user" => [
            "id" => Auth::user()->id ?? null,
            "current_airport" => [
                "icao" => Auth::user()->current_airport->icao ?? null,
            ] ?? null,
        ],
        "pagination" => [
            "current_page" => $flights->currentPage(),
            "last_page" => $flights->lastPage(),
            "total" => $flights->total(),
            "per_page" => $flights->perPage(),
            "from" => $flights->firstItem() ?? 0,
            "to" => $flights->lastItem() ?? 0,
        ],
        "onlyFlightsFromCurrent" => setting("pilots.only_flights_from_current", false),
        "apiBaseUrl" => url("/api"),
        "apiKey" => Auth::user()->api_key ?? "",
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'></div>
@endsection
```

## Build Configuration

The component is built using Laravel Mix. Add to `webpack.mix.js`:

```javascript
mix.setPublicPath(modulePublicPath)
  .js('Resources/assets/frontend/js/flightsearch.js', 'frontend/js/flightsearch.js')
  .vue()
  .version();
```

## Building

```bash
cd modules/SimVector
npm install
npm run dev    # Development build
npm run prod   # Production build
```

## TODO Items

### LiveFlightSearch.vue
- [ ] Implement API call to fetch live flights with pagination
- [ ] Implement add bid API call
- [ ] Implement remove bid API call
- [ ] Add error handling and user feedback

### Integration
- [ ] Create API endpoints for live flights CRUD
- [ ] Implement bid management for live flights
- [ ] Add loading states and error messages
- [ ] Test pagination functionality

## Key Differences from Regular Flights

| Feature | Regular Flights | Live Flights |
|---------|----------------|--------------|
| Data Source | phpVMS database | SimVector Schedule Cloud |
| SimBrief | ✅ Yes | ❌ No |
| vmsACARS | ✅ Yes | ❌ No |
| PIREP | ✅ Yes | ❌ No |
| Bidding | ✅ Yes | ✅ Yes |
| View Details | ✅ Yes | ❌ No (minimal data) |
| Search Fields | 9+ fields | 5 fields |
| Aircraft Selection | Modal-based | N/A |

## Props (LiveFlightSearch.vue)

- `initialFlights` (Array) - Flight data from server
- `initialSaved` (Object) - User's saved bids
- `initialUser` (Object) - Current user data
- `initialPagination` (Object) - Pagination metadata
- `onlyFlightsFromCurrent` (Boolean) - Restrict bids to current airport
- `apiBaseUrl` (String) - Base URL for API calls
- `apiKey` (String) - User's API key

## Events

### LiveFlightSearchForm.vue
- `@search` - Emitted when search is submitted
- `@reset` - Emitted when reset is clicked

### LiveFlightTable.vue
- `@sort` - Emitted when column header is clicked
- `@add-bid` - Emitted when add bid is clicked
- `@remove-bid` - Emitted when remove bid is clicked

## Usage Example

The component is automatically initialized by the entry point file:

```javascript
// flightsearch.js
import { createApp } from 'vue';
import LiveFlightSearch from './LiveFlightSearch.vue';

const appElement = document.getElementById('app');
const appData = JSON.parse(appElement.dataset.app || '{}');

createApp(LiveFlightSearch, {
  initialFlights: appData.flights || [],
  initialSaved: appData.saved || {},
  // ... other props
}).mount('#app');
```

## Notes

- Uses Bootstrap 5 classes exclusively (no scoped styles)
- Client-side sorting for better UX (data already loaded)
- Simplified UI focused on viewing and bidding
- Date/time formatting uses browser's locale
- Duration displayed in hours and minutes format

