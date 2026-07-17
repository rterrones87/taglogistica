<script setup>
import { ref, watch } from 'vue';
import suggestioninput from './suggestioninput.vue';
import TableAction from '@/components/TableAction.vue';

const props = defineProps({
  rows: { type: Array, default: () => [] },
  disabled: { type: Boolean, default: false }
});

const emit = defineEmits(['update:rows']);

const localRows = ref([]);

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
  localRows.value.push({ supplier_id : 0, request_type :0, description : '', quantity: 0, cost : 0, supplier : {name:''} });
}

function removeRow(index) {
  localRows.value.splice(index, 1);
}

</script>

<template>

    <table class="table">
      <thead>
        <tr>
          <th>Proveedor</th>
          <th>Tipo</th>
          <th>Descripción</th>
          <th>Cantidad</th>
          <th>Costo</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row, index) in localRows" :key="index">
          <!-- Solo si row.id existe, incluir el input hidden -->
          <input v-if="row.id" type="hidden" v-model="row.id" />
          <td>
            <suggestioninput 
              v-model="row.supplier_id" 
              url="suppliers"
              valueKey="id"
              textKey="name"
              :textValue="row.supplier.name || ''"
              :disabled="disabled"
            />
          </td>
          <td>
            <div class="form-item">
              <select 
                  v-model="row.request_type"
                  :disabled="disabled"
              >
                  <option value="1">Servicio</option>
                  <option value="2">Producto</option>
              </select>
            </div>
          </td>
          <td>
            <div class="form-item">
              <input v-model="row.description" :disabled="disabled"/>
            </div>
          </td>
          <td>
            <div class="form-item">
              <input v-model="row.quantity" :disabled="disabled"/>
            </div>
          </td>
          <td>
            <div class="form-item">
              <input v-model="row.cost" :disabled="disabled"/>
            </div>
          </td>
          <td>
            <!--<a href="#" @click.prevent="removeRow(index)" class="tb-delete"></a>-->
            <TableAction
                  v-if="!disabled"
                  title="Eliminar"
                  icon="delete.png"
                  @click.prevent="removeRow(index)"
              />
          </td>
        </tr>
      </tbody>
    </table>
    <div v-if="!disabled" class="flex justify-end">
      <a 
        href="#" 
        @click.prevent="addRow" 
        class="my-2 py-2 px-3 flex items-center hover:opacity-80 border-2 border-white/15 rounded font-semibold shadow-sm text-white bg-dynamic-color">
        + Agregar solicitud
      </a>
    </div>
</template>
