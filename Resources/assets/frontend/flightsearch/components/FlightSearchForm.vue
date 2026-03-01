<script setup>
import { ref, onMounted } from 'vue';

const emit = defineEmits(['search', 'reset']);

// Search form state
const formData = ref({
  airline_id: '',
  flight_type: '',
  flight_number: '',
  route_code: '',
  dep_icao: '',
  arr_icao: '',
  subfleet_id: '',
  type_rating_id: '',
  icao_type: ''
});

// Dropdown options
const airlines = ref([]);
const flightTypes = ref([]);
const subfleets = ref([]);
const typeRatings = ref([]);
const icaoCodes = ref([]);

onMounted(() => {
  // TODO: Implement API call to fetch airlines
  // TODO: Implement API call to fetch flight types
  // TODO: Implement API call to fetch subfleets
  // TODO: Implement API call to fetch type ratings
  // TODO: Implement API call to fetch ICAO codes
});

const handleSearch = () => {
  emit('search', formData.value);
};

const handleReset = () => {
  formData.value = {
    airline_id: '',
    flight_type: '',
    flight_number: '',
    route_code: '',
    dep_icao: '',
    arr_icao: '',
    subfleet_id: '',
    type_rating_id: '',
    icao_type: ''
  };
  emit('reset');
};
</script>

<template>
  <div class="row">
    <div class="col-12">
      <div class="form-group search-form">
        <form @submit.prevent="handleSearch">
          <div>
            <div class="mb-3">
              <label for="airline_id" class="form-label">Airline</label>
              <select
                v-model="formData.airline_id"
                name="airline_id"
                id="airline_id"
                class="form-select"
              >
                <option value="">Select an airline</option>
                <option v-for="airline in airlines" :key="airline.id" :value="airline.id">
                  {{ airline.name }}
                </option>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label for="flight_type" class="form-label">Flight Type</label>
            <select
              v-model="formData.flight_type"
              name="flight_type"
              id="flight_type"
              class="form-select"
            >
              <option value="">Select a flight type</option>
              <option v-for="type in flightTypes" :key="type.id" :value="type.id">
                {{ type.name }}
              </option>
            </select>
          </div>

          <div class="mb-3">
            <label for="flight_number" class="form-label">Flight Number</label>
            <input
              v-model="formData.flight_number"
              type="text"
              name="flight_number"
              id="flight_number"
              class="form-control"
            />
          </div>

          <div class="mb-3">
            <label for="route_code" class="form-label">Route Code</label>
            <input
              v-model="formData.route_code"
              type="text"
              name="route_code"
              id="route_code"
              class="form-control"
            />
          </div>

          <div class="mb-3">
            <label for="dep_icao" class="form-label">Departure Airport</label>
            <select
              v-model="formData.dep_icao"
              name="dep_icao"
              id="dep_icao"
              class="form-select airport_search"
            >
              <option value="">Type To Begin Search</option>
              <!-- TODO: Implement airport search -->
            </select>
          </div>

          <div class="mb-3">
            <label for="arr_icao" class="form-label">Arrival Airport</label>
            <select
              v-model="formData.arr_icao"
              name="arr_icao"
              id="arr_icao"
              class="form-select airport_search"
            >
              <option value="">Type To Begin Search</option>
              <!-- TODO: Implement airport search -->
            </select>
          </div>

          <div class="mb-3">
            <label for="subfleet_id" class="form-label">Subfleet</label>
            <select
              v-model="formData.subfleet_id"
              name="subfleet_id"
              id="subfleet_id"
              class="form-select select2"
            >
              <option value="">Select a subfleet</option>
              <option v-for="subfleet in subfleets" :key="subfleet.id" :value="subfleet.id">
                {{ subfleet.name }}
              </option>
            </select>
          </div>

          <div v-if="typeRatings.length > 0" class="mb-3">
            <label for="type_rating_id" class="form-label">Type Rating</label>
            <select
              v-model="formData.type_rating_id"
              name="type_rating_id"
              id="type_rating_id"
              class="form-select select2"
            >
              <option value="">Select a type rating</option>
              <option v-for="rating in typeRatings" :key="rating.id" :value="rating.id">
                {{ rating.type }} | {{ rating.name }}
              </option>
            </select>
          </div>

          <div v-if="icaoCodes.length > 0" class="mb-3">
            <label for="icao_type" class="form-label">ICAO Type</label>
            <select
              v-model="formData.icao_type"
              name="icao_type"
              id="icao_type"
              class="form-select select2"
            >
              <option value="">Select an ICAO type</option>
              <option v-for="code in icaoCodes" :key="code" :value="code">
                {{ code }}
              </option>
            </select>
          </div>

          <div class="d-flex justify-content-between mt-3">
            <button type="submit" class="btn btn-primary">Find Flights</button>
            <button type="button" class="btn btn-secondary" @click="handleReset">Reset</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

