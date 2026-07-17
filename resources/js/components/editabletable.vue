<script setup>
import { ref, watch } from 'vue';
import { usePermissions } from '../composables/usePermissions';
import autocompleteinput from './autocompleteinput.vue';
import TableAction from '@/components/TableAction.vue';

const props = defineProps({
  rows: { type: Array, default: () => [] }
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
  localRows.value.push({ order_number: '', container_number: '', destine: '', container_type: '' });
}

function removeRow(index) {
  localRows.value.splice(index, 1);
}

// Composable de permisos
const { hasPermission } = usePermissions();

// Helpers
const canEdit = hasPermission('services.create') || hasPermission('services.edit');
const isAssigning = hasPermission('services.assign');
</script>

<template>

    <table class="table">
      <thead>
        <tr>
          <th>Órden de compra</th>
          <th>No. de contenedor</th>
          <th v-if="isAssigning">Tipo de contenedor</th>
          <th v-else>Destino</th>
          <th v-if="canEdit"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row, index) in localRows" :key="index">
          <!-- Solo si row.id existe, incluir el input hidden -->
          <input v-if="row.id" type="hidden" v-model="row.id" />
          <td>
            <div class="form-item">
              <input :disabled="!canEdit" v-model="row.order_number"/>
            </div>
          </td>
          <td>
            <autocompleteinput :disabled="!canEdit" v-model="row.container_number" url="catalog/container-numbers"/>
          </td> 
          <td v-if="isAssigning">
            <autocompleteinput v-model="row.container_type" url="catalog/containers"/>
          </td>
          <td v-else>
            <autocompleteinput v-model="row.destine" url="catalog/destines"/>
          </td>
          <td v-if="canEdit">
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
        v-if="canEdit" 
        href="#" 
        @click.prevent="addRow" 
        class="my-2 py-2 px-3 flex items-center hover:opacity-80 border-2 border-white/15 rounded font-semibold shadow-sm text-white bg-dynamic-color">
        + Agregar fila
      </a>
   </div>
</template>
