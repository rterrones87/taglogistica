<template>
    <breadcrumb :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 rounded shadow-md">

        <div class="flex items-center flex-col md:flex-row">
            <h2 class="text-3xl my-4 font-bold grow text-center md:text-left">Viajes</h2>

            <div class="my-2 flex justify-end hidden md:flex gap-2">
                <GenericAction
                  v-if="hasPermission('services.create')"
                    title="Nuevo registro"
                    icon="add.png"
                    route="service"
                />

                <GenericAction
                  v-if="hasPermission('clients.consult')"
                    title="Clientes"
                    icon="edit.png"
                    @click="openClientsModal"
                />

                <GenericAction
                  v-if="hasPermission('booths.consult')"
                    title="Casetas"
                    icon="edit.png"
                    @click="openBoothsModal"
                />

                <GenericAction
                  v-if="hasPermission('operators.view')"
                    title="Operadores"
                    icon="edit.png"
                    @click="openDriversModal"
                />

                <GenericAction
                  v-if="hasPermission('units.consult')"
                    title="Unidades"
                    icon="edit.png"
                    @click="openUnitsModal"
                />

                <GenericAction
                  v-if="hasPermission('places.consult')"
                    title="Destinos"
                    icon="edit.png"
                    @click="openPlacesModal"
                />
            </div>

            <ExcelExport
                v-if="hasPermission('services.download')"
                class="w-full md:w-auto md:mx-1"
                endpoint="download/services"
                :fields="['id', 'dispatch_date', 'delivery_date', 'imo', 'type_unit', 'unit', 'containers', 'client', 'destines', 'waybill']"
                :headers="['ID', 'Tomar', 'Posicionar', 'Tipo', 'Tipo Unidad', 'Transporte', 'Ref/Booking', 'Cliente', 'Destino', 'Carta Porte']"
                fileName="viajes.xlsx"
                buttonLabel="Descargar"
                :filters="getCurrentFilters()"
            />

        </div>

        <SegmentedControl
            v-if="hasPermission('services.download')"
            :titles="estados"
            @OnClicked="(i) => filterData(i)"
        />
        

        <router-link 
            v-if="hasPermission('services.create')"
            class="float-button block md:hidden" 
            :to="{ path: 'service' }"
        ></router-link>

        <div 
            v-if="hasPermission('services.download')"
            class="flex justify-end items-center gap-2"
        >
            <input 
                type="checkbox" 
                v-model="dateFilterEnabled"
                class="w-4 h-4 cursor-pointer"
                title="Habilitar/Deshabilitar filtro de fechas"
            />
            <WeekNavigator 
              v-model="dateRange"
              :disabled="!dateFilterEnabled"
            />
            <div>
                <div class="form-item">
                    <select 
                        v-model="operationActual"
                        @change="getByTypeOperation"
                      >
                        <option value="">Todas las Operaciones</option>
                        <option value="1">Importación</option>
                        <option value="2">Exportación</option>
                        <option value="3">Carga Suelta</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- DataTable Component -->
        <DataTable
            :data="items"
            :columns="columns"
            :onReload="handleReload"
            emptyMessage="No hay viajes disponibles."
        >
            <template #actions="{ row }">
                <div class="flex justify-center flex-col md:flex-row">
                  <TableAction
                      v-if="row.evidences && row.evidences.length > 0"
                      title="Ver evidencias"
                      icon="cam.png"
                      @click.prevent="openEvidencesModal(row)"
                  />
                  <TableAction
                      v-if="hasPermission('services.view')"
                      title="Ver historial de subestados"
                      icon="historical.png"
                      @click.prevent="openSubstateHistoryModal(row)"
                  />
                  <TableAction
                      v-if="hasPermission('services.change_substate') && row.state_id >= 2 && row.operator_id == getUserId()"
                      title="Ver gastos"
                      icon="cost.png"
                      @click.prevent="openExpensesModal(row)"
                  />
                  <statebutton v-if="hasPermission('services.change_substate') && row.state_id > 1 && row.cost?.waybill != null" :stateId="row.substate_id" :serviceId="row.id" :typeOperation="row.type_operation == 1? 'impo' : 'expo'"/>
                  <TableAction
                      v-if="hasPermission('services.request_booth') && row.state_id >= 3 && row.state_id < 5"
                      title="Solicitar caseta extra"
                      icon="booth.png"
                      @click.prevent="openBoothModal(row)"
                  />
                  <TableAction
                      v-if="hasPermission('costs.view') && row.state_id <= 2"
                      title="Gastos"
                      icon="cost.png"
                      :route="`cost/${row.id}`"
                  />
                  <TableAction
                      v-if="hasPermission('expenses.view') && row.state_id >= 3 && row.state_id < 5"
                      title="Gastos extras"
                      icon="cost.png"
                      :route="`extras/${row.id}`"
                  />
                  <TableAction
                      v-if="hasAnyPermission(['services.assign', 'services.assign_diesel'])"
                      title="Asignar viaje"
                      icon="car_tag.png"
                      :route="`service/${row.id}`"
                  />
                  <TableAction
                      v-if="hasPermission('services.edit') && row.state_id == 1"
                      title="Editar"
                      icon="edit.png"
                      :route="`service/${row.id}`"
                  />
                  <TableAction
                      v-if="hasPermission('services.reassign') && row.state_id > 3 && row.state_id < 5"
                      title="Reasignar"
                      icon="assign.png"
                      @click.prevent="openModal(row.id)"
                    />
                  <TableAction
                      v-if="hasPermission('services.request_diesel') && row.state_id >= 3 && row.state_id < 5"
                      title="Solicitar mas diesel"
                      icon="diesel.png"
                      @click.prevent="openDieselModal(row)"
                  />
                  <TableAction
                      v-if="hasPermission('services.cancel') && (row.state_id < 5 && approved(row.approvals_map, 'initial_diesel_required'))"
                      title="Cancelar"
                      icon="cancel.png"
                      @click.prevent="cancelItem(row.id)"
                  />
                  <TableAction
                      v-if="hasPermission('services.commission')"
                      title="Comisión"
                      icon="comission.png"
                      @click.prevent="setCommission(row)"
                  />
                </div>
            </template>
        </DataTable>
    </div>

  <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
    <div class="bg-white p-4 rounded-md w-full max-w-lg">
      <h3 class="text-xl font-bold mb-4">Reasignar servicio</h3>

      <div class="form-item">
        <label>Operador:</label>
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

      <div class="flex justify-end gap-2 mt-4">
        <button @click="showModal = false" class="form-button bg-[#6e7881]">Cancelar</button>
        <button @click="confirmReassign" class="form-button">Aceptar</button>
      </div>
    </div>
  </div>

  <div v-if="showDieselModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
    <div class="bg-white p-4 rounded-md w-full max-w-lg">
      <h3 class="text-xl font-bold mb-4">Solicitar diesel extra</h3>

      <template v-if="approved(currentService.approvals_map, 'extra_diesel')">
        <div class="form-item">
          <label>Diesel Requerido:</label>
          <input v-model="modalDieselForm.amount" type="number" min="0" required />
        </div>
      </template>
      <p v-else>
        Ya tiene una solicitud de diesel extra pendiente para este viaje
      </p>

      <div class="flex justify-end gap-2 mt-4">
          <button @click="showDieselModal = false" class="form-button bg-[#6e7881]">
            <span v-if="!approved(currentService.approvals_map, 'extra_diesel')" >Entendido</span>
            <span v-else>Cancelar</span>
          </button>
          <button v-if="approved(currentService.approvals_map, 'extra_diesel')" @click="confirmRequestDiesel" class="form-button">Solicitar</button>
      </div>
    </div>
  </div>

  <div v-if="showBoothModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
    <div class="bg-white p-4 rounded-md w-full max-w-lg">
      <h3 class="text-xl font-bold mb-4">Solicitar caseta extra</h3>

      <template v-if="approved(currentService.approvals_map, 'extra_booth')">
        <div class="form-item">
          <label>Seleccione una caseta:</label>
          <select v-model="modalBoothForm.booth_id" required>
            <option value="" disabled>-- Seleccione una caseta --</option>
            <option v-for="booth in booths" :key="booth.id" :value="booth.id">
              {{ booth.name }} - ${{ Number(booth.cost).toFixed(2) }}
            </option>
          </select>
        </div>
      </template>
      <p v-else>
        Ya tiene una solicitud de caseta extra pendiente para este viaje
      </p>

      <div class="flex justify-end gap-2 mt-4">
          <button @click="showBoothModal = false" class="form-button bg-[#6e7881]">
            <span v-if="!approved(currentService.approvals_map, 'extra_booth')" >Entendido</span>
            <span v-else>Cancelar</span>
          </button>
          <button v-if="approved(currentService.approvals_map, 'extra_booth')" @click="confirmRequestBooth" class="form-button">Solicitar</button>
      </div>
    </div>
  </div>

  <div v-if="showExpensesModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
    <div class="bg-white p-4 rounded-md w-full max-w-lg">
      <div class="flex items-center">
        <h3 class="flex-grow text-xl font-bold mb-4">Gastos Autorizados - {{ expensesData.folio }}</h3>
        <button 
          @click="showExpensesModal = false" 
          class="relative items-center rounded-full border border-[#18364a] text-[#18364a] w-6 h-6"
        >
          <span class="relative -top-1">x</span>
        </button>
      </div>

      <div class="w-full max-h-[400px] overflow-y-auto">
        <table class="table mt-2 text-sm">
          <thead>
            <tr>
              <th>Concepto</th>
              <th class="text-end">Monto</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="expense in expensesData.expenses" :key="expense.id || expense.concept">
              <td><b>{{ expense.concept }}</b></td>
              <td class="text-end">${{ Number(expense.cost).toFixed(2) }}</td>
            </tr>
            <tr class="border-t-2 border-gray-400 font-bold">
              <td><b>TOTAL AUTORIZADO</b></td>
              <td class="text-end text-lg">
                ${{ Number(expensesData.total_authorized).toFixed(2) }}
              </td>
            </tr>
          </tbody>
        </table>

        <p v-if="expensesData.expenses.length === 0" class="text-center py-4 text-gray-500">
          No hay gastos registrados para este viaje
        </p>
      </div>
    </div>
  </div>

  <div v-if="showCommissionModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
    <div class="bg-white p-4 rounded-md w-full max-w-lg">
      <div class="flex items-center">
        <h3 class="flex-grow text-xl font-bold mb-4">Comisión</h3>
        <button 
          @click="showCommissionModal = false" 
          class="relative items-center rounded-full border border-[#18364a] text-[#18364a] w-6 h-6"
        >
          <span class="relative -top-1">x</span>
        </button>
      </div>

      <div class="w-full">
        <div class="form-item">
          <label>Agregar un valor de comisión:</label>
          <input v-model="commissionModalForm.commission" type="number" min="0" required />
        </div>
      </div>

      <div class="flex justify-end gap-2 mt-4">
          <button @click="showCommissionModal = false" class="form-button bg-[#6e7881]">
            <span>Cancelar</span>
          </button>
          <button @click="saveCommission" class="form-button">Guardar</button>
      </div>

    </div>
  </div>
  <clientslistmodal 
      :show="showClientsModal" 
      @close="closeClientsModal"
  />
  <boothslistmodal 
      :show="showBoothsModal" 
      @close="closeBoothsModal"
  />
  <placeslistmodal 
      :show="showPlacesModal" 
      @close="closePlacesModal"
  />
  <driverslistmodal 
      :show="showDriversModal" 
      @close="closeDriversModal"
  />
  <unitslistmodal 
      :show="showUnitsModal" 
      @close="closeUnitsModal"
  />

  <!-- Modal de Historial de Subestados -->
  <div v-if="showSubstateHistoryModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
    <div class="bg-white p-4 rounded-md w-full max-w-2xl">
      <div class="flex items-center">
        <h3 class="flex-grow text-xl font-bold mb-4">
          Historial de Subestados - {{ substateHistoryData.folio }}
        </h3>
        <button 
          @click="showSubstateHistoryModal = false" 
          class="relative items-center rounded-full border border-[#18364a] text-[#18364a] w-6 h-6"
        >
          <span class="relative -top-1">x</span>
        </button>
      </div>

      <div class="w-full max-h-[400px] overflow-y-auto">
        <table class="table mt-2 text-sm">
          <thead>
            <tr>
              <th>Operador</th>
              <th>Subestado</th>
              <th>Fecha</th>
              <th>Hora</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in substateHistoryData.history" :key="item.id">
              <td><b>{{ item.operator_name }}</b></td>
              <td>{{ item.substate_name }}</td>
              <td>{{ item.date }}</td>
              <td>{{ item.time }}</td>
            </tr>
          </tbody>
        </table>

        <p v-if="substateHistoryData.history.length === 0" class="text-center py-4 text-gray-500">
          No hay historial de subestados para este viaje
        </p>
      </div>
    </div>
  </div>

  <!-- Modal de Evidencias -->
  <div v-if="showEvidencesModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
    <div class="bg-white p-4 rounded-md w-full max-w-4xl">
      <div class="flex items-center mb-4">
        <h3 class="flex-grow text-xl font-bold">Evidencias - {{ currentService.folio }}</h3>
        <button 
          @click="showEvidencesModal = false" 
          class="relative items-center rounded-full border border-[#18364a] text-[#18364a] w-6 h-6"
        >
          <span class="relative -top-1">x</span>
        </button>
      </div>

      <!-- Grid de evidencias con scroll -->
      <div class="w-full max-h-[500px] overflow-y-auto">
        <div v-if="currentService.evidences && currentService.evidences.length > 0" 
             class="grid grid-cols-2 md:grid-cols-3 gap-4 p-2">
          <div 
            v-for="evidence in currentService.evidences" 
            :key="evidence.id"
            class="cursor-pointer hover:opacity-80 transition-opacity"
            @click="openImageModal(evidence.file_name)"
          >
            <img 
              :src="`/storage/evidencias/${evidence.file_name}`" 
              :alt="`Evidencia ${evidence.id}`"
              class="w-full h-48 object-cover rounded-md shadow"
            />
          </div>
        </div>
        
        <p v-else class="text-center py-8 text-gray-500">
          No hay evidencias registradas para este viaje
        </p>
      </div>
    </div>
  </div>

  <!-- Modal de Imagen Ampliada -->
  <div v-if="showImageModal" class="fixed inset-0 bg-black bg-opacity-90 flex justify-center items-center z-[60]" @click="showImageModal = false">
    <div class="relative max-w-5xl max-h-screen p-4">
      <button 
        @click="showImageModal = false" 
        class="absolute top-2 right-2 bg-white rounded-full border border-[#18364a] text-[#18364a] w-8 h-8 flex items-center justify-center hover:bg-gray-100"
      >
        <span class="text-xl leading-none">×</span>
      </button>
      <img 
        :src="`/storage/evidencias/${selectedImage}`" 
        alt="Evidencia ampliada"
        class="max-w-full max-h-screen object-contain rounded"
      />
    </div>
  </div>
