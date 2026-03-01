import { createApp } from 'vue';
import LiveFlightSearch from './LiveFlightSearch.vue';
import { createApi } from './api';

// Get the data hydrated from the Blade template
const appElement = document.getElementById('app');
const appData = appElement ? JSON.parse(appElement.dataset.app || '{}') : {};

console.log(appData);
// Create API instance
const api = createApi({ baseUrl: appData.apiBaseUrl || '/api', apiKey: appData.apiKey || '' });

// Create and mount the Vue app
const app = createApp(LiveFlightSearch, {
    subfleets: appData.subfleets || [],
    aircraft: appData.aircraft || [],
    airports: appData.airports || [],
    airlines: appData.airlines || [],
    initialSaved: appData.initialSaved || {},
    initialPagination: appData.pagination || {
        current_page: 1,
        last_page: 1,
        total: 0,
        per_page: 15,
        from: 0,
        to: 0
    },
    onlyFlightsFromCurrent: appData.onlyFlightsFromCurrent || false,
    apiBaseUrl: appData.apiBaseUrl || '/api',
    apiKey: appData.apiKey || ''
});

// Provide the API instance globally
app.config.globalProperties.$api = api;
app.provide('api', api);

app.mount('#app');

