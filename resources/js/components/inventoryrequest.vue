<script setup>
import { ref, watch, nextTick  } from 'vue';
import suggestioninput from './suggestioninput.vue';
import TableAction from '@/components/TableAction.vue';
import axios from 'axios';

const props = defineProps({
  rows: { type: Array, default: () => [] },
  disabled: { type: Boolean, default: false }
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
  localRows.value.push({ inventory_id : 0, quantity: 0, inventory: {name:'', quantity:0} });
}

function removeRow(index) {
  localRows.value.splice(index, 1);
}

function validQuantity(row) {
    const current = Number(row.quantity)

    if(current > row.inventory.quantity) {
      row.quantity = row.inventory.quantity;
    }
}

async function OnSelected(id) {
  let { data } = await axios.get('inventories/' + id);
  const item = localRows.value.find(obj => obj.inventory_id === id);

  if (item) {
    item.inventory.quantity = data.quantity; 
  }
}

</script>

<template>
  
    <table class="table">
      <thead>
        <tr>
          <th>Producto</th>
          <th>Cantidad</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row, index) in localRows" :key="index">
          <!-- Solo si row.id existe, incluir el input hidden -->
          <input v-if="row.id" type="hidden" v-model="row.id" />
          <td>
            <suggestioninput 
              v-model="row.inventory_id" 
              url="inventories"
              valueKey="id"
              textKey="name"
              :textValue="row.inventory.name || ''"
              :disabled="disabled"
              @onSelected="OnSelected"
            />
          </td>
          <td>
            <div class="form-item">
              <input 
                v-model="row.quantity" 
                @input="validQuantity(row)"
                :disabled="disabled || !row.inventory_id"
                />
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
        + Agregar producto
      </a>
    </div>
</template>