</template>

<script setup>
import { inject, ref, computed, onMounted, watch } from 'vue';
import { actionslist } from '../composables/actionslist';
import { usePermissions } from '../composables/usePermissions';
import breadcrumb from '../components/breadcrumb.vue';
import DataTable from '@/components/DataTable.vue';
import suggestioninput from '../components/suggestioninput.vue';
import remoteselect from '../components/remoteselect.vue';
import statebutton from '../components/statebutton.vue';
import ExcelExport from '../components/ExcelExport.vue';
import TableAction from '@/components/TableAction.vue';
import GenericAction from '@/components/GenericAction.vue';
import SegmentedControl from '@/components/SegmentedControl.vue';
import WeekNavigator from '@/components/WeekNavigator.vue';
import {approved} from "../plugins/approvals";
import clientslistmodal from "../components/clientslistmodal.vue";
import boothslistmodal from "../components/boothslistmodal.vue";
import placeslistmodal from "../components/placeslistmodal.vue";
import driverslistmodal from "../components/driverslistmodal.vue";
import unitslistmodal from "../components/unitslistmodal.vue";

import axios from "axios";
import { useRoute, useRouter } from 'vue-router';

const dialogs = inject("swal");
const route = useRoute();
const router = useRouter();

// Composable de permisos
const { hasPermission, hasAnyPermission } = usePermissions();

