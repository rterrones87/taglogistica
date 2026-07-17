<template>
    <breadcrumb :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 rounded shadow-md">

        <div class="flex items-center">
            <h2 class="text-3xl my-4 font-bold grow text-center md:text-left">Pago Operadores</h2>
        </div>

        <!-- DataTable Component -->
        <DataTable
            :data="items"
            :columns="columns"
            :onReload="loadItems"
            emptyMessage="No hay solicitudes disponibles."
        >
            <template #actions="{ row }">
                <div class="flex justify-center flex-col md:flex-row">
                    <TableAction
                        title="Solicitar nómina"
                        icon="cost.png"
                        :route="`operator_payment/${row.operator_id}`"
                    />
                </div>
            </template>
        </DataTable>
    </div>
</template>


<script setup>
import { inject, onMounted } from 'vue';
import { actionslist } from '../composables/actionslist';
import breadcrumb from '../components/breadcrumb.vue';
import DataTable from '@/components/DataTable.vue';
import TableAction from '@/components/TableAction.vue';

const dialogs = inject("swal");

const { items, deleteItem, loadItems } = actionslist({
    endpoint: 'services/weekly-payments/operators',
    dialogs
});

onMounted(() => {
    loadItems();
});

// Definir los elementos del breadcrumb
const breadcrumbItems = [
    { title: 'Pago Operadores' } // Último elemento sin path porque es la página actual
];

// Configuración de columnas para DataTable
const columns = [
    { 
        key: 'operator_id', 
        label: 'ID',
        sortable: true,
        filterable: true
    },
    { 
        key: 'operator', 
        label: 'Nombre operador',
        sortable: true,
        filterable: true
    },
    { 
        key: 'total_services', 
        label: 'No. de Viajes',
        sortable: true,
        filterable: true
    }
];
</script>
