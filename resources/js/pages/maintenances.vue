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
                    
                    <!-- Botón de estado personalizado -->
                    <manttostatebutton
                        v-if="row.maintenance_status_id > 1"
                        :stateId="row.maintenance_status_id" 
                        :manttoId="row.id"
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
                    </div>
                </div>
            </template>
        </DataTable>
    </div>
</template>


<script setup>
import { inject, ref, computed, onMounted, watch } from 'vue';
import { actionslist } from '../composables/actionslist';
import breadcrumb from '../components/breadcrumb.vue';
import manttostatebutton from '../components/manttostatebutton.vue';
import { useRoute, useRouter } from 'vue-router';
import DataTable from '@/components/DataTable.vue';
import TableAction from '@/components/TableAction.vue';
import GenericAction from '@/components/GenericAction.vue';
import SegmentedControl from '@/components/SegmentedControl.vue';
import WeekNavigator from '@/components/WeekNavigator.vue';
import ExcelExport from '../components/ExcelExport.vue';
import {approved} from "../plugins/approvals";

const route = useRoute();
const router = useRouter();
const dialogs = inject("swal");

const estadosBase = [
  { id: '1', label: 'Solicitado', icon : 'request.png' },
  { id: '2', label: 'Pendiente', icon : 'pending.png' },
  { id: '3', label: 'En proceso', icon : 'overview.png' },
  { id: '4', label: 'Finalizado', icon : 'complete.png' },
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

</script>