const { items, deleteItem, loadItems, loadFilteredItems } = actionslist({
    endpoint: 'services',
    dialogs
});

// Definir los elementos del breadcrumb
const breadcrumbItems = [
    { title: 'Viajes' } // Último elemento sin path porque es la página actual
];

const operationActual = ref(route.query.operation || "");
const dateRange = ref({ start: '', end: '' });
const dateFilterEnabled = ref(true); // Inicia marcado (filtro activo)

onMounted(async () => {
    // Inicializar dateRange con semana actual si no tiene valores
    if (!dateRange.value.start || !dateRange.value.end) {
        const today = new Date();
        const dayOfWeek = today.getDay();
        const diff = dayOfWeek === 0 ? -6 : 1 - dayOfWeek; // Lunes
        
        const startOfWeek = new Date(today);
        startOfWeek.setDate(today.getDate() + diff);
        
        const endOfWeek = new Date(startOfWeek);
        endOfWeek.setDate(startOfWeek.getDate() + 6); // Domingo
        
        dateRange.value = {
            start: startOfWeek.toISOString().split('T')[0],
            end: endOfWeek.toISOString().split('T')[0]
        };
    }
    
    const filters = {};
    if (route.query.estado) filters.estado = route.query.estado;
    if (route.query.operation) filters.operation = route.query.operation;
    if (dateFilterEnabled.value && dateRange.value.start && dateRange.value.end) {
        filters.start_date = dateRange.value.start;
        filters.end_date = dateRange.value.end;
    }
    await loadFilteredItems(filters);
});

