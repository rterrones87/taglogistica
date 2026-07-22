<script setup>
import { ref, watch, nextTick  } from 'vue';
import TableAction from '@/components/TableAction.vue';
import CurrencyInput from '@/components/CurrencyInput.vue';
import axios from 'axios';

const props = defineProps({
  rows: { type: Array, default: () => [] }
});

const emit = defineEmits(['update:rows']);

const localRows = ref([]);

const maxQuantity = ref(20)

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
  localRows.value.push({ discount : '', amount: 0 });
}

function removeRow(index) {
  localRows.value.splice(index, 1);
}


</script>

<template>
  
    <table class="table">
      <thead>
        <tr>
          <th>Descuento</th>
          <th>Cantidad</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row, index) in localRows" :key="index">
          <!-- Solo si row.id existe, incluir el input hidden -->
          <input v-if="row.id" type="hidden" v-model="row.id" />
          <td>
            <div class="form-item">
              <input v-model="row.discount"/>
            </div>
          </td> 
          <td>
            <div class="form-item">
              <CurrencyInput
                v-model="row.amount"
                :min="0"
                placeholder="0.00"
                :disabled="!row.discount"
              />
            </div>
          </td>
          
          <td>
            <!--<a href="#" @click.prevent="removeRow(index)" class="tb-delete"></a>-->
            <TableAction
                  title="Eliminar"
                  icon="delete.png"
                  @click.prevent="removeRow(index)"
              />
          </td>
        </tr>
      </tbody>
    </table>
    <div class="flex justify-end">
      <a 
        href="#" 
        @click.prevent="addRow" 
        class="my-2 py-2 px-3 flex items-center hover:opacity-80 border-2 border-white/15 rounded font-semibold shadow-sm text-white bg-dynamic-color">
        + Agregar descuento
      </a>
    </div>
</template>
