<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
  operators: { type: Array, default: () => [] }, // {icao, iata, name, logo_url}
  modelValue: { type: String, default: null },    // selected ICAO
});
const emit = defineEmits(['update:modelValue']);

const text = ref('');
const open = ref(false);
const typing = ref(false);

const selected = computed(() => props.operators.find((o) => o.icao === props.modelValue) || null);
watch(() => [props.modelValue, props.operators], () => { if (!typing.value) text.value = selected.value?.name || ''; }, { immediate: true });

const visible = computed(() => {
  const q = typing.value ? text.value.trim().toLowerCase() : '';
  if (!q) return props.operators;
  return props.operators.filter((o) => [o.name, o.icao, o.iata].some((f) => (f ?? '').toLowerCase().includes(q)));
});

function choose(o) { typing.value = false; open.value = false; text.value = o.name || o.icao; emit('update:modelValue', o.icao); }
function clear() { typing.value = false; text.value = ''; emit('update:modelValue', null); }
function onBlur() { open.value = false; typing.value = false; text.value = selected.value?.name || ''; }
</script>

<template>
  <div class="position-relative">
    <label class="form-label small text-muted text-uppercase mb-1">Operator</label>
    <div class="position-relative">
      <input v-model="text" type="text" class="form-control form-control-sm pe-4" placeholder="All operators" autocomplete="off"
             @focus="open = true; typing = false" @blur="onBlur" @input="typing = true; open = true" />
      <button v-if="modelValue" type="button" class="btn-close position-absolute top-50 end-0 translate-middle-y me-2"
              style="font-size: .6rem" aria-label="Clear" @mousedown.prevent="clear"></button>
    </div>
    <div v-if="open" class="dropdown-menu show shadow overflow-auto" style="max-height: 16rem; min-width: 20rem">
      <div v-if="!visible.length" class="dropdown-item-text small text-muted">No matching operators</div>
      <button v-for="o in visible" :key="o.icao" type="button" class="dropdown-item py-1" @mousedown.prevent="choose(o)">
        <div class="d-flex align-items-center gap-2">
          <img v-if="o.logo_url" :src="o.logo_url" :alt="o.iata" style="height: 1.5rem; width: 2rem; object-fit: contain" />
          <span v-else style="width: 2rem"></span>
          <div>
            <div class="small fw-semibold">{{ o.name }}</div>
            <small class="text-muted">{{ [o.iata, o.icao].filter(Boolean).join(' · ') }}</small>
          </div>
        </div>
      </button>
    </div>
  </div>
</template>