watch(
    () => router.currentRoute.value.query,
    async () => {
        const filters = {};
        if (route.query.estado) filters.estado = route.query.estado;
        if (route.query.operation) filters.operation = route.query.operation;
        if (dateFilterEnabled.value && dateRange.value.start && dateRange.value.end) {
            filters.start_date = dateRange.value.start;
            filters.end_date = dateRange.value.end;
        }
        await loadFilteredItems(filters);
    }
);

watch(dateRange, async (newRange) => {
    if (!dateFilterEnabled.value) return;
    if (!newRange.start || !newRange.end) return;
    
    const filters = {};
    if (route.query.estado) filters.estado = route.query.estado;
    if (route.query.operation) filters.operation = route.query.operation;
    filters.start_date = newRange.start;
    filters.end_date = newRange.end;
    await loadFilteredItems(filters);
}, { deep: true });

watch(dateFilterEnabled, async (newValue) => {
    // Recargar datos con o sin filtro de fechas
    const filters = {};
    if (route.query.estado) filters.estado = route.query.estado;
    if (route.query.operation) filters.operation = route.query.operation;
    
    if (newValue && dateRange.value.start && dateRange.value.end) {
        filters.start_date = dateRange.value.start;
        filters.end_date = dateRange.value.end;
    }
    
    await loadFilteredItems(filters);
});

