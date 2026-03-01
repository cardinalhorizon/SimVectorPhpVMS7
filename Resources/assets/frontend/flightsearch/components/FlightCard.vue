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
  }
});

const emit = defineEmits(['add-bid', 'remove-bid', 'show-aircraft-modal']);

const isSaved = computed(() => props.saved[props.flight.natural_key] !== undefined);

const getSubfleetsDisplay = computed(() => {
  if (!props.flight.subfleets || props.flight.subfleets.length === 0) {
    return { display: 'Any Subfleet', all: 'Any Subfleet' };
  }

  const types = props.flight.subfleets.map(sf => sf.type);
  const display = types.length > 2
    ? types.slice(0, 2).join(', ') + '...'
    : types.join(', ');
  const all = types.join(', ');

  return { display, all };
});

const minutesToTime = (minutes) => {
  // TODO: Implement minutes to time conversion
  const hours = Math.floor(minutes / 60);
  const mins = minutes % 60;
  return `${hours}h ${mins}m`;
};

const handleBidClick = () => {
  if (isSaved.value) {
    emit('remove-bid', props.flight.id);
  } else {
    if (props.blockAircraft) {
      emit('show-aircraft-modal', props.flight.id);
    } else {
      emit('add-bid', props.flight.id);
    }
  }
};

const canAddBid = computed(() => {
  if (props.onlyFlightsFromCurrent && props.user.current_airport) {
    return props.flight.dpt_airport_id === props.user.current_airport.icao;
  }
  return true;
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
              <img
                v-if="flight.airline?.logo"
                :src="flight.airline.logo"
                :alt="flight.airline.name"
                style="max-width: 80px; width: 100%; height: auto;"
              />
              <span v-else>{{ flight.airline?.name }}:</span>
              <span class="ms-1">
                <template v-if="flight.airline?.iata">
                  {{ flight.airline.icao }}{{ flight.flight_number }} |
                </template>
                {{ flight.ident }}
                <template v-if="flight.callsign">
                  | {{ flight.atc }}
                </template>
              </span>
            </div>
            <div>
              <span class="badge bg-secondary">
                {{ flight.flight_type }}&nbsp;
                <span class="d-none d-sm-inline">({{ flight.flight_type_label }})</span>
              </span>
            </div>
          </div>

          <div class="my-2 d-flex flex-row justify-content-between">
            <div class="d-flex flex-column text-start">
              <div class="fs-2" style="font-weight: 600">
                <a :href="`/airports/${flight.dpt_airport_id}`">
                  {{ flight.dpt_airport_id }}
                </a>
              </div>
              <div class="fs-5 d-none d-md-flex">{{ flight.dpt_airport?.name }}</div>
              <div class="fs-5">{{ flight.dpt_time }}</div>
            </div>
            <div class="d-flex flex-column text-end">
              <div class="fs-2" style="font-weight: 600">
                <a :href="`/airports/${flight.arr_airport_id}`">
                  {{ flight.arr_airport_id }}
                </a>
              </div>
              <div class="fs-5 d-none d-md-flex">{{ flight.arr_airport?.name }}</div>
              <div class="fs-5">{{ flight.arr_time }}</div>
            </div>
          </div>

          <div class="d-flex flex-row justify-content-between">
            <div class="text-center fs-5">
              <template v-if="flight.flight_time">
                {{ minutesToTime(flight.flight_time) }}
              </template>
              {{ flight.flight_time && flight.distance ? '/' : '' }}
              {{ flight.distance ? flight.distance + 'nm' : '' }}
            </div>
            <div class="fs-5">
              <span
                v-if="getSubfleetsDisplay.display !== getSubfleetsDisplay.all"
                data-bs-toggle="popover"
                data-bs-trigger="hover"
                data-bs-placement="bottom"
                :data-bs-content="getSubfleetsDisplay.all"
              >
                {{ getSubfleetsDisplay.display }}
              </span>
              <span v-else>
                {{ getSubfleetsDisplay.display }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card-footer">
      <a class="btn btn-sm btn-primary" :href="`/flights/${flight.id}`">
        View Flight
      </a>

      <template v-if="acaresPlugin">
        <a
          v-if="isSaved"
          :href="`vmsacars:bid/${saved[flight.id]}`"
          class="btn btn-sm btn-primary"
        >
          Load in vmsACARS
        </a>
        <a
          v-else
          :href="`vmsacars:flight/${flight.id}`"
          class="btn btn-sm btn-primary"
        >
          Load in vmsACARS
        </a>
      </template>

      <template v-if="simbrief">
        <a
          v-if="flight.simbrief && flight.simbrief.user_id === user.id"
          :href="`/simbrief/briefing/${flight.simbrief.id}`"
          class="btn btn-sm btn-primary"
        >
          View SimBrief
        </a>
        <template v-else>
          <a
            v-if="!simbriefBids || (simbriefBids && isSaved)"
            :href="`/simbrief/generate?flight_id=${flight.id}${saved[flight.id]?.aircraft_id ? '&aircraft_id=' + saved[flight.id].aircraft_id : ''}`"
            class="btn btn-sm btn-primary"
          >
            Create SimBrief
          </a>
        </template>
      </template>

      <a :href="`/pireps/create?flight_id=${flight.id}`" class="btn btn-sm btn-info">
        New PIREP
      </a>

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

