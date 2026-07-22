<template>
    <breadcrumb :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 rounded shadow-md">

        <div class="flex items-center">
            <h2 class="text-3xl my-4 font-bold grow text-center md:text-left">Mantenimientos</h2>

            <div class="my-2 flex justify-end hidden md:block">
                <GenericAction
                    title="Nuevo registro"
                    icon="add.png"
                    route="maintenance"
                />
            </div>

            <ExcelExport
                class="w-full md:w-auto md:mx-1"
                endpoint="download/maintenances"
                :fields="['id', 'folio', 'type_maintenance', 'description', 'kms', 'init_date', 'econame', 'name']"
                :headers="['ID', 'Folio', 'Tipo', 'Descripción', 'Kms', 'Fecha', 'Unidad', 'Estatus']"
                fileName="mantenimientos.xlsx"
                buttonLabel="Descargar"
                :filters="getCurrentFilters()"
            />
        </div>

        <router-link class="float-button block md:hidden" :to="{ path: 'maintenance' }"></router-link>

        <SegmentedControl
            :titles="estados"
            @OnClicked="(i) => filterData(i)"
        />

        <!-- Checkbox y WeekNavigator -->
        <div class="flex justify-end items-center gap-2 my-4">
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
        </div>

        <!-- DataTable Component -->
        <DataTable
            :data="items"
            :columns="columns"
            :onReload="handleReload"
            emptyMessage="No hay mantenimientos disponibles."
        >
            <template #actions="{ row }">
                <div class="flex justify-center flex-col md:flex-row gap-2">
                    <!-- Label de rechazado -->
                    <span 
                        v-if="isRejected(row?.approvals_map)"
                        class="inline-flex items-center px-2.5 py-1 text-xs font-semibold text-white bg-red-600 rounded-md"
                    >
                        Rechazado
                    </span>
                    
                    <!-- Ver evidencias -->
                    <TableAction
                        v-if="row.evidences && row.evidences.length > 0"
                        title="Ver evidencias"
                        icon="cam.png"
                        @click.prevent="openEvidencesModal(row)"
                    />

                    <!-- Botón de estado personalizado -->
                    <manttostatebutton
                        v-if="row.maintenance_status_id > 1 && row.maintenance_status_id < 5"
                        :stateId="row.maintenance_status_id" 
                        :manttoId="row.id"
                        :hasEvidencePermission="hasPermission('maintenances.upload_evidence')"
                        :hasEvidences="row.evidences && row.evidences.length > 0"
                    />
                    
                    <!-- Acciones de editar y eliminar -->
                    <div class="flex flex-col md:flex-row">
                        <TableAction
                            title="Editar"
                            icon="edit.png"
                            :route="`maintenance/${row.id}`"
                        />
                        <TableAction
                            v-if="row.maintenance_status_id == 1 && approved(row?.approvals_map, 'maintenance_expenses')"
                            title="Eliminar"
                            icon="delete.png"
                            @click.prevent="deleteItem(row.id)"
                        />
                        <TableAction
                            v-if="row.maintenance_status_id == 1 && hasPermission('maintenances.cancel')"
                            title="Cancelar"
                            icon="delete.png"
                            @click.prevent="cancelMaintenance(row.id)"
                        />
                    </div>
                </div>
            </template>
        </DataTable>
    </div>

    <!-- Modal de Evidencias -->
    <BaseModal
        :show="showEvidencesModal"
        :title="`Evidencias - ${currentMaintenance.folio}`"
        size="4xl"
        @close="showEvidencesModal = false"
    >
        <div v-if="currentMaintenance.evidences && currentMaintenance.evidences.length > 0" 
             class="grid grid-cols-2 md:grid-cols-3 gap-4 p-2">
            <div 
                v-for="evidence in currentMaintenance.evidences" 
                :key="evidence.id"
                class="cursor-pointer hover:opacity-80 transition-opacity"
                @click="openImageModal(evidence.file_name)"
            >
                <img 
                    :src="`/storage/evidencias_mantenimientos/${evidence.file_name}`" 
                    :alt="`Evidencia ${evidence.id}`"
                    class="w-full h-48 object-cover rounded-md shadow"
                />
            </div>
        </div>
        <p v-else class="text-center py-8 text-gray-500">
            No hay evidencias registradas
        </p>
    </BaseModal>

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
                :src="`/storage/evidencias_mantenimientos/${selectedImage}`" 
                alt="Evidencia ampliada"
                class="max-w-full max-h-screen object-contain rounded"
            />
        </div>
    </div>
</template>


<script setup>
import { inject, ref, computed, onMounted, watch } from 'vue';
import { actionslist } from '../composables/actionslist';
import breadcrumb from '../components/breadcrumb.vue';
import manttostatebutton from '../components/manttostatebutton.vue';
import axios from 'axios';
import { useRoute, useRouter } from 'vue-router';
import DataTable from '@/components/DataTable.vue';
import TableAction from '@/components/TableAction.vue';
import GenericAction from '@/components/GenericAction.vue';
import SegmentedControl from '@/components/SegmentedControl.vue';
import WeekNavigator from '@/components/WeekNavigator.vue';
import ExcelExport from '../components/ExcelExport.vue';
import BaseModal from '../components/BaseModal.vue';
import {approved} from "../plugins/approvals";
import { usePermissions } from '../composables/usePermissions';

const route = useRoute();
const router = useRouter();
const dialogs = inject("swal");
const { hasPermission } = usePermissions();