const handleReload = async () => {
    const filters = {};
    if (route.query.estado) filters.estado = route.query.estado;
    if (route.query.operation) filters.operation = route.query.operation;
    if (dateFilterEnabled.value && dateRange.value.start && dateRange.value.end) {
        filters.start_date = dateRange.value.start;
        filters.end_date = dateRange.value.end;
    }
    await loadFilteredItems(filters);
};


const estadosBase = [
  { id: '1', label: 'Solicitado', icon : 'request.png' },
  { id: '2', label: 'Programado', icon : 'program.png' },
  { id: '3', label: 'En Ruta', icon : 'travel.png' },
  { id: '4', label: 'Carga/Entrega', icon : 'package.png' },
  { id: '5', label: 'Finalizado', icon : 'complete.png' },
  { id: '6', label: 'Cancelado', icon : 'cancel.png' },
  { id: 'todos', label: 'Todos', icon : 'request.png' }
]

const estados = ref([...estadosBase])


const labels = {
  1: 'Carga Entregada',
  2: 'Carga Recibida',
  3: 'Carga Entregada',
  default: 'Carga Entregada/Recibida'
}

const setStateNameByTypeOperation = () => {
  estados.value = [...estadosBase]
  const label = labels[operationActual.value] ?? labels.default
  const index = estados.value.findIndex(e => e.id === '4')
  if (index !== -1) {
    estados.value[index].label = label
  }
}

