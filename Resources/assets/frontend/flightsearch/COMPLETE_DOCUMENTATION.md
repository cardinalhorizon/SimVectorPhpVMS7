# Flight Search Component System - Complete Documentation

## Overview

This project contains two separate flight search implementations:
1. **Regular Flight Search** - For phpVMS database flights (full features)
2. **Live Flight Search** - For SimVector Schedule Cloud flights (bidding only)

## Directory Structure

```
modules/SimVector/Resources/assets/frontend/
├── js/
│   └── flightsearch.js                      # Entry point for Live Flight Search
├── flightsearch/
│   ├── FlightSearch.vue                     # Main component for regular flights
│   ├── LiveFlightSearch.vue                 # Main component for live flights
│   ├── README.md                            # Regular flights documentation
│   ├── README_LIVE_FLIGHTS.md              # Live flights documentation
│   └── components/
│       ├── FlightSearchForm.vue             # Search form (regular flights)
│       ├── FlightCard.vue                   # Flight card (regular flights)
│       ├── FlightTable.vue                  # Flight table (regular flights)
│       ├── FlightPagination.vue            # Pagination (shared)
│       ├── BidModal.vue                     # Aircraft selection modal (regular)
│       ├── LiveFlightSearchForm.vue        # Search form (live flights)
│       ├── LiveFlightCard.vue              # Flight card (live flights)
│       └── LiveFlightTable.vue             # Flight table (live flights)
```

## Component Comparison

### Regular Flights (FlightSearch.vue)

**Features:**
- Full flight search with 9+ filter fields
- SimBrief integration
- vmsACARS integration
- PIREP creation
- Aircraft selection modal
- View flight details
- Add/Remove bids

**Use Case:** phpVMS database flights with complete metadata

**Data Source:** phpVMS API

### Live Flights (LiveFlightSearch.vue)

**Features:**
- Simplified search with 5 filter fields
- Add/Remove bids only
- No SimBrief/vmsACARS/PIREP
- Client-side sorting
- Minimal flight metadata

**Use Case:** Real-time flights from SimVector Schedule Cloud

**Data Source:** SimVector API + Blade template hydration

## Build Setup

### Prerequisites
```bash
cd modules/SimVector
npm install
```

### Webpack Configuration
Both components are configured in `webpack.mix.js`:

```javascript
mix.setPublicPath(modulePublicPath)
  .js('Resources/assets/admin/admin.js', 'admin/js/admin.js')
  .js('Resources/assets/frontend/js/flightsearch.js', 'frontend/js/flightsearch.js')
  .vue()
  .version();
```

### Build Commands
```bash
npm run dev     # Development build with source maps
npm run watch   # Watch mode for development
npm run prod    # Production build (minified)
```

## Implementation Status

### ✅ Completed
- [x] Component structure for both systems
- [x] Bootstrap 5 styling (no scoped styles)
- [x] Responsive layouts (mobile/desktop)
- [x] Client-side sorting
- [x] Pagination component
- [x] Blade template integration for live flights
- [x] Webpack build configuration
- [x] Entry point file

### 🔄 TODO - API Integration

#### Regular Flights (FlightSearch.vue)
- [ ] Load flights from API
- [ ] Load dropdown data (airlines, flight types, subfleets, etc.)
- [ ] Implement airport search
- [ ] Add bid API call
- [ ] Remove bid API call
- [ ] Search with filters API call
- [ ] Load available aircraft for bid modal

#### Live Flights (LiveFlightSearch.vue)
- [ ] Load live flights from API (if needed beyond initial hydration)
- [ ] Add bid API call for live flights
- [ ] Remove bid API call for live flights
- [ ] Search/filter live flights API call

## Data Structures

### Regular Flight Object
```javascript
{
  id: 123,
  airline: { id: 1, name: "American Airlines", icao: "AAL", logo: "url" },
  flight_number: "123",
  ident: "AAL123",
  callsign: "AMERICAN 123",
  atc: "AAL123",
  flight_type: "J",
  flight_type_label: "Passenger",
  dpt_airport_id: "KLAX",
  arr_airport_id: "KJFK",
  dpt_airport: { name: "Los Angeles Intl" },
  arr_airport: { name: "John F Kennedy Intl" },
  dpt_time: "10:00",
  arr_time: "18:30",
  flight_time: 330,  // minutes
  distance: 2475,    // nautical miles
  subfleets: [{ id: 1, type: "B738", name: "Boeing 737-800" }],
  simbrief: { id: 1, user_id: 123 }
}
```

