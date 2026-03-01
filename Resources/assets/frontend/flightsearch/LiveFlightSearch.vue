<script setup>
import { ref, computed, onMounted, inject } from 'vue';
import LiveFlightSearchForm from './components/LiveFlightSearchForm.vue';
import LiveFlightTable from './components/LiveFlightTable.vue';
import FlightPagination from './components/FlightPagination.vue';
import axios from "axios";

// Props - data will be hydrated from the Blade template
const props = defineProps({
    subfleets: {
        type: Object,
        default: () => ({})
    }
    ,
    aircraft: {
        type: Object,
        default: () => {}

    },
    airports: {
        type: Object,
        default: () => {}

    } ,
    airlines: {
        type: Object,
        default: () => {}

    },
    initialFlights: {
        type: Array,
        default: () => []
    },
    initialSaved: {
        type: Object,
        default: () => ({})
    },
    initialUser: {
        type: Object,
        default: () => ({})
    },
    initialPagination: {
        type: Object,
        default: () => ({
            current_page: 1,
            last_page: 1,
            total: 0,
            per_page: 15,
            from: 0,
            to: 0
        })
    },
    onlyFlightsFromCurrent: {
        type: Boolean,
        default: false
    },
    apiBaseUrl: {
        type: String,
        default: '/api'
    },
    apiKey: {
        type: String,
        default: ''
    }
});

// Inject the global axios instance (created in flightsearch.js)
const api = inject('api', null);

// State
const flights = ref([...props.initialFlights]);
const saved = ref({ ...props.initialSaved });
const user = ref({ ...props.initialUser });
const loading = ref(false);
const searchParams = ref({});
const firstLoad = ref(true);

// Pagination
const currentPage = ref(props.initialPagination.current_page);
const lastPage = ref(props.initialPagination.last_page);
const total = ref(props.initialPagination.total);
const perPage = ref(props.initialPagination.per_page);
const from = ref(props.initialPagination.from);
const to = ref(props.initialPagination.to);

// Sorting
const sortColumn = ref(null);
const sortDirection = ref('asc');

// Computed sorted flights
const sortedFlights = computed(() => {
    if (!sortColumn.value) {
        return flights.value;
    }

    return [...flights.value].sort((a, b) => {
        let aVal = a[sortColumn.value];
        let bVal = b[sortColumn.value];

        // Handle different data types
        if (typeof aVal === 'string') {
            aVal = aVal.toLowerCase();
            bVal = bVal.toLowerCase();
        }

        if (aVal < bVal) return sortDirection.value === 'asc' ? -1 : 1;
        if (aVal > bVal) return sortDirection.value === 'asc' ? 1 : -1;
        return 0;
    });
});

const loadFlights = async (page = 1) => {
    loading.value = true;
    firstLoad.value = false;
    try {
        const params = {
            ...searchParams.value,
            page,
            per_page: perPage.value,
            ...(sortColumn.value && {
                sort_by: sortColumn.value,
                sorted_by: sortDirection.value
            })
        }
        const resp = await api.get('/simvector/flights', { params });
        const result = resp.data;

        // Extract flights array (handle both paginated and simple array responses)
        flights.value = result.data || result;

        // Update pagination if present
        if (result.current_page) {
            currentPage.value = result.current_page;
            lastPage.value = result.last_page;
            total.value = result.total;
            perPage.value = result.per_page;
            from.value = result.from || 0;
            to.value = result.to || 0;
        }
    } catch (error) {
        console.error('Error loading flights:', error);
        flights.value = [];
    } finally {
        loading.value = false;
    }
};

const handleSearch = (searchData) => {
    searchParams.value = { ...searchData };
    console.log(searchData);
    currentPage.value = 1;
    loadFlights(1);
};

const handleReset = () => {
    searchParams.value = {};
    currentPage.value = 1;
    sortColumn.value = null;
    sortDirection.value = 'asc';
    loadFlights(1);
};

const handleSort = (column) => {
    if (sortColumn.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn.value = column;
        sortDirection.value = 'asc';
    }
    // reload using new sort
    loadFlights(currentPage.value);
};

const handlePageChange = (page) => {
    currentPage.value = page;
    loadFlights(page);
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const handleAddBid = async (flightId) => {
    try {
        // get the data for the flight from the array we have
        let flightData = flights.value.find(flight => flight.natural_key === flightId);
        if (!flightData) {
            throw new Error('Flight data not found for flight ID: ' + flightId);

        }

        await axios.post(`${props.apiBaseUrl}/simvector/bids`, { flight_id: flightId }, {
            headers: {
                'X-API-KEY': props.apiKey
            }
        });

        // Append to local saved bids
        console.log('Adding bid for flight:', flightId);
        saved.value['SV_'+flightId] = flightData; // Update with actual flight data
    } catch (error) {
        console.error('Error adding bid:', error);
    }
};

const handleRemoveBid = async (flightId) => {
    try {
        await axios.post(`${props.apiBaseUrl}/simvector/bids`, {
            _method: 'DELETE',
            flight_id: flightId
        }, {
            headers: {
                'X-API-KEY': props.apiKey
            }
        });

        // For now, just update local state
        console.log('Removing bid for flight:', flightId);
        delete saved.value[flightId]; // Update after API call
    } catch (error) {
        console.error('Error removing bid:', error);
        alert('Error removing bid. Please try again.');
    }
};

// Load initial flights on mount
onMounted(() => {
    // Disable the initial loading for now.
    // TODO: Consider populating user's current airport as the departure airport

    //loadFlights(currentPage.value);
});
</script>

<template>
    <div class="row">
        <!-- Main content area -->
        <div class="col-xl-9 col-md-12">
            <h2>Live Flights</h2>

            <!-- Mobile search accordion -->
            <div class="d-xl-none mb-3">
                <div class="accordion" id="searchAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button
                                class="accordion-button collapsed"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#collapseOne"
                                aria-expanded="false"
                                aria-controls="collapseOne"
                            >
                                Search
                            </button>
                        </h2>
                        <div
                            id="collapseOne"
                            class="accordion-collapse collapse"
                            aria-labelledby="headingOne"
                            data-bs-parent="#searchAccordion"
                        >
                            <div class="accordion-body">
                                <LiveFlightSearchForm @search="handleSearch" @reset="handleReset" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading indicator -->
            <div v-if="loading" class="text-center my-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>

            <!-- Flight table -->
            <LiveFlightTable
                v-else
                :flights="sortedFlights"
                :saved="saved"
                :user="user"
                :only-flights-from-current="onlyFlightsFromCurrent"
                :sort-column="sortColumn"
                :sort-direction="sortDirection"
                @sort="handleSort"
                @add-bid="handleAddBid"
                @remove-bid="handleRemoveBid"
            />

            <!-- No results message -->
            <div v-if="!loading && sortedFlights.length === 0 && !firstLoad" class="alert alert-info">
                No flights found matching your search criteria.
            </div>
            <div v-if="firstLoad && sortedFlights.length === 0" class="alert alert-info">
                Use the Search Form to Find Flights
            </div>
        </div>

        <!-- Desktop search sidebar -->
        <div class="col-xl-3 d-none d-xl-block">
            <div class="card">
                <div class="card-header bg-primary text-white">Search</div>
                <div class="card-body">
                    <LiveFlightSearchForm @search="handleSearch" @reset="handleReset" />
                </div>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <FlightPagination
        v-if="lastPage > 1"
        :current-page="currentPage"
        :last-page="lastPage"
        :total="total"
        :per-page="perPage"
        :from="from"
        :to="to"
        @page-change="handlePageChange"
    />
</template>

