<script setup>
import { ref } from 'vue';
import AirportAutocomplete from './AirportAutocomplete.vue';

const emit = defineEmits(['search', 'reset']);

// Search form state
const formData = ref({
    operator_icao: '',
    flight_number: '',
    departure_airport: '',
    arrival_airport: '',
    aircraft_type: ''
});

const handleSearch = () => {
    emit('search', formData.value);
};

const handleReset = () => {
    formData.value = {
        operator_icao: '',
        flight_number: '',
        departure_airport: '',
        arrival_airport: '',
        aircraft_type: ''
    };
    emit('reset');
};
</script>

<template>
    <div class="row">
        <div class="col-12">
            <div class="form-group search-form">
                <form @submit.prevent="handleSearch">
                    <!--
                    <div class="mb-3">
                        <label for="operator_icao" class="form-label">Airline Code</label>
                        <input
                            v-model="formData.operator_icao"
                            type="text"
                            name="operator_icao"
                            id="operator_icao"
                            class="form-control"
                            placeholder="e.g., DAL, UAL"
                        />
                    </div>

                    <div class="mb-3">
                        <label for="flight_number" class="form-label">Flight Number</label>
                        <input
                            v-model="formData.flight_number"
                            type="text"
                            name="flight_number"
                            id="flight_number"
                            class="form-control"
                            placeholder="e.g., 7, 123"
                        />
                    </div>
                    -->
                    <div class="mb-3">
                        <label for="departure_airport" class="form-label">Departure Airport</label>
                        <AirportAutocomplete
                            v-model="formData.departure_airport"
                            id="departure_airport"
                            name="departure_airport"
                            placeholder="e.g., KLAX"
                        />
                    </div>

                    <div class="mb-3">
                        <label for="arrival_airport" class="form-label">Arrival Airport</label>
                        <AirportAutocomplete
                            v-model="formData.arrival_airport"
                            id="arrival_airport"
                            name="arrival_airport"
                            placeholder="e.g., RJTT"
                        />
                    </div>
                    <!--
                    <div class="mb-3">
                        <label for="aircraft_type" class="form-label">Aircraft Type</label>
                        <input
                            v-model="formData.aircraft_type"
                            type="text"
                            name="aircraft_type"
                            id="aircraft_type"
                            class="form-control"
                            placeholder="e.g., A359, B77W"
                        />
                    </div>
                    -->
                    <div class="d-flex justify-content-between mt-3">
                        <button type="submit" class="btn btn-primary">Find Flights</button>
                        <button type="button" class="btn btn-secondary" @click="handleReset">Reset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
