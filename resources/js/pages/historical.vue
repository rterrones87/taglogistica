<template>
    <breadcrumb :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 rounded shadow-md">

        <h2 class="text-3xl my-4 font-bold text-center md:text-left">Asignar Servicio</h2>
    
        <!--
        <nav class="tabbar">
            <router-link :to="{ path: `/panel/service/${item.id}` }">Asignación</router-link>
            <router-link class="active" :to="{ path: `/panel/service/historical/${item.id}` }">Historicos</router-link>
        </nav>
        -->
        <SegmentedControl
            :titles="types"
            @OnClicked="(i) => filterData(i)"
            v-model="currentType"
        />

        <div class="flex items-center">
            <h3 class="grow text-xl my-4 font-bold text-center md:text-left">Historial de operadores</h3>
            <button class="form-button" type="button" @click.prevent="openModal(item.id)">Reasignar</button>
        </div>
        
        <table class="table text-center">
            <thead>
                <tr>
                    <th>Operador</th>
                    <th>Fecha de asignación</th>
                    <th>¿Se pagara?</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="operator in item.operators" :key="operator.id">
                    <td>{{ operator.first_details }}</td>
                    <td>{{ operator.date }}</td>
                    <td>{{ operator.second_details }}</td>
                </tr>
            </tbody>
        </table>

        <div class="flex items-center">
            <h3 class="grow text-xl my-4 font-bold text-center md:text-left">Historial de unidades</h3>
            <button class="form-button" type="button" @click.prevent="openModal(item.id)">Reasignar</button>
        </div>

        <table class="table text-center">
            <thead>
                <tr>
                    <th>Unidad</th>
                    <th>Fecha de asignación</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="unit in item.units" :key="unit.id">
                    <td>{{ unit.first_details }}</td>
                    <td>{{ unit.date }}</td>
                </tr>
            </tbody>
        </table>

        <div class="flex items-center">
            <h3 class="grow text-xl my-4 font-bold text-center md:text-left">Historial de estados</h3>
            <button class="form-button" type="button" @click.prevent="openModal(item.id)">Reasignar</button>
        </div>

        <table class="table text-center">
            <thead>
                <tr>
                    <th>Estado</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="state in item.states" :key="state.id">
                    <td>{{ state.first_details }}</td>
                    <td>{{ state.date }}</td>
                </tr>
            </tbody>
        </table>
    </div>

  <BaseModal
    :show="showModal"
    title="Reasignar servicio"
    @close="showModal = false"
  >
    <div class="form-item">
        <label>Operador:</label>
        <!--
        <suggestioninput
          v-model="modalForm.operator_id"
          url="operators"
          valueKey="id"
          textKey="name"
          :textValue="modalForm.operator_name"
        />
        -->
        <remoteselect
            v-model="modalForm.operator_id"
            endpoint="operators"
            valueKey="id"
            textKey="name"
        />
      </div>
      <div class="form-item">
        <label>Unidad:</label>
        <suggestioninput
          v-model="modalForm.unit_id"
          url="units"
          valueKey="id"
          textKey="econame"
          :textValue="modalForm.unit_name"
        />
      </div>
      <label>
        <input type="checkbox" v-model="modalForm.payment"/>
        <span class="mx-1">Se pagara?</span>
      </label>

    <template #footer>
      <div class="flex justify-end gap-2">
        <button @click="showModal = false" class="form-button bg-[#6e7881]">Cancelar</button>
        <button @click="confirmReassign" class="form-button">Aceptar</button>
      </div>
    </template>
  </BaseModal>

</template>

<script setup>
import { inject, ref } from "vue";
import { useRoute, useRouter } from 'vue-router';
import { upsert } from '../composables/upsert';
import breadcrumb from '../components/breadcrumb.vue';
import suggestioninput from '../components/suggestioninput.vue';
import remoteselect from '../components/remoteselect.vue';
import SegmentedControl from '@/components/SegmentedControl.vue';
import BaseModal from '../components/BaseModal.vue';
import axios from "axios";

const dialogs = inject("swal");
const route = useRoute();
const router = useRouter();

const { item, loadItem } = upsert({
    endpoint: 'services/historical',
    data: { id:0, status:[], operators:[], units:[] },
    dialogs
});

// Definir los elementos del breadcrumb
const breadcrumbItems = [
    { title: 'Servicios', path: '/panel/services' },
    { title: 'Asignar Servicio', path: ''} 
];

    
const showModal = ref(false);
const modalForm = ref({
  operator_id: null,
  unit_id: null,
  operator_name: '',
  unit_name: '',
  payment : false
});
const selectedId = ref(null);

const openModal = (id) => {
  selectedId.value = id;
  showModal.value = true;
};

const confirmReassign = async () => {
  if (!modalForm.value.operator_id || !modalForm.value.unit_id) {
    return alert("Ambos campos son obligatorios");
  }

  try {
    await axios.post(`services/reassign/${selectedId.value}`, {
      operator_id: modalForm.value.operator_id,
      unit_id: modalForm.value.unit_id,
      payment: modalForm.value.payment
    });
    showModal.value = false;
    dialogs.fire("Éxito", "El viaje fue reasignado correctamente.", "success");
    await loadItem(selectedId.value);
  } catch (error) {
    console.error(error);
    alert("Error al reasignar");
  }
};

const currentType = ref("2");

const types = ref([
  { id: '1', label: 'Asignación', icon : 'assign.png'  },
  { id: '2', label: 'Historicos', icon : 'historical.png'  },
])

const filterData = (i) => {
    currentType.value = i;
    router.push({
        path: i==1? `/panel/service/${item.value.id}` : `/panel/service/historical/${item.value.id}`
    });
}

</script>
  
  