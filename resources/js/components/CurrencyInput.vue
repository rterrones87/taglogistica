<template>
  <div class="w-full border border-slate-300 rounded flex py-2 px-2 bg-white" :class="{ 'currency-disabled': disabled }">
    <span class="currency-symbol">$</span>
    <input
      ref="inputRef"
      :value="displayValue"
      @keydown="handleKeydown"
      @paste="handlePaste"
      type="text"
      inputmode="numeric"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      class="currency-input-field"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  modelValue: [Number, String],
  min: { type: Number, default: 0 },
  max: Number,
  placeholder: { type: String, default: '0.00' },
  disabled: { type: Boolean, default: false },
  required: { type: Boolean, default: false }
});

const emit = defineEmits(['update:modelValue']);
const inputRef = ref(null);

// Convertir valor a centavos (entero)
const toCents = (value) => {
  const num = parseFloat(value);
  if (isNaN(num)) return 0;
  return Math.round(num * 100);
};

// Convertir centavos a valor decimal
const fromCents = (cents) => {
  return (cents / 100).toFixed(2);
};

// Formatear número con separadores de miles y 2 decimales
const formatCurrency = (value) => {
  const num = parseFloat(value);
  if (isNaN(num)) return '0.00';
  
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(num);
};

// Valor formateado para mostrar
const displayValue = computed(() => {
  return formatCurrency(props.modelValue);
});

const handleKeydown = (event) => {
  // Permitir Cmd+V (Mac) o Ctrl+V (Windows/Linux) para pegar
  if ((event.metaKey || event.ctrlKey) && event.key === 'v') {
    return; // Dejar que el evento paste se maneje normalmente
  }
  
  // Permitir Cmd+C (Mac) o Ctrl+C (Windows/Linux) para copiar
  if ((event.metaKey || event.ctrlKey) && event.key === 'c') {
    return; // Permitir copiar
  }
  
  // Permitir Cmd+A (Mac) o Ctrl+A (Windows/Linux) para seleccionar todo
  if ((event.metaKey || event.ctrlKey) && event.key === 'a') {
    return; // Permitir seleccionar todo
  }
  
  // Permitir teclas de control
  const controlKeys = [
    'Backspace', 'Delete', 'Tab', 'Escape', 'Enter',
    'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown',
    'Home', 'End'
  ];
  
  if (controlKeys.includes(event.key)) {
    if (event.key === 'Backspace') {
      event.preventDefault();
      handleBackspace();
    }
    return;
  }
  
  // Solo permitir dígitos
  if (!/^[0-9]$/.test(event.key)) {
    event.preventDefault();
    return;
  }
  
  event.preventDefault();
  handleDigit(event.key);
};

const handleDigit = (digit) => {
  // Obtener valor actual en centavos
  let cents = toCents(props.modelValue);
  
  // Multiplicar por 10 y agregar el nuevo dígito
  cents = cents * 10 + parseInt(digit);
  
  // Convertir de vuelta a decimal
  const newValue = fromCents(cents);
  
  // Aplicar límite máximo si existe
  if (props.max !== undefined && parseFloat(newValue) > props.max) {
    return; // No permitir exceder el máximo
  }
  
  emit('update:modelValue', newValue);
};

const handleBackspace = () => {
  // Obtener valor actual en centavos
  let cents = toCents(props.modelValue);
  
  // Dividir entre 10 (eliminar último dígito)
  cents = Math.floor(cents / 10);
  
  // Convertir de vuelta a decimal
  const newValue = fromCents(cents);
  
  emit('update:modelValue', newValue);
};

const handlePaste = (event) => {
  event.preventDefault();
  
  // Obtener texto pegado
  const pastedText = event.clipboardData.getData('text');
  
  // Limpiar y validar
  const cleanValue = pastedText.replace(/[^0-9.]/g, '');
  const num = parseFloat(cleanValue);
  
  if (!isNaN(num)) {
    // Aplicar límites
    let finalValue = num;
    if (props.min !== undefined && finalValue < props.min) {
      finalValue = props.min;
    }
    if (props.max !== undefined && finalValue > props.max) {
      finalValue = props.max;
    }
    
    emit('update:modelValue', finalValue.toFixed(2));
  }
};

// Asegurar que el valor inicial tenga formato correcto
watch(() => props.modelValue, (newValue) => {
  if (newValue === '' || newValue === null || newValue === undefined) {
    emit('update:modelValue', '0.00');
  }
}, { immediate: true });
</script>

<style scoped>
.currency-symbol {
  color: #6b7280;
  font-weight: 500;
  pointer-events: none;
  z-index: 1;
  display: block;
  padding-right: 5px;
}

.currency-input-field {
  outline: none;
  width: 100%;
}

.currency-disabled {
  background-color: #bdbdbb;
}
</style>