const getCurrentFilters = () => {
  const filters = {};
  if (route.query.estado) filters.estado = route.query.estado;
  if (operationActual.value) filters.operation = operationActual.value;
  if (dateFilterEnabled.value && dateRange.value.start && dateRange.value.end) {
    filters.start_date = dateRange.value.start;
    filters.end_date = dateRange.value.end;
  }
  return filters;
};


const getByTypeOperation = () => {
  router.push({
    path: "/panel/services",
    query: {
      ...route.query,          
      estado: route.query.estado,
      operation: operationActual.value 
    },
  });
  setStateNameByTypeOperation();
};

const getOperationTypeTitle = (id) => {
    let list = {1 : 'Importación', 2 : 'Exportación', 3 : 'Carga Suelta'};
    return list[id];
};

const getAllRef = (item) => {
    if (!item || !item.containers || !Array.isArray(item.containers)) {
        return '';
    }

    return item.containers
        .map(container => container.order_number)
        .filter(orderNumber => !!orderNumber) 
        .join(', ');
};

const getAllDestine = (item) => {
    if (!item || !item.containers || !Array.isArray(item.containers)) {
        return '';
    }

    return item.containers
        .map(container => container.place.name)
        .join(', ');
    /*return item.containers
        .map(container => container.destine)
        .filter(destine => !!destine) 
        .join(', ');*/
};

const getAllDestineWithAddress = (item) => {
    if (!item || !item.containers || !Array.isArray(item.containers)) {
        return '';
    }
    return item.containers
        .map(container => {
            const placeName = container.place?.name || '';
            const address = container.address || '';
            return address ? `${placeName} (${address})` : placeName;
        })
        .join(', ');
};

// Función auxiliar para obtener el ID del usuario
const getUserId = () => {
    return parseInt(localStorage.getItem('user_id'));
};

const cancelItem = async (id) => {
    const result = await dialogs.fire({
        title: "Cancelar viaje",
        text: "¿Estás seguro(a) que deseas ejecutar esta acción?",
        showCancelButton: true,
        confirmButtonText: "Aceptar",
        cancelButtonText: "Cancelar"
    });

    if (result.isConfirmed) {
        try {
            dialogs.fire({
                title: "Procesando...",
                text: "Por favor, espere",
                allowOutsideClick: false,
                didOpen: () => dialogs.showLoading()
            });

            // Ejecutar la solicitud de cancelación
            await axios.post(`services/cancel/${id}`);
            
            //items.value = items.value.filter(item => item.id !== id);

            dialogs.close();
            dialogs.fire("Excelente!", "El registro ha sido cancelado correctamente.", "success");

            await loadItems();

        } catch (error) {
            dialogs.close();
            if (error.response && error.response.status === 400) {
                errors.value = error.response.data;
            } else {
                console.error("Error cancelando el viaje:", error);
            }
        }
    }
};

