<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
  modelValue: [String, Number, Object], // Para v-model
  url: { type: String, required: true },
  valueKey: { type: String, required: true },
  textKey: { type: String, required: true },
  textValue: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  textFormatter: { type: Function, default: null }, // Función para formatear el texto
  filterFunction: { type: Function, default: null }, // Función para filtrar resultados
});

const emit = defineEmits(['update:modelValue', 'onSelected']);

//const query = ref('');
const query = ref(props.textValue || '');
const suggestions = ref([]);
let showDropdown = ref(false);
const isSelecting = ref(false);
const hasInteracted = ref(false);

// Función para obtener el texto a mostrar
const getDisplayText = (item) => {
  if (props.textFormatter) {
    return props.textFormatter(item);
  }
  return item[props.textKey] || '';
};

function onInput() {
  hasInteracted.value = true;
}

watch(() => props.textValue, (newVal) => {
  if (newVal && !query.value) {
    query.value = newVal;
  }
});

// Observar el input para hacer fetch
watch(query, async (newQuery) => {
 
  if (!hasInteracted.value) return;

  if (isSelecting.value) {
    isSelecting.value = false;
    return; // 🚫 No buscar si estamos seleccionando
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
      
      // Aplicar filtro personalizado si existe
      if (props.filterFunction) {
        suggestions.value = data.filter(props.filterFunction);
      } else {
        suggestions.value = data;
      }
      
      showDropdown.value = true;
    } catch (error) {
      console.error('Error fetching suggestions:', error);
    }
  }
});


// Para seleccionar un item
function selectSuggestion(item) {
  isSelecting.value = true;
  emit('update:modelValue', item[props.valueKey]);
  emit('onSelected', item[props.valueKey]);
  query.value = getDisplayText(item);
  showDropdown.value = false;
  hasInteracted.value = false;
}


function closeDrop() {
  setTimeout(() => { 
    showDropdown.value = false 
    hasInteracted.value = false;
  }, 200);
}

// Mostrar el texto seleccionado si v-model tiene valor
const selectedText = computed(() => {
  if (typeof props.modelValue === 'object') return props.modelValue[props.textKey] || '';
  return query.value;
});
</script>

<template>
  <div class="relative">
    <input
      type="text"
      v-model="query"
      @focus="showDropdown = !!suggestions.length"
      @blur="() => closeDrop()"
      @input="() => onInput()"
      class="border rounded py-[5px] px-2 w-full"
      placeholder="Buscar..."
      :disabled="disabled"
    />

    <ul v-if="showDropdown" class="absolute left-0 right-0 bg-white border mt-1 rounded shadow-lg z-10 max-h-60 overflow-y-auto">
      <li
        v-for="item in suggestions"
        :key="item[props.valueKey]"
        @click="selectSuggestion(item)"
        class="p-2 hover:bg-blue-100 cursor-pointer text-left"
      >
        {{ getDisplayText(item) }}
      </li>
    </ul>
  </div>
</template>

<style scoped>
/* Puedes personalizar el estilo del dropdown aquí */
</style>
  