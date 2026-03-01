<script setup>
import LiveFlightCard from './LiveFlightCard.vue';

const props = defineProps({
  flights: {
    type: Array,
    default: () => []
  },
  saved: {
    type: Object,
    default: () => ({})
  },
  user: {
    type: Object,
    default: () => ({})
  },
  onlyFlightsFromCurrent: {
    type: Boolean,
    default: false
  },
  sortColumn: {
    type: String,
    default: null
  },
  sortDirection: {
    type: String,
    default: 'asc'
  }
});

const emit = defineEmits(['sort', 'add-bid', 'remove-bid']);

const handleSort = (column) => {
  emit('sort', column);
};

const getSortIcon = (column) => {
  if (props.sortColumn !== column) return '';
  return props.sortDirection === 'asc' ? '▲' : '▼';
};
</script>

<template>
  <div>
    <div class="row">
      <div class="col">
        <table class="table table-sm table-borderless align-middle text-nowrap mb-2">
          <tbody>
            <tr>
            <th @click="handleSort('operator_icao')" style="cursor: pointer;">
              Airline {{ getSortIcon('operator_icao') }}
            </th>
            <th @click="handleSort('flight_number')" style="cursor: pointer;">
              Flight Number {{ getSortIcon('flight_number') }}
            </th>
            <th @click="handleSort('departure_airport')" style="cursor: pointer;">
              Departure {{ getSortIcon('departure_airport') }}
            </th>
            <th @click="handleSort('arrival_airport')" style="cursor: pointer;">
              Arrival {{ getSortIcon('arrival_airport') }}
            </th>
            <th @click="handleSort('departure_time')" style="cursor: pointer;">
              STD {{ getSortIcon('departure_time') }}
            </th>
            <th @click="handleSort('arrival_time')" style="cursor: pointer;">
              STA {{ getSortIcon('arrival_time') }}
            </th>
            <th @click="handleSort('duration')" style="cursor: pointer;">
              Duration {{ getSortIcon('duration') }}
            </th>
            <th @click="handleSort('aircraft_type')" style="cursor: pointer;">
              Aircraft {{ getSortIcon('aircraft_type') }}
            </th>
          </tr>
          </tbody>
        </table>
      </div>
    </div>
    <LiveFlightCard
      v-for="flight in flights"
      :key="flight.natural_key"
      :flight="flight"
      :saved="saved"
      :user="user"
      :only-flights-from-current="onlyFlightsFromCurrent"
      @add-bid="(flightId) => emit('add-bid', flightId)"
      @remove-bid="(flightId) => emit('remove-bid', flightId)"
    />
  </div>
</template>

