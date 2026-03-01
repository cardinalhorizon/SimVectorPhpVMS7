<script setup>
import FlightCard from './FlightCard.vue';

const props = defineProps({
  flights: {
    type: Array,
    default: () => []
  },
  saved: {
    type: Object,
    default: () => ({})
  },
  acaresPlugin: {
    type: Boolean,
    default: false
  },
  simbrief: {
    type: Boolean,
    default: false
  },
  simbriefBids: {
    type: Boolean,
    default: false
  },
  user: {
    type: Object,
    default: () => ({})
  },
  blockAircraft: {
    type: Boolean,
    default: false
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

const emit = defineEmits(['sort', 'add-bid', 'remove-bid', 'show-aircraft-modal']);

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
                <th @click="handleSort('airline_id')" style="cursor: pointer;">
                    Airline {{ getSortIcon('airline_id') }}
                </th>
                <th @click="handleSort('flight_number')" style="cursor: pointer;">
                    Flight Number {{ getSortIcon('flight_number') }}
                </th>
                <th @click="handleSort('dpt_airport_id')" style="cursor: pointer;">
                    Departure {{ getSortIcon('dpt_airport_id') }}
                </th>
                <th @click="handleSort('arr_airport_id')" style="cursor: pointer;">
                    Arrival {{ getSortIcon('arr_airport_id') }}
                </th>
                <th @click="handleSort('dpt_time')" style="cursor: pointer;">
                    STD {{ getSortIcon('dpt_time') }}
                </th>
                <th @click="handleSort('arr_time')" style="cursor: pointer;">
                    STA {{ getSortIcon('arr_time') }}
                </th>
                <th @click="handleSort('distance')" style="cursor: pointer;">
                    Distance {{ getSortIcon('distance') }}
                </th>
                <th @click="handleSort('flight_time')" style="cursor: pointer;">
                    Flight Time {{ getSortIcon('flight_time') }}
                </th>
            </tr>
            </tbody>

        </table>
      </div>
    </div>

    <FlightCard
      v-for="flight in flights"
      :key="flight.id"
      :flight="flight"
      :saved="saved"
      :acares-plugin="acaresPlugin"
      :simbrief="simbrief"
      :simbrief-bids="simbriefBids"
      :user="user"
      :block-aircraft="blockAircraft"
      :only-flights-from-current="onlyFlightsFromCurrent"
      @add-bid="(flightId) => emit('add-bid', flightId)"
      @remove-bid="(flightId) => emit('remove-bid', flightId)"
      @show-aircraft-modal="(flightId) => emit('show-aircraft-modal', flightId)"
    />
  </div>
</template>

