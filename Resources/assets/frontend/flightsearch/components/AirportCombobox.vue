<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
  modelValue: { type: String, default: null },
  options: { type: Array, default: () => [] }, // {icao, iata, name, hint}
  label: { type: String, required: true },
  placeholder: { type: String, default: 'Search airports' },
  maxVisible: { type: Number, default: 40 },
});
const emit = defineEmits(['update:modelValue']);

const text = ref('');
const open = ref(false);
const highlighted = ref(0);
const typing = ref(false);

function labelFor(icao) {
  if (!icao) return '';
  const o = props.options.find((x) => x.icao === icao);
  return o?.iata ? `${icao} · ${o.iata}` : icao;
}

watch(() => [props.modelValue, props.options], () => {
  if (!typing.value) text.value = labelFor(props.modelValue);
}, { immediate: true });

function score(o, q) {
  const icao = o.icao.toLowerCase(); const iata = (o.iata ?? '').toLowerCase();
  if (icao === q || iata === q) return 3;
  if (icao.startsWith(q) || iata.startsWith(q)) return 2;
  if ((o.name ?? '').toLowerCase().startsWith(q)) return 1;
  return 0;
}

const visible = computed(() => {
  const q = typing.value ? text.value.trim().toLowerCase() : '';
  let list = props.options;
  if (q) {
    list = list.filter((o) => o.icao.toLowerCase().includes(q) || (o.iata ?? '').toLowerCase().includes(q) || (o.name ?? '').toLowerCase().includes(q))
      .sort((a, b) => score(b, q) - score(a, q));
  }
  return list.slice(0, props.maxVisible);
});

function onFocus(e) { open.value = true; typing.value = false; highlighted.value = 0; e.target.select(); }
function onInput() { typing.value = true; open.value = true; highlighted.value = 0; }
function onBlur() { open.value = false; typing.value = false; text.value = labelFor(props.modelValue); }
function move(d) { open.value = true; highlighted.value = Math.max(0, Math.min(visible.value.length - 1, highlighted.value + d)); }
function choose(o) { typing.value = false; open.value = false; text.value = labelFor(o.icao); emit('update:modelValue', o.icao); }
function chooseHighlighted() { const o = visible.value[highlighted.value]; if (o) choose(o); }
function clear() { typing.value = false; text.value = ''; emit('update:modelValue', null); }
</script>

<template>
  <div class="position-relative w-100">
    <label class="form-label small text-muted text-uppercase mb-1">{{ label }}</label>
    <div class="position-relative">
      <input v-model="text" type="text" class="form-control form-control-sm pe-4" autocomplete="off"
             :placeholder="placeholder" @focus="onFocus" @blur="onBlur" @input="onInput"
             @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)"
             @keydown.enter.prevent="chooseHighlighted" @keydown.escape="open = false" />
      <button v-if="modelValue" type="button" class="btn-close position-absolute top-50 end-0 translate-middle-y me-2"
              style="font-size: .6rem" aria-label="Clear" @mousedown.prevent="clear"></button>
    </div>
    <div v-if="open" class="dropdown-menu show shadow overflow-auto" style="max-height: 16rem; min-width: 20rem">
      <div v-if="!visible.length" class="dropdown-item-text small text-muted">No matching airports</div>
      <button v-for="(o, i) in visible" :key="o.icao" type="button"
              :class="['dropdown-item py-1', highlighted === i ? 'active' : '']"
              @mousedown.prevent="choose(o)" @mouseenter="highlighted = i">
        <div class="d-flex justify-content-between gap-2">
          <span class="fw-semibold">{{ o.icao }}<small v-if="o.iata" class="ms-1 fw-normal opacity-75">{{ o.iata }}</small></span>
          <small v-if="o.hint" class="opacity-75 text-nowrap">{{ o.hint }}</small>
        </div>
        <small class="d-block text-truncate opacity-75">{{ o.name || '—' }}</small>
      </button>
    </div>
  </div>
</template>
