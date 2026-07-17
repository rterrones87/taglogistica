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

// Inicializar con semana actual
onMounted(() => {
  if (!props.modelValue.start || !props.modelValue.end) {
    const today = new Date();
    const dayOfWeek = today.getDay();
    const diff = dayOfWeek === 0 ? -6 : 1 - dayOfWeek; // Lunes
    
    const startOfWeek = new Date(today);
    startOfWeek.setDate(today.getDate() + diff);
    
    const endOfWeek = new Date(startOfWeek);
    endOfWeek.setDate(startOfWeek.getDate() + 6); // Domingo
    
    startDate.value = startOfWeek.toISOString().split('T')[0];
    endDate.value = endOfWeek.toISOString().split('T')[0];
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
