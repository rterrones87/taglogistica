<script setup>
import { ref, watch } from 'vue';
import suggestioninput from './suggestioninput.vue';
import TableAction from '@/components/TableAction.vue';

const props = defineProps({
  rows: { type: Array, default: () => [] },
  visible: { type: Boolean, default: true }
});
const emit = defineEmits(['update:rows']);

const localRows = ref([]);

// Sincronización inicial
watch(
  () => props.rows,
  (val) => {
    localRows.value = Array.isArray(val) ? JSON.parse(JSON.stringify(val)) : [];
    
    // Asegurar que cada row tenga booths_outbound y booths_return
    localRows.value = localRows.value.map(row => ({
      ...row,
      booths_outbound: row.booths_outbound || [],
      booths_return: row.booths_return || []
    }));
  },
  { immediate: true }
);

// Emitir cambios
watch(
  localRows,
  (val) => {
    if (JSON.stringify(val) !== JSON.stringify(props.rows)) {
      emit('update:rows', val);
    }
  },
  { deep: true }
);

// MODIFICADO: addToll ahora recibe el array específico (ida o vuelta)
function addToll(boothsArray) {
  boothsArray.push({ booth_id: null, name: '' });
}

// MODIFICADO: removeToll ahora recibe el array y el índice
function removeToll(boothsArray, index) {
  boothsArray.splice(index, 1);
}

</script>

<template>
  <table class="table w-full">
    <thead>
      <tr>
        <th>Destino</th>
        <th>Casetas de Ida</th>
        <th>Casetas de Vuelta</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="(row, rowIndex) in localRows" :key="rowIndex">
        <!-- Columna Destino -->
        <td>
          <suggestioninput 
            v-model="row.place_id"
            url="places"
            valueKey="id"
            textKey="name"
            :textValue="row.name || ''"
            :disabled="true"
          />
        </td>
        
        <!-- Columna Casetas de IDA -->
        <td>
          <div v-for="(booth, index) in row.booths_outbound" :key="'out-' + index" class="flex items-center mb-1">
            <div class="grow">
              <suggestioninput 
                :disabled="!visible"
                v-model="booth.booth_id"
                url="booths"
                valueKey="id"
                textKey="name"
                :textValue="booth.name || ''"
              />
            </div>
            <TableAction
              v-if="visible"
              title="Eliminar"
              icon="delete.png"
              @click.prevent="removeToll(row.booths_outbound, index)"
            />
          </div>
          <a 
            v-if="visible"
            href="#" 
            @click.prevent="addToll(row.booths_outbound)" 
            class="text-blue-600 text-sm"
          >
            + Agregar caseta de ida
          </a>
        </td>
        
        <!-- Columna Casetas de VUELTA -->
        <td>
          <div v-for="(booth, index) in row.booths_return" :key="'ret-' + index" class="flex items-center mb-1">
            <div class="grow">
              <suggestioninput 
                :disabled="!visible"
                v-model="booth.booth_id"
                url="booths"
                valueKey="id"
                textKey="name"
                :textValue="booth.name || ''"
              />
            </div>
            <TableAction
              v-if="visible"
              title="Eliminar"
              icon="delete.png"
              @click.prevent="removeToll(row.booths_return, index)"
            />
          </div>
          <a 
            v-if="visible"
            href="#" 
            @click.prevent="addToll(row.booths_return)" 
            class="text-blue-600 text-sm"
          >
            + Agregar caseta de vuelta
          </a>
        </td>
      </tr>
    </tbody>
  </table>
</template>
