import { createApp } from 'vue';
import { createPinia } from 'pinia';
import LiveFlightSearch from './LiveFlightSearch.vue';
import { createApi } from './api';

// Data hydrated from the Blade template
const el = document.getElementById('app');
const appData = el ? JSON.parse(el.dataset.app || '{}') : {};

const api = createApi({ baseUrl: appData.apiBaseUrl || '/api', apiKey: appData.apiKey || '' });

const app = createApp(LiveFlightSearch, {
    initialSaved: appData.initialSaved || {},
    user: appData.user || {},
    onlyFlightsFromCurrent: appData.onlyFlightsFromCurrent || false,
    bidsUrl: appData.bidsUrl || null,
});

app.use(createPinia());
app.provide('api', api);
app.mount('#app');
