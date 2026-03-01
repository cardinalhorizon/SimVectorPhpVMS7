<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  flightId: {
    type: Number,
    required: true
  },
  show: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['close', 'select-aircraft', 'select-without-aircraft']);

const selectedAircraft = ref(null);
const aircrafts = ref([]);
const loading = ref(false);

watch(() => props.show, (newVal) => {
  if (newVal && props.flightId) {
    loadAircrafts();
  }
});

const loadAircrafts = async () => {
  loading.value = true;
  // TODO: Implement API call to fetch available aircraft for this flight
  // fetch(`/api/flights/${props.flightId}/aircraft`)
  loading.value = false;
};

const handleSelectWithAircraft = () => {
  if (selectedAircraft.value) {
    emit('select-aircraft', props.flightId, selectedAircraft.value);
  }
};

const handleSelectWithoutAircraft = () => {
  emit('select-without-aircraft', props.flightId);
};

const handleClose = () => {
  selectedAircraft.value = null;
  emit('close');
};
</script>

<template>
  <div
    class="modal fade"
    :class="{ show: show, 'd-block': show }"
    id="bidModal"
    tabindex="-1"
    aria-labelledby="bidModalLabel"
    :aria-hidden="!show"
    style="background-color: rgba(0, 0, 0, 0.5);"
    v-if="show"
  >
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="bidModalLabel">Select Aircraft</h5>
          <button type="button" class="btn-close" @click="handleClose" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="aircraft_select" class="form-label">Aircraft</label>
            <select
              v-model="selectedAircraft"
              id="aircraft_select"
              class="form-select"
              :disabled="loading"
            >
              <option :value="null" disabled>
                {{ loading ? 'Loading Aircrafts...' : 'Select an aircraft' }}
              </option>
              <option v-for="aircraft in aircrafts" :key="aircraft.id" :value="aircraft.id">
                {{ aircraft.text }}
              </option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="handleClose">
            Close
          </button>
          <button
            type="button"
            class="btn btn-primary"
            @click="handleSelectWithAircraft"
            :disabled="!selectedAircraft || loading"
          >
            Add Bid with Aircraft
          </button>
          <button
            type="button"
            class="btn btn-info"
            @click="handleSelectWithoutAircraft"
            :disabled="loading"
          >
            Add Bid without Aircraft
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