const showCommissionModal = ref(false);
const commissionModalForm = ref({
    service_id: 0,
    commission: 0
});

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
    await loadItems();
  } catch (error) {
    console.error(error);
    alert("Error al reasignar");
  }
};


const currentService = ref({});
const showDieselModal = ref(false);
const modalDieselForm = ref({
  amount: 0
});

const showBoothModal = ref(false);
const modalBoothForm = ref({
  booth_id: null
});
const booths = ref([]);

const showExpensesModal = ref(false);
const expensesData = ref({
  expenses: [],
  total_authorized: 0,
  folio: ''
});

const openDieselModal = (current) => {
  currentService.value = current
  selectedId.value = current.id;
  showDieselModal.value = true;
};

const confirmRequestDiesel = async () => {
  if (!modalDieselForm.value.amount) {
    return alert("El campo Diesel requerido es obligatorio");
  }

  try {
    await axios.post(`services/request_diesel/${selectedId.value}`, {
      amount: modalDieselForm.value.amount
    });
    showDieselModal.value = false;
    modalDieselForm.value.amount = 0
    dialogs.fire("Éxito", "Diesel extra solicitado con éxito.", "success");
   // await loadItems();
  } catch (error) {
    console.error(error);
    alert("Error al solicitar el diesel extra");
  }
};

const openBoothModal = async (current) => {
  currentService.value = current;
  selectedId.value = current.id;
  
  // Cargar casetas si aún no están cargadas
  if (booths.value.length === 0) {
    try {
      const { data } = await axios.get('booths');
      booths.value = data;
    } catch (error) {
      console.error("Error cargando casetas:", error);
      alert("Error al cargar el catálogo de casetas");
      return;
    }
  }
  
  showBoothModal.value = true;
};

const confirmRequestBooth = async () => {
  if (!modalBoothForm.value.booth_id) {
    return alert("Debe seleccionar una caseta");
  }

  try {
    await axios.post(`services/request_booth/${selectedId.value}`, {
      booth_id: modalBoothForm.value.booth_id
    });
    showBoothModal.value = false;
    modalBoothForm.value.booth_id = null;
    dialogs.fire("Éxito", "Caseta extra solicitada con éxito.", "success");
  } catch (error) {
    console.error(error);
    alert("Error al solicitar la caseta extra");
  }
};


const filterData = (i) => {
    let estadoParam = i;
    
    // Si es "todos", enviar estados 1,2,3,4,5 (sin Cancelado)
    if (i === 'todos') {
        estadoParam = '1,2,3,4,5';
    }
    
    router.push({
        path: "/panel/services",
        query: {
          estado: estadoParam
        },
    });
}

const openExpensesModal = async (service) => {
  try {
    dialogs.fire({
      title: "Cargando...",
      text: "Por favor, espere",
      allowOutsideClick: false,
      didOpen: () => dialogs.showLoading()
    });

    const { data } = await axios.get(`services/authorized_expenses/${service.id}`);
    
    expensesData.value = data;
    dialogs.close();
    showExpensesModal.value = true;

  } catch (error) {
    dialogs.close();
    console.error("Error cargando gastos:", error);
    await dialogs.fire("Error", "No se pudieron cargar los gastos autorizados.", "error");
  }
};

const setCommission = (service) => {
    commissionModalForm.value = { 
        service_id: service.id, 
        commission: service.commission 
    };
    showCommissionModal.value = true;
}

const saveCommission = async () => {
  if (!commissionModalForm.value.commission) {
    return alert("El campo comisión es obligatorio");
  }

  try {
    await axios.post(`commission/service`, {
      service_id: commissionModalForm.value.service_id,
      commission: commissionModalForm.value.commission
    });
    showCommissionModal.value = false;
   
    items.value.forEach(obj => {
      if (obj.id === commissionModalForm.value.service_id) {
        obj.commission = commissionModalForm.value.commission;
      }
    });

    dialogs.fire("Éxito", "Comsisión agregada con éxito.", "success");
   
  } catch (error) {
    console.error(error);
    alert("Error al asignar comisión");
  }
    
}

