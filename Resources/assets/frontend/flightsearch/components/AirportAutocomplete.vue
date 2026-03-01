<script setup>
import { ref, onMounted, onBeforeUnmount, watch, inject, nextTick } from 'vue';

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: 'Search for an airport'
  },
  hubsOnly: {
    type: Boolean,
    default: false
  },
  name: {
    type: String,
    default: ''
  },
  id: {
    type: String,
    default: ''
  },
  // maximum suggestions to show
  limit: {
    type: Number,
    default: 10
  }
});

const emit = defineEmits(['update:modelValue']);

const api = inject('api', null);

const inputEl = ref(null);
const inputText = ref('');
const suggestions = ref([]);
const loading = ref(false);
const showDropdown = ref(false);
const highlighted = ref(-1);
let searchTimer = null;
let outsideClickHandler = null;

function buildSearchParams(query) {
  return {
    search: query,
    hubs: props.hubsOnly ? 1 : 0,
    page: 1,
    orderBy: 'id',
    sortedBy: 'asc',
    limit: props.limit
  };
}

async function fetchSuggestions(query) {
  loading.value = true;
  try {
    const params = buildSearchParams(query);
    let data = null;
    if (api && typeof api.get === 'function') {
      const resp = await api.get('/airports/search', { params });
      data = resp.data && resp.data.data ? resp.data.data : resp.data;
    } else {
      const base = window.location.origin + '/api';
      const url = new URL(base + '/airports/search');
      Object.keys(params).forEach(k => url.searchParams.append(k, params[k]));
      const resp = await fetch(url);
      const json = await resp.json();
      data = json.data || json;
    }

    // Normalize suggestions to { id, description }
    suggestions.value = Array.isArray(data) ? data.slice(0, props.limit).map(item => {
      if (typeof item === 'string') {
        return { id: item, description: item };
      }
      // common shapes: { id, description } or { icao, name }
      const id = item.id ?? item.icao ?? item.code ?? item._id ?? '';
      const desc = item.description ?? (item.icao && item.name ? `${item.icao} — ${item.name}` : item.name ?? id);
      return { id, description: desc };
    }) : [];

    showDropdown.value = suggestions.value.length > 0;
    highlighted.value = -1;
  } catch (err) {
    suggestions.value = [];
    showDropdown.value = false;
    highlighted.value = -1;
  } finally {
    loading.value = false;
  }
}

function debounceSearch(query) {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    if (query && query.length >= 1) {
      fetchSuggestions(query);
    } else {
      // if empty, clear suggestions
      suggestions.value = [];
      showDropdown.value = false;
    }
  }, 300);
}

function onInput(e) {
  const v = e.target.value;
  inputText.value = v;
  // when user types, clear bound modelValue (we only keep modelValue as selected id)
  // emit empty so parent knows selection cleared
  emit('update:modelValue', '');
  debounceSearch(v);
}

function selectSuggestion(item) {
  inputText.value = item.description; // show friendly description in input
  emit('update:modelValue', item.id);
  showDropdown.value = false;
}

function onKeyDown(e) {
  if (!showDropdown.value) return;
  if (e.key === 'ArrowDown') {
    e.preventDefault();
    highlighted.value = Math.min(highlighted.value + 1, suggestions.value.length - 1);
    scrollToHighlighted();
  } else if (e.key === 'ArrowUp') {
    e.preventDefault();
    highlighted.value = Math.max(highlighted.value - 1, 0);
    scrollToHighlighted();
  } else if (e.key === 'Enter') {
    e.preventDefault();
    if (highlighted.value >= 0 && suggestions.value[highlighted.value]) {
      selectSuggestion(suggestions.value[highlighted.value]);
    }
  } else if (e.key === 'Escape') {
    showDropdown.value = false;
  }
}

function scrollToHighlighted() {
  nextTick(() => {
    const list = document.getElementById(`${props.id || 'airport'}-list`);
    if (!list) return;
    const el = list.querySelector('.dropdown-item.active');
    if (el) el.scrollIntoView({ block: 'nearest' });
  });
}

function onFocus() {
  if (suggestions.value.length > 0) showDropdown.value = true;
  if (inputText.value && suggestions.value.length === 0) debounceSearch(inputText.value);
}

function onBlur() {
  // small timeout to allow click to register
  setTimeout(() => { showDropdown.value = false; }, 150);
}

function setupOutsideClick() {
  outsideClickHandler = (event) => {
    const root = inputEl.value ? inputEl.value.closest('.airport-autocomplete-root') : null;
    if (!root) return;
    if (!root.contains(event.target)) {
      showDropdown.value = false;
    }
  };
  document.addEventListener('click', outsideClickHandler);
}

function teardownOutsideClick() {
  if (outsideClickHandler) {
    document.removeEventListener('click', outsideClickHandler);
    outsideClickHandler = null;
  }
}

async function fetchAirportDetails(id) {
  if (!id) return;
  try {
    // Try fetching by ID endpoint first
    if (api && typeof api.get === 'function') {
      try {
        const resp = await api.get(`/airports/${encodeURIComponent(id)}`);
        const d = resp.data && (resp.data.data ?? resp.data);
        if (d) {
          const description = d.description ?? (d.icao && d.name ? `${d.icao} — ${d.name}` : d.name ?? (d.id ?? id));
          inputText.value = description;
          return;
        }
      } catch (err) {
        // ignore and fallback to search
      }

      // Fallback: search by id string
      const params = buildSearchParams(id);
      const resp2 = await api.get('/airports/search', { params });
      const data = resp2.data && resp2.data.data ? resp2.data.data : resp2.data;
      if (Array.isArray(data) && data.length > 0) {
        const item = data[0];
        const idv = item.id ?? item.icao ?? item.code ?? item._id ?? id;
        const desc = item.description ?? (item.icao && item.name ? `${item.icao} — ${item.name}` : item.name ?? idv);
        inputText.value = desc;
        return;
      }
    } else {
      // No api: fallback to fetch search
      const base = window.location.origin + '/api';
      const url = new URL(base + '/airports/search');
      const params = buildSearchParams(id);
      Object.keys(params).forEach(k => url.searchParams.append(k, params[k]));
      const resp = await fetch(url);
      const json = await resp.json();
      const data = json.data || json;
      if (Array.isArray(data) && data.length > 0) {
        const item = data[0];
        const idv = item.id ?? item.icao ?? item.code ?? item._id ?? id;
        const desc = item.description ?? (item.icao && item.name ? `${item.icao} — ${item.name}` : item.name ?? idv);
        inputText.value = desc;
        return;
      }
    }
  } catch (err) {
    // ignore
  }
  // if all else fails, show id
  inputText.value = id;
}

onMounted(() => {
  // initialize inputText from modelValue (show ICAO if provided)
  inputText.value = props.modelValue || '';
  if (props.modelValue) {
    fetchAirportDetails(props.modelValue);
  }
  setupOutsideClick();
});

onBeforeUnmount(() => {
  if (searchTimer) clearTimeout(searchTimer);
  teardownOutsideClick();
});

// keep in sync when parent updates modelValue (selected id)
watch(() => props.modelValue, (newVal) => {
  if (!newVal) {
    // clear input when modelValue cleared
    inputText.value = '';
    suggestions.value = [];
    showDropdown.value = false;
    return;
  }
  // If modelValue is set programmatically, reflect it in the inputText
  fetchAirportDetails(newVal);
});
</script>

<template>
  <div class="airport-autocomplete-root position-relative">
    <input
      :id="id"
      :name="name"
      ref="inputEl"
      type="text"
      class="form-control"
      :placeholder="placeholder"
      autocomplete="off"
      :value="inputText"
      @input="onInput"
      @keydown="onKeyDown"
      @focus="onFocus"
      @blur="onBlur"
      aria-autocomplete="list"
      :aria-expanded="showDropdown"
      :aria-owns="(id || 'airport') + '-list'"
    />

    <ul
      v-show="showDropdown"
      :id="(id || 'airport') + '-list'"
      class="dropdown-menu show w-100 mt-0"
      style="max-height: 260px; overflow:auto;">
      <li v-if="loading" class="dropdown-item text-center">
        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
      </li>
      <li v-if="!loading && suggestions.length === 0" class="dropdown-item text-muted">No results</li>
      <li v-for="(s, idx) in suggestions" :key="s.id" @mousedown.prevent="selectSuggestion(s)"
          :class="['dropdown-item', { active: idx === highlighted } ]">
        <div class="d-flex justify-content-between">
          <div>{{ s.description }}</div>
          <small class="text-muted ms-2">{{ s.id }}</small>
        </div>
      </li>
    </ul>
  </div>
</template>
