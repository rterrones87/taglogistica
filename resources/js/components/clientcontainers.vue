<script setup>
import { ref, watch, computed } from 'vue';
import { usePermissions } from '../composables/usePermissions';
import autocompleteinput from './autocompleteinput.vue';
import suggestioninput from './suggestioninput.vue';
import remoteselect from './remoteselect.vue';
import TableAction from '@/components/TableAction.vue';

const props = defineProps({
  rows: { type: Array, default: () => [] },
  client_id: { type: Number, required: true },
  errors: { type: Object, default: () => ({}) },
  disabled: { type: Boolean, default: false },
  disabledContainerNumber: { type: Boolean, default: false },
  canAddDelete: { type: Boolean, default: false }
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
  localRows.value.push({ order_number: '', container_number: '', place_id: 0, container_type: '', address: '', place :{} });
}

function removeRow(index) {
  localRows.value.splice(index, 1);
}

// Composable de permisos
const { hasPermission, hasAnyPermission } = usePermissions();

// Helper para verificar si puede editar campos de datos (orden, tipo, destino, dirección)
const canEdit = computed(() => {
  return hasAnyPermission(['services.create', 'services.edit']) && !props.disabled;
});

// Helper para agregar o eliminar filas (solo cuando canAddDelete es true)
const canAddDeleteRows = computed(() => {
  return hasAnyPermission(['services.create', 'services.edit']) && props.canAddDelete;
});

// Helper específico para el campo de número de contenedor
const canEditContainerNumber = computed(() => {
  return hasAnyPermission(['services.create', 'services.edit']) && !props.disabledContainerNumber;
});

// Función para obtener error de un campo específico del contenedor
const getContainerError = (index, field) => {
  const errorKey = `containers.${index}.${field}`;
  return props.errors[errorKey] ? props.errors[errorKey][0] : null;
};
</script>

<template>
  <table class="table">
    <thead>
      <tr>
        <th>Órden de compra</th>
        <th>No. de contenedor/Descripción</th>
        <th>Tipo de unidad</th>
        <th>Destino</th>
        <th>Dirección</th>
        <th v-if="canAddDeleteRows"></th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="(row, index) in localRows" :key="index">
        <!-- Solo si row.id existe, incluir el input hidden -->
        <input v-if="row.id" type="hidden" v-model="row.id" />
        <td>
          <div class="form-item">
            <input :disabled="!canEdit" v-model="row.order_number"/>
            <p v-if="getContainerError(index, 'order_number')" class="text-red-500 text-sm">
              {{ getContainerError(index, 'order_number') }}
            </p>
          </div>
        </td>
        <td>
          <div class="form-item">
            <autocompleteinput :disabled="!canEditContainerNumber" v-model="row.container_number" url="catalog/container-numbers"/>
            <p v-if="getContainerError(index, 'container_number')" class="text-red-500 text-sm">
              {{ getContainerError(index, 'container_number') }}
            </p>
          </div>
        </td>
        <td>
          <div class="form-item">
            <autocompleteinput :disabled="!canEdit" v-model="row.container_type" url="catalog/containers"/>
            <p v-if="getContainerError(index, 'container_type')" class="text-red-500 text-sm">
              {{ getContainerError(index, 'container_type') }}
            </p>
          </div>
        </td>
        <td>
          <div class="form-item">
            <suggestioninput
              :disabled="!canEdit"
              v-model="row.place_id"
              :url="`places`"
              valueKey="id"
              textKey="name"
              :textValue="row.place.name || ''" />
            <p v-if="getContainerError(index, 'place_id')" class="text-red-500 text-sm">
              {{ getContainerError(index, 'place_id') }}
            </p>
          </div>
           <!-- <remoteselect
              v-model="row.place_id"
              :endpoint="`places/detinations/${client_id}`"
              valueKey="id"
              textKey="name" 
            />-->
        </td>
        <td>
          <div class="form-item">
            <input :disabled="!canEdit" v-model="row.address" placeholder="Dirección"/>
            <p v-if="getContainerError(index, 'address')" class="text-red-500 text-sm">
              {{ getContainerError(index, 'address') }}
            </p>
          </div>
        </td>
        <td v-if="canAddDeleteRows">
          <div class="flex justify-center flex-col md:flex-row">
                <TableAction
                    title="Eliminar"
                    icon="delete.png"
                    @click.prevent="removeRow(index)"
                />
            </div>
          <!--
          <a href="#" @click.prevent="removeRow(index)" class="tb-delete"></a>
          -->
        </td>
      </tr>
    </tbody>
  </table>
  <div class="flex justify-end">
    <a 
      v-if="canAddDeleteRows" 
      href="#"
      @click.prevent="addRow" 
      class="my-2 py-2 px-3 flex items-center hover:opacity-80 border-2 border-white/15 rounded font-semibold shadow-sm text-white bg-dynamic-color">
      + Agregar contenedor
    </a>
  </div>
</template>
