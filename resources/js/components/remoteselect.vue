<template>
  <select 
    v-model="selectedValue" 
    class="p-[.40rem] w-full border border-slate-300"
    :disabled="disabled">
    <option disabled value="">Seleccione una opción</option>
    <option
      v-for="item in options"
      :key="item[valueKey]"
      :value="item[valueKey]"
    >
      {{ getDisplayText(item) }}
    </option>
  </select>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';

// ✅ Props como objeto (para usarlas con props.modelValue, etc.)
const props = defineProps({
  modelValue: [String, Number],
  endpoint: { type: String, required: true },
  valueKey: { type: String, default: 'id' },
  textKey: { type: String, default: 'name' },
  disabled: { type: Boolean, default: false },
  textFormatter: { type: Function, default: null }, // Función para formatear el texto mostrado
  filterFunction: { type: Function, default: null }, // Función para filtrar opciones
});

// ✅ Emits
const emit = defineEmits(['update:modelValue']);

// ✅ Estado local
const options = ref([]);
const selectedValue = ref(props.modelValue); // Inicializa con modelValue

// 🔄 Sincronizar selectedValue con modelValue (desde el padre)
watch(() => props.modelValue, (val) => {
  selectedValue.value = val;
});

// 🔄 Emitir hacia el padre cuando cambia el valor local
watch(selectedValue, (val) => {
  emit('update:modelValue', val);
});

// 🔨 Función para obtener el texto a mostrar
const getDisplayText = (item) => {
  if (props.textFormatter) {
    return props.textFormatter(item);
  }
  return item[props.textKey] || '';
};

// 📡 Cargar opciones del endpoint
const fetchData = async () => {
  try {
    const response = await axios.get(props.endpoint);
    
    // Aplicar filterFunction si existe
    if (props.filterFunction) {
      options.value = response.data.filter(props.filterFunction);
    } else {
      options.value = response.data;
    }

    // Si ya hay un modelValue, asegúrate de seleccionarlo cuando se carguen los datos
    const found = options.value.find(
      (item) => item[props.valueKey] === props.modelValue
    );
    if (found) {
      selectedValue.value = props.modelValue;
    }
  } catch (error) {
    console.error('Error al cargar datos del selector remoto:', error);
  }
};

// ▶️ Cargar al montar
onMounted(fetchData);
</script>
