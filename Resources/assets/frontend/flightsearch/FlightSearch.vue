<script setup>
import { ref, onMounted } from 'vue';
import FlightSearchForm from './components/FlightSearchForm.vue';
import FlightTable from './components/FlightTable.vue';
import FlightPagination from './components/FlightPagination.vue';
import BidModal from './components/BidModal.vue';

// Props for configuration
const props = defineProps({
  apiBaseUrl: {
    type: String,
    default: '/api'
  },
  apiKey: {
    type: String,
    default: ''
  }
});

// State
const flights = ref([]);
const saved = ref({});
const user = ref({});
const loading = ref(false);

// Pagination
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);
const perPage = ref(15);
const from = ref(0);
const to = ref(0);

// Sorting
const sortColumn = ref(null);
const sortDirection = ref('asc');

// Configuration flags
const acaresPlugin = ref(false);
const simbrief = ref(false);
const simbriefBids = ref(false);
const blockAircraft = ref(false);
const onlyFlightsFromCurrent = ref(false);

// Modal state
const showBidModal = ref(false);
const selectedFlightId = ref(null);

onMounted(() => {
  loadFlights();
  loadUserData();
  loadConfiguration();
});

const loadFlights = async (searchParams = {}) => {
  loading.value = true;
  console.log(searchParams);
  // TODO: Implement API call to fetch flights
  // const response = await fetch(`${props.apiBaseUrl}/flights?page=${currentPage.value}`, {
  //   headers: {
  //     'X-API-KEY': props.apiKey
  //   }
  // });
  // const data = await response.json();
  // flights.value = data.data;
  // currentPage.value = data.current_page;
  // lastPage.value = data.last_page;
  // total.value = data.total;
  // perPage.value = data.per_page;
  // from.value = data.from;
  // to.value = data.to;
  loading.value = false;
};

const loadUserData = async () => {
  // TODO: Implement API call to fetch user data and saved bids
  // const response = await fetch(`${props.apiBaseUrl}/user`, {
  //   headers: {
  //     'X-API-KEY': props.apiKey
  //   }
  // });
  // const data = await response.json();
  // user.value = data;
};

const loadConfiguration = async () => {
  // TODO: Implement API call to fetch configuration settings
  // blockAircraft.value = setting('bids.block_aircraft', false);
  // onlyFlightsFromCurrent.value = setting('pilots.only_flights_from_current', false);
  // etc.
};

const handleSearch = (searchData) => {
  currentPage.value = 1;
  loadFlights(searchData);
};

const handleReset = () => {
  currentPage.value = 1;
  sortColumn.value = null;
  sortDirection.value = 'asc';
  loadFlights();
};

const handleSort = (column) => {
  if (sortColumn.value === column) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortColumn.value = column;
    sortDirection.value = 'asc';
  }
  loadFlights();
};

const handlePageChange = (page) => {
  currentPage.value = page;
  loadFlights();
};

const handleAddBid = async (flightId, aircraftId = null) => {
  // TODO: Implement API call to add bid
  // await fetch(`${props.apiBaseUrl}/bids`, {
  //   method: 'POST',
  //   headers: {
  //     'X-API-KEY': props.apiKey,
  //     'Content-Type': 'application/json'
  //   },
  //   body: JSON.stringify({ flight_id: flightId, aircraft_id: aircraftId })
  // });
  loadFlights();
  loadUserData();
};

const handleRemoveBid = async (flightId) => {
  // TODO: Implement API call to remove bid
  // await fetch(`${props.apiBaseUrl}/bids/${saved.value[flightId]}`, {
  //   method: 'DELETE',
  //   headers: {
  //     'X-API-KEY': props.apiKey
  //   }
  // });
  loadFlights();
  loadUserData();
};

const handleShowAircraftModal = (flightId) => {
  selectedFlightId.value = flightId;
  showBidModal.value = true;
};

const handleCloseModal = () => {
  showBidModal.value = false;
  selectedFlightId.value = null;
};

const handleSelectAircraft = (flightId, aircraftId) => {
  handleAddBid(flightId, aircraftId);
  handleCloseModal();
};

const handleSelectWithoutAircraft = (flightId) => {
  handleAddBid(flightId);
  handleCloseModal();
};
</script>

<template>
  <div class="row">
    <!-- Flash messages placeholder -->
    <div class="col-12">
      <!-- TODO: Implement flash messages -->
    </div>

    <!-- Main content area -->
    <div class="col-xl-9 col-md-12">
      <h2>Flights</h2>

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
                aria-controls="flush-collapseOne"
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
                <FlightSearchForm @search="handleSearch" @reset="handleReset" />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Flight table -->
      <FlightTable
        :flights="flights"
        :saved="saved"
        :acares-plugin="acaresPlugin"
        :simbrief="simbrief"
        :simbrief-bids="simbriefBids"
        :user="user"
        :block-aircraft="blockAircraft"
        :only-flights-from-current="onlyFlightsFromCurrent"
        :sort-column="sortColumn"
        :sort-direction="sortDirection"
        @sort="handleSort"
        @add-bid="handleAddBid"
        @remove-bid="handleRemoveBid"
        @show-aircraft-modal="handleShowAircraftModal"
      />
    </div>

    <!-- Desktop search sidebar -->
    <div class="col-xl-3 d-none d-xl-block">
      <div class="card">
        <div class="card-header bg-primary text-white">Search</div>
        <div class="card-body">
          <FlightSearchForm @search="handleSearch" @reset="handleReset" />
        </div>
      </div>
    </div>
  </div>

  <!-- Pagination -->
  <FlightPagination
    :current-page="currentPage"
    :last-page="lastPage"
    :total="total"
    :per-page="perPage"
    :from="from"
    :to="to"
    @page-change="handlePageChange"
  />

  <!-- Bid Modal -->
  <BidModal
    :flight-id="selectedFlightId"
    :show="showBidModal"
    @close="handleCloseModal"
    @select-aircraft="handleSelectAircraft"
    @select-without-aircraft="handleSelectWithoutAircraft"
  />
</template>