const showClientsModal = ref(false);
const showBoothsModal = ref(false);
const showPlacesModal = ref(false);
const showDriversModal = ref(false);
const showUnitsModal = ref(false);

const openClientsModal = () => {
    showClientsModal.value = true;
};

const closeClientsModal = () => {
    showClientsModal.value = false;
};

const openBoothsModal = () => {
    showBoothsModal.value = true;
};

const closeBoothsModal = () => {
    showBoothsModal.value = false;
};

const openPlacesModal = () => {
    showPlacesModal.value = true;
};

const closePlacesModal = () => {
    showPlacesModal.value = false;
};

const openDriversModal = () => {
    showDriversModal.value = true;
};

const closeDriversModal = () => {
    showDriversModal.value = false;
};

const openUnitsModal = () => {
    showUnitsModal.value = true;
};

const closeUnitsModal = () => {
    showUnitsModal.value = false;
};

// Refs y funciones para modal de evidencias
const showEvidencesModal = ref(false);
const showImageModal = ref(false);
const selectedImage = ref('');

// Refs y funciones para modal de historial de subestados
const showSubstateHistoryModal = ref(false);
const substateHistoryData = ref({
  service_id: null,
  folio: '',
  history: []
});

const openEvidencesModal = (service) => {
  currentService.value = service;
  showEvidencesModal.value = true;
};

const openImageModal = (fileName) => {
  selectedImage.value = fileName;
  showImageModal.value = true;
};

const openSubstateHistoryModal = async (service) => {
  try {
    dialogs.fire({
      title: "Cargando...",
      text: "Por favor, espere",
      allowOutsideClick: false,
      didOpen: () => dialogs.showLoading()
    });

    const { data } = await axios.get(`services/${service.id}/substate-history`);
    
    substateHistoryData.value = data;
    dialogs.close();
    showSubstateHistoryModal.value = true;

  } catch (error) {
    dialogs.close();
    console.error("Error cargando historial:", error);
    await dialogs.fire("Error", "No se pudo cargar el historial de subestados.", "error");
  }
};

// Configuración de columnas para el DataTable
const columns = [
    { 
        key: 'id', 
        label: 'ID', 
        sortable: true, 
        filterable: true 
    },
    { 
        key: 'state.name', 
        label: 'Estado', 
        sortable: true, 
        filterable: true,
        formatter: (value) => value || 'N/A'
    },
    { 
        key: 'folio', 
        label: 'Folio', 
        sortable: true, 
        filterable: true 
    },
    { 
        key: 'formatted_dispatch_date', 
        label: 'Tomar', 
        sortable: true, 
        filterable: true 
    },
    { 
        key: 'formatted_delivery_date', 
        label: 'Posicionar', 
        sortable: true, 
        filterable: true 
    },
    { 
        key: 'formatted_imo', 
        label: 'Tipo', 
        sortable: true, 
        filterable: true 
    },
    { 
        key: 'formatted_unit', 
        label: 'Tipo Unidad', 
        sortable: true, 
        filterable: true 
    },
    { 
        key: 'unit.econame', 
        label: 'Transporte', 
        sortable: true, 
        filterable: true,
        formatter: (value) => value || 'N/A'
    },
    { 
        key: 'containers_ref', 
        label: 'Ref/Booking', 
        sortable: true, 
        filterable: true,
        formatter: (value, row) => getAllRef(row)
    },
    { 
        key: 'client.name', 
        label: 'Cliente', 
        sortable: true, 
        filterable: true 
    },
    { 
        key: 'containers_destine', 
        label: 'Destino', 
        sortable: true, 
        filterable: true,
        formatter: (value, row) => getAllDestineWithAddress(row),
        filterValue: (row) => getAllDestine(row)
    },
    { 
        key: 'cost.waybill', 
        label: 'Carta Porte', 
        sortable: true, 
        filterable: true,
        formatter: (value) => value || 'N/A'
    }
];

</script>
