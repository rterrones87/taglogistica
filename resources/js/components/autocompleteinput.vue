<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
  modelValue: String, // v-model
  url: { type: String, required: true },
  disabled: { type: Boolean, default: false},
});

const emit = defineEmits(['update:modelValue']);

const query = ref(props.modelValue || '');
const suggestions = ref([]);
const showDropdown = ref(false);
const isSelecting = ref(false);
const hasInteracted = ref(false);

function onInput() {
  hasInteracted.value = true;
}

// Actualiza valor externo si se edita desde dentro
watch(query, (val) => {
  if (!isSelecting.value) {
    emit('update:modelValue', val);
  }
});

// Escucha cambios desde fuera
watch(() => props.modelValue, (val) => {
  if (val !== query.value) query.value = val;
});

// Buscar sugerencias
watch(query, async (newQuery) => {

  if (!hasInteracted.value) return;

  if (isSelecting.value) {
    isSelecting.value = false;
    return;
  }

  if (!newQuery) {
    suggestions.value = [];
    showDropdown.value = false;
    return;
  }

  if(newQuery.length >= 1)
  {
    try {
      const { data } = await axios.get(props.url, { params: { q: newQuery } });
      suggestions.value = data;
      showDropdown.value = data.length > 0;
    } catch (error) {
      console.error('Error fetching suggestions:', error);
    }
  }
  
});

function selectSuggestion(suggestion) {
  isSelecting.value = true;
  query.value = suggestion;
  emit('update:modelValue', suggestion);
  showDropdown.value = false;
  hasInteracted.value = false;
}

function closeDropdown() {
  setTimeout(() => { 
    showDropdown.value = false;
    hasInteracted.value = false;
  }, 200);
}
</script>

<template>
  <div class="relative">
    <input
      type="text"
      v-model="query"
      @focus="showDropdown = !!suggestions.length"
      @blur="closeDropdown"
      @input="() => onInput()"
      class="border py-[5px] px-2 w-full rounded"
      placeholder="Escribe algo..."
      :disabled=disabled
    />

    <ul
      v-if="showDropdown"
      class="absolute left-0 right-0 bg-white border mt-1 rounded shadow-lg z-10 max-h-60 overflow-y-auto"
    >
      <li
        v-for="(item, index) in suggestions"
        :key="index"
        @click="selectSuggestion(item)"
        class="p-2 hover:bg-blue-100 cursor-pointer text-left"
      >
        {{ item }}
      </li>
    </ul>
  </div>
</template>