### Live Flight Object
```javascript
{
  id: 2706681,
  natural_key: "DAL7LAXHND260105",
  operator_icao: "DAL",
  flight_number: "7",
  departure_airport: "KLAX",
  arrival_airport: "RJTT",
  departure_time: "2026-01-05 17:25:00",
  arrival_time: "2026-01-06 05:00:00",
  duration: 695,  // minutes
  aircraft_type: "A359",
  known_aircraft: ["A359"],
  notes: "SimVector Schedule Cloud || ..."
}
```

## Blade Template Usage

### Regular Flights
```blade
@section('content')
  <div id="regular-flights-app"></div>
@endsection

@section('scripts')
  <script src="{{ mix('/frontend/js/flightsearch.js', '/assets/simvector') }}" defer></script>
  <script>
    // Initialize regular flight search
    // TODO: Create separate entry point if needed
  </script>
@endsection
```

### Live Flights
```blade
@section('content')
  <div id="app" data-app='@json([
    "flights" => $flights->items(),
    "saved" => $saved ?? [],
    "user" => [...],
    "pagination" => [...],
    ...
  ])'></div>
@endsection

@section('scripts_head')
  <script src="{{ mix('/frontend/js/flightsearch.js', '/assets/simvector') }}" defer></script>
@endsection
```

## Key Design Decisions

### Why Two Separate Implementations?

1. **Different Data Sources**: Regular flights from phpVMS database vs. live flights from SimVector
2. **Different Feature Sets**: Regular flights have full functionality vs. live flights are bid-only
3. **Different UX**: Regular flights need detailed search vs. live flights need quick filtering
4. **Maintainability**: Easier to maintain separate, focused components

### Why Bootstrap 5 Without Scoped Styles?

- Components are embedded in Bootstrap 5 pages
- Reduces CSS duplication
- Ensures consistency with parent page
- Lighter bundle size

### Why Client-Side Sorting for Live Flights?

- Data already loaded from server
- Better UX (instant sorting)
- Reduces server round trips
- Live flights have less data (faster sorting)

### Why Blade Template Hydration?

- Initial page load performance
- SEO-friendly (server-rendered)
- Progressive enhancement
- Reduces API calls on first load

## Browser Support

- Modern browsers with ES6+ support
- Vue 3 compatible browsers
- Bootstrap 5 compatible browsers

## Performance Considerations

- Vue 3 Composition API (smaller bundle, better tree-shaking)
- Lazy loading for modal components (if implemented)
- Client-side sorting for live flights (no API calls)
- Pagination to limit rendered items
- Versioned assets (browser caching)

## Testing Checklist

### Regular Flights
- [ ] Search form submission
- [ ] All filter fields working
- [ ] Sorting by each column
- [ ] Pagination navigation
- [ ] Add bid without aircraft
- [ ] Add bid with aircraft (modal)
- [ ] Remove bid
- [ ] SimBrief links
- [ ] vmsACARS links
- [ ] PIREP creation link
- [ ] Responsive layout (mobile/desktop)

### Live Flights
- [ ] Search form submission
- [ ] All filter fields working
- [ ] Client-side sorting
- [ ] Pagination navigation
- [ ] Add bid
- [ ] Remove bid
- [ ] Date/time formatting
- [ ] Duration formatting
- [ ] Aircraft popover
- [ ] Responsive layout (mobile/desktop)
- [ ] Initial data hydration
- [ ] Empty state message

## Next Steps

1. **Build the components**: Run `npm run dev` to compile
2. **Create API endpoints**: Implement backend API routes
3. **Test in browser**: Load pages and verify functionality
4. **Implement API calls**: Replace TODO comments with actual fetch calls
5. **Add error handling**: Implement user-friendly error messages
6. **Add loading states**: Show spinners during API calls
7. **Test thoroughly**: Use the testing checklist above

