<template>
  <div class="flex items-center gap-2" :class="{ 'opacity-50': disabled }">
    <VueDatePicker
      v-model="startDate"
      placeholder="Fecha inicio"
      :enable-time-picker="false"
      model-type="yyyy-MM-dd"
      utc="preserve"
      :format="dateFormat"
      :max-date="endDate || undefined"
      :disabled="disabled"
      week-start="0"
      class="w-40"
    />
    <span class="text-gray-500">—</span>
    <VueDatePicker
      v-model="endDate"
      placeholder="Fecha final"
      :enable-time-picker="false"
      model-type="yyyy-MM-dd"
      utc="preserve"
      :format="dateFormat"
      :min-date="startDate || undefined"
      :disabled="disabled"
      week-start="0"
      class="w-40"
    />
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({ start: '', end: '' })
  },
  loading: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:modelValue', 'range-changed']);

const startDate = ref('');
const endDate = ref('');

// Formato dd/mm/yyyy
const dateFormat = (date) => {
  if (!date) return '';
  
  // Si date es un string en formato 'yyyy-MM-dd'
  if (typeof date === 'string') {
    const [year, month, day] = date.split('-');
    return `${day}/${month}/${year}`;
  }
  
  // Si date es un objeto Date
  const d = date instanceof Date ? date : new Date(date);
  if (isNaN(d.getTime())) return '';
  
  const day = String(d.getDate()).padStart(2, '0');
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const year = d.getFullYear();
  return `${day}/${month}/${year}`;
};

// Inicializar con semana actual (domingo a sábado)
onMounted(() => {
  if (!props.modelValue.start || !props.modelValue.end) {
    const today = new Date();
    const dayOfWeek = today.getDay(); // 0=Domingo, 1=Lunes, ..., 6=Sábado
    
    // Calcular domingo de esta semana usando fecha local (no UTC)
    const startOfWeek = new Date(today.getFullYear(), today.getMonth(), today.getDate() - dayOfWeek);
    
    // Calcular sábado (6 días después del domingo)
    const endOfWeek = new Date(today.getFullYear(), today.getMonth(), today.getDate() - dayOfWeek + 6);
    
    // Formatear sin toISOString() para evitar conversión a UTC
    const formatDate = (date) => {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    };
    
    startDate.value = formatDate(startOfWeek);
    endDate.value = formatDate(endOfWeek);
  } else {
    startDate.value = props.modelValue.start;
    endDate.value = props.modelValue.end;
  }
});

// Sincronizar con el valor externo
watch(() => props.modelValue, (newVal) => {
  if (newVal && newVal.start && newVal.end) {
    startDate.value = newVal.start;
    endDate.value = newVal.end;
  }
}, { deep: true });

// Emitir cambios cuando se actualicen las fechas
watch([startDate, endDate], ([newStart, newEnd]) => {
  if (newStart && newEnd) {
    emit('update:modelValue', { start: newStart, end: newEnd });
    emit('range-changed', newStart, newEnd);
  }
});
</script>
