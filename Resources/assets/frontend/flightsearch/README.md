# Flight Search Vue Component

A modular Vue 3 component system for flight searching and browsing in phpVMS 7, built with Bootstrap 5.

## Component Structure

### Main Component
- **FlightSearch.vue** - Main container component that orchestrates all child components

### Child Components
- **FlightSearchForm.vue** - Search form with filters (airline, flight type, airports, etc.)
- **FlightTable.vue** - Displays sortable flight table headers and flight cards
- **FlightCard.vue** - Individual flight card with details and action buttons
- **FlightPagination.vue** - Bootstrap 5 pagination component
- **BidModal.vue** - Modal for selecting aircraft when adding bids

## Features

### Flight Search Form
- Airline selection
- Flight type filter
- Flight number search
- Route code search
- Departure/Arrival airport selection (with airport_search class for future integration)
- Subfleet filter
- Type rating filter (conditional display)
- ICAO type filter (conditional display)
- Reset functionality

### Flight Display
- Sortable columns (airline, flight number, departure, arrival, STD, STA, distance, flight time)
- Card-based flight display with:
  - Airline logo or name
  - Flight identifier and callsign
  - Flight type badge
  - Departure/Arrival airports with names
  - Flight time and distance
  - Subfleet information with popover for multiple subfleets
- Responsive design (mobile accordion, desktop sidebar)

### Flight Actions
- View Flight details
- Load in vmsACARS (if plugin enabled)
- View/Create SimBrief briefing
- Create new PIREP
- Add/Remove bid
- Aircraft selection modal (when block_aircraft setting is enabled)

### Pagination
- Bootstrap 5 styled pagination
- Smart page number display with ellipsis
- Previous/Next navigation
- Active page highlighting

## Bootstrap 5 Classes Used

The component uses Bootstrap 5 classes exclusively for styling:

- **Layout**: `row`, `col-*`, `d-flex`, `justify-content-*`, `text-*`, `mb-*`, `mt-*`, `my-*`, `ms-*`
- **Cards**: `card`, `card-body`, `card-header`, `card-footer`
- **Forms**: `form-label`, `form-select`, `form-control`, `form-group`
- **Buttons**: `btn`, `btn-primary`, `btn-secondary`, `btn-success`, `btn-danger`, `btn-info`, `btn-sm`
- **Typography**: `fs-2`, `fs-5`, `badge`, `bg-primary`, `bg-secondary`, `text-white`
- **Accordion**: `accordion`, `accordion-item`, `accordion-header`, `accordion-button`, `accordion-body`, `accordion-collapse`
- **Modal**: `modal`, `modal-dialog`, `modal-content`, `modal-header`, `modal-body`, `modal-footer`, `modal-title`, `btn-close`
- **Pagination**: `pagination`, `page-item`, `page-link`
- **Responsive**: `d-none`, `d-xl-block`, `d-xl-none`, `d-md-flex`, `d-sm-inline`
- **Tables**: `table`, `table-sm`, `table-borderless`, `align-middle`, `text-nowrap`

## TODO Items

The following API integrations need to be implemented:

### FlightSearch.vue
- [ ] Load flights from API with pagination
- [ ] Load user data and saved bids
- [ ] Load configuration settings
- [ ] Add bid API call
- [ ] Remove bid API call
- [ ] Implement search with parameters
- [ ] Implement sorting

### FlightSearchForm.vue
- [ ] Fetch airlines from API
- [ ] Fetch flight types from API
- [ ] Fetch subfleets from API
- [ ] Fetch type ratings from API
- [ ] Fetch ICAO codes from API
- [ ] Implement airport search functionality

### FlightCard.vue
- [ ] Implement minutesToTime conversion helper
- [ ] Verify URL routing matches phpVMS routes

### BidModal.vue
- [ ] Fetch available aircraft for selected flight
- [ ] Initialize TomSelect or similar for aircraft dropdown

## Props

### FlightSearch.vue
- `apiBaseUrl` (String, default: '/api') - Base URL for API calls
- `apiKey` (String, default: '') - API key for authentication

## Events

### FlightSearchForm.vue
- `@search` - Emitted when search is submitted, passes form data
- `@reset` - Emitted when reset is clicked

### FlightTable.vue
- `@sort` - Emitted when column header is clicked, passes column name
- `@add-bid` - Emitted when add bid is clicked, passes flight ID
- `@remove-bid` - Emitted when remove bid is clicked, passes flight ID
- `@show-aircraft-modal` - Emitted when aircraft modal should show, passes flight ID

### FlightPagination.vue
- `@page-change` - Emitted when page is changed, passes page number

### BidModal.vue
- `@close` - Emitted when modal is closed
- `@select-aircraft` - Emitted when aircraft is selected, passes (flightId, aircraftId)
- `@select-without-aircraft` - Emitted when bid without aircraft is selected, passes flightId

## Usage

```vue
<template>
  <FlightSearch 
    :api-base-url="'/api'"
    :api-key="userApiKey"
  />
</template>

<script setup>
import FlightSearch from './flightsearch/FlightSearch.vue';

const userApiKey = 'your-api-key';
</script>
```

## Notes

- All components use Bootstrap 5 classes from the parent page context
- No scoped styles are included to leverage existing Bootstrap 5 styling
- Components match the Blade template structure 1:1
- The component is designed to be embedded in an existing Bootstrap 5 page
- Airport search fields include the `airport_search` class for integration with existing airport search scripts

