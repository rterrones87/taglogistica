<template>
    <breadcrumb :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 rounded shadow-md">

        <div class="flex items-center">
            <h2 class="text-3xl my-4 font-bold grow text-center md:text-left">Costo Diesel</h2>

            <div class="my-2 flex justify-end hidden md:block">
                <GenericAction
                    title="Nuevo registro"
                    icon="add.png"
                    route="diesel_cost"
                />
            </div>
        </div>

        <router-link class="float-button block md:hidden" :to="{ path: 'diesel_cost' }"></router-link>

        <!-- DataTable Component -->
        <DataTable
            :data="items"
            :columns="columns"
            :onReload="loadItems"
            emptyMessage="No hay costo disponibles."
        />
        <!-- Nota: Esta tabla no tiene acciones, solo muestra información -->
    </div>
</template>


<script setup>
import { inject, onMounted } from 'vue';
import { actionslist } from '../composables/actionslist';
import breadcrumb from '../components/breadcrumb.vue';
import DataTable from '@/components/DataTable.vue';
import GenericAction from '@/components/GenericAction.vue';

const dialogs = inject("swal");

const { items, deleteItem, loadItems } = actionslist({
    endpoint: 'diesel_costs',
    dialogs
});

onMounted(() => {
    loadItems();
});

// Definir los elementos del breadcrumb
const breadcrumbItems = [
    { title: 'Costo Diesel' }
];

// Función helper para formatear moneda
const formatCurrency = (value) => {
    if (value == null || value === '') return '$0.00';
    return `$${Number(value).toFixed(2)}`;
};

// Función helper para formatear fecha
const formatDate = (dateString) => {
    if (!dateString) return '';
    return dateString.substring(0, 10);
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
        key: 'price', 
        label: 'Precio', 
        sortable: true, 
        filterable: true,
        formatter: (value) => formatCurrency(value)
    },
    { 
        key: 'active', 
        label: 'Estado', 
        sortable: true, 
        filterable: true,
        formatter: (value) => value === 1 ? 'Activo' : 'Inactivo'
    },
    { 
        key: 'created_at', 
        label: 'Fecha', 
        sortable: true, 
        filterable: true,
        formatter: (value) => formatDate(value)
    }
];
</script>

