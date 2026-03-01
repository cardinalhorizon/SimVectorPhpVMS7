<script setup>
import { computed } from 'vue';

const props = defineProps({
  flight: {
    type: Object,
    required: true
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
  }
});

const emit = defineEmits(['add-bid', 'remove-bid']);

const isSaved = computed(() => props.saved['SV_'+props.flight.natural_key] !== undefined);

const formatDuration = (minutes) => {
  const hours = Math.floor(minutes / 60);
  const mins = minutes % 60;
  return `${hours}h ${mins}m`;
};

const formatDateTime = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: false
  });
};

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric'
  });
};

const handleBidClick = () => {
  if (isSaved.value) {
    emit('remove-bid', props.flight.natural_key);
  } else {
    emit('add-bid', props.flight.natural_key);
  }
};

const canAddBid = computed(() => {
  if (props.onlyFlightsFromCurrent && props.user.current_airport) {
    return props.flight.departure_airport === props.user.current_airport.icao;
  }
  return true;
});

const getAircraftDisplay = computed(() => {
  if (!props.flight.known_aircraft || props.flight.known_aircraft.length === 0) {
    return props.flight.aircraft_type || 'Any Aircraft';
  }

  const types = props.flight.known_aircraft;
  const display = types.length > 2
    ? types.slice(0, 2).join(', ') + '...'
    : types.join(', ');
  const all = types.join(', ');

  return { display, all };
});
</script>

<template>
  <div class="card mb-3">
    <div class="card-body">
      <div class="row">
        <div class="col-sm-12">
          <div class="flex-row justify-content-between d-flex">
            <div class="d-flex flex-row center-align"
              style="font-size: 1.4rem; line-height: 1.4rem; font-weight: 600; text-align: center">
              <span>{{ flight.operator_icao }}:</span>
              <span class="ms-1">
                {{ flight.operator_icao }}{{ flight.flight_number }}
              </span>
            </div>
            <div>
              <span class="badge bg-secondary">Live Flight</span>
            </div>
          </div>

          <div class="my-2 d-flex flex-row justify-content-between">
            <div class="d-flex flex-column text-start">
              <div class="fs-2" style="font-weight: 600">
                {{ flight.departure_airport }}
              </div>
              <div class="fs-5">
                {{ formatDateTime(flight.departure_time) }}
              </div>
              <div class="fs-6 text-muted">
                {{ formatDate(flight.departure_time) }}
              </div>
            </div>
            <div class="d-flex flex-column text-end">
              <div class="fs-2" style="font-weight: 600">
                {{ flight.arrival_airport }}
              </div>
              <div class="fs-5">
                {{ formatDateTime(flight.arrival_time) }}
              </div>
              <div class="fs-6 text-muted">
                {{ formatDate(flight.arrival_time) }}
              </div>
            </div>
          </div>

          <div class="d-flex flex-row justify-content-between">
            <div class="text-center fs-5">
              {{ formatDuration(flight.duration) }}
            </div>
            <div class="fs-5">
              <span
                v-if="typeof getAircraftDisplay === 'object'"
                data-bs-toggle="popover"
                data-bs-trigger="hover"
                data-bs-placement="bottom"
                :data-bs-content="getAircraftDisplay.all"
              >
                {{ getAircraftDisplay.display }}
              </span>
              <span v-else>
                {{ getAircraftDisplay }}
              </span>
            </div>
          </div>

          <div v-if="flight.notes" class="mt-2">
            <small class="text-muted">{{ flight.notes }}</small>
          </div>
        </div>
      </div>
    </div>

    <div class="card-footer">
      <button
        v-if="canAddBid"
        class="btn btn-sm save_flight"
        :class="isSaved ? 'btn-danger' : 'btn-success'"
        type="button"
        title="Add/Remove Bid"
        @click="handleBidClick"
      >
        {{ isSaved ? 'Remove Bid' : 'Add Bid' }}
      </button>
    </div>
  </div>
</template>