const estadosBase = [
  { id: '1', label: 'Solicitado', icon : 'request.png' },
  { id: '2', label: 'Pendiente', icon : 'pending.png' },
  { id: '3', label: 'En proceso', icon : 'overview.png' },
  { id: '4', label: 'Finalizado', icon : 'complete.png' },
  { id: '5', label: 'Cancelado', icon : 'delete.png' },
  { id: 'todos', label: 'Todos', icon : 'complete.png' }
]

const estados = ref([...estadosBase])

const dateRange = ref({ start: '', end: '' });
const dateFilterEnabled = ref(true); // Inicia marcado (filtro activo)

const { items, deleteItem, loadItems, loadFilteredItems } = actionslist({
    endpoint: 'maintenances',
    dialogs
});

onMounted(async () => {
    // Inicializar dateRange con semana actual si no tiene valores
    if (!dateRange.value.start || !dateRange.value.end) {
        const today = new Date();
        const dayOfWeek = today.getDay();
        const diff = -dayOfWeek; // Domingo (día 0)
        
        const startOfWeek = new Date(today);
        startOfWeek.setDate(today.getDate() + diff);
        
        const endOfWeek = new Date(startOfWeek);
        endOfWeek.setDate(startOfWeek.getDate() + 6); // Sábado
        
        dateRange.value = {
            start: startOfWeek.toISOString().split('T')[0],
            end: endOfWeek.toISOString().split('T')[0]
        };
    }
    
    const filters = {};
    if (route.query.estado) filters.estado = route.query.estado;
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
    filters.start_date = newRange.start;
    filters.end_date = newRange.end;
    await loadFilteredItems(filters);
}, { deep: true });

watch(dateFilterEnabled, async (newValue) => {
    // Recargar datos con o sin filtro de fechas
    const filters = {};
    if (route.query.estado) filters.estado = route.query.estado;
    
    if (newValue && dateRange.value.start && dateRange.value.end) {
        filters.start_date = dateRange.value.start;
        filters.end_date = dateRange.value.end;
    }
    
    await loadFilteredItems(filters);
});

const handleReload = async () => {
    const filters = {};
    if (route.query.estado) filters.estado = route.query.estado;
    if (dateFilterEnabled.value && dateRange.value.start && dateRange.value.end) {
        filters.start_date = dateRange.value.start;
        filters.end_date = dateRange.value.end;
    }
    await loadFilteredItems(filters);
};

// Definir los elementos del breadcrumb
const breadcrumbItems = [
    { title: 'Mantenimientos' }
];

// Función helper para formatear fecha
const formatDate = (dateString) => {
    if (!dateString) return '';
    return dateString.substring(0, 10);
};

// Función helper para obtener el nombre del estatus
const getStatusName = (statusId) => {
    const status = estadosBase.find(e => e.id === String(statusId));
    return status ? status.label : '';
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
        key: 'type_maintenance.name', 
        label: 'Tipo de Mantenimiento', 
        sortable: true, 
        filterable: true
    },
    { 
        key: 'unit.econame', 
        label: 'Unidad', 
        sortable: true, 
        filterable: true
    },
    { 
        key: 'kms', 
        label: 'Kms', 
        sortable: true, 
        filterable: true
    },
    { 
        key: 'description', 
        label: 'Descripción', 
        sortable: true, 
        filterable: true
    },
    { 
        key: 'init_date', 
        label: 'Fecha', 
        sortable: true, 
        filterable: true,
        formatter: (value) => formatDate(value)
    },
    { 
        key: 'maintenance_status_id', 
        label: 'Estatus', 
        sortable: true, 
        filterable: true,
        formatter: (value) => getStatusName(value)
    }
];

const filterData = (i) => {
    router.push({
        path: "/panel/maintenances",
        query: {
          estado: i
        },
    });
}

const getCurrentFilters = () => {
  const filters = {};
  if (route.query.estado) filters.estado = route.query.estado;
  if (dateFilterEnabled.value && dateRange.value.start && dateRange.value.end) {
    filters.start_date = dateRange.value.start;
    filters.end_date = dateRange.value.end;
  }
  return filters;
};

// Función para verificar si una solicitud fue rechazada
const isRejected = (approvalsMap) => {
    if (!approvalsMap) return false;
    return approvalsMap.maintenance_expenses === 'rejected';
}

// Refs y funciones para modal de evidencias
const showEvidencesModal = ref(false);
const showImageModal = ref(false);
const selectedImage = ref('');
const currentMaintenance = ref({ folio: '', evidences: [] });

const openEvidencesModal = (maintenance) => {
    currentMaintenance.value = maintenance;
    showEvidencesModal.value = true;
};

const openImageModal = (fileName) => {
    selectedImage.value = fileName;
    showImageModal.value = true;
};

const cancelMaintenance = async (id) => {
    const result = await dialogs.fire({
        title: "Cancelar mantenimiento",
        text: "¿Estás seguro(a) que deseas cancelar este mantenimiento? La solicitud de aprobación pendiente será eliminada.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, cancelar",
        cancelButtonText: "No",
        confirmButtonColor: "#dc2626"
    });

    if (result.isConfirmed) {
        try {
            dialogs.fire({
                title: "Procesando...",
                text: "Por favor, espere",
                allowOutsideClick: false,
                didOpen: () => dialogs.showLoading()
            });

            await axios.post(`maintenances/cancel/${id}`);
            items.value = items.value.filter(item => item.id !== id);

            dialogs.close();
            dialogs.fire("Excelente!", "El mantenimiento ha sido cancelado.", "success");
        } catch (error) {
            dialogs.close();
            const msg = error.response?.data?.message || "No se pudo cancelar el mantenimiento.";
            dialogs.fire("Error", msg, "error");
        }
    }
};

</script>


