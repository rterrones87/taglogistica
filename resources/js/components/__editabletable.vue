<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:modelValue']);

const rows = ref([]);

// Inicializa con datos si existen
watch(
  () => props.modelValue,
  (val) => {
    rows.value = val.length ? JSON.parse(JSON.stringify(val)) : [];
  },
  { immediate: true }
);

// Emitir cuando cambian las filas
watch(rows, (val) => {
  emit('update:modelValue', val);
}, { deep: true });

// Métodos para agregar y eliminar filas
function addRow() {
  rows.value.push({ number: '', reference: '', destine: '' });
}

function removeRow(index) {
  rows.value.splice(index, 1);
}
</script>

<template>
  <div class="text-right">
    <table class="table">
      <thead>
        <tr>
          <th>Número</th>
          <th>Referencia</th>
          <th>Destino</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row, index) in rows" :key="index">
          <td data-label="Número">
            <input v-model="row.number" class="ms-2 md:ms-0 md:w-full border rounded px-1 py-0.5" />
          </td>
          <td data-label="Referencia">
            <input v-model="row.reference" class="ms-2 md:ms-0 md:w-full border rounded px-1 py-0.5" />
          </td>
          <td data-label="Destino">
            <input v-model="row.destine" class="ms-2 md:ms-0 md:w-full border rounded px-1 py-0.5" />
          </td>
          <td>
            <a href="#" @click="removeRow(index)" class="tb-delete"></a>
          </td>
        </tr>
      </tbody>
    </table>
    
        <a href="#" @click="addRow" class="form-button inline-block">
        + Agregar fila
        </a>
    
  </div>
</template>
