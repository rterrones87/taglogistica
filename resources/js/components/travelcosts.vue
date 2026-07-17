<script setup>
import { ref, watch, computed  } from 'vue';
import TableAction from '@/components/TableAction.vue';

const props = defineProps({
  rows: { type: Array, default: () => [] },
  booth_costs: { type: Number, default: 0},
  type: { type: String, default: "INIT"},
  visible: { type: Boolean, default: true }
});

const emit = defineEmits(['update:rows']);

const localRows = ref([]);

const totalCost = computed(() => {
  return localRows.value.reduce((sum, row) => {
    const cost = parseFloat(row.cost);
    return sum + (isNaN(cost) ? 0 : cost);
  }, 0) + props.booth_costs;
});

// ✅ Solo una vez al iniciar, sincroniza props -> local
watch(
  () => props.rows,
  (val) => {
    localRows.value = Array.isArray(val) ? JSON.parse(JSON.stringify(val)) : [];
  },
  { immediate: true } // <- importante para cargar datos al montar
);

// ✅ Solo emite si realmente cambia (evita loops)
watch(
  localRows,
  (val) => {
    if (JSON.stringify(val) !== JSON.stringify(props.rows)) {
      emit('update:rows', val);
    }
  },
  { deep: true }
);

function addRow() {
  localRows.value.push({ concept: '', cost: 0 });
}

function removeRow(index) {
  localRows.value.splice(index, 1);
}


</script>

<template>
  
    <table class="table">
      <thead>
        <tr>
          <th>Concepto</th>
          <th>Costo</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row, index) in localRows" :key="index">
          <!-- Solo si row.id existe, incluir el input hidden -->
          <input v-if="row.id" type="hidden" v-model="row.id" />
          <td>
            <div class="form-item">
              <input 
                :disabled="!visible"
                v-model="row.concept"
              />
            </div>
          </td>
          <td>
            <div class="form-item">
              <input 
                :disabled="!visible"
                v-model="row.cost"
              />
            </div>
          </td>
          <td>
            <!--<a href="#" @click.prevent="removeRow(index)" class="tb-delete"></a>-->
            <TableAction
                  v-if="visible"
                  title="Eliminar"
                  icon="delete.png"
                  @click.prevent="removeRow(index)"
              />
          </td>
        </tr>
      </tbody>
      <tfoot>
        <tr>
          <td></td>
          <td>
            <div class="items-center flex ps-2">
              <span v-if="props.type == 'INIT'">Gastos totales del viaje:</span>
              <span v-else>Costos extras totales:</span>
              <span class="ps-2 text-2xl font-bold">${{ totalCost.toFixed(2) }}</span>
            </div>
            <p v-if="props.type == 'INIT'" class="text-red-500 text-start">*El costo de las casillas se sumara al guardar</p>
          </td>
          <td></td>
        </tr>
      </tfoot>
    </table>
    <div class="flex justify-end">
      <a 
        v-if="visible"
        href="#" 
        @click.prevent="addRow" 
        class="my-2 py-2 px-3 flex items-center hover:opacity-80 border-2 border-white/15 rounded font-semibold shadow-sm text-white bg-dynamic-color"
      >
        + Agregar costo
      </a>
   </div>
</template>
