<template>
    <breadcrumb :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 rounded shadow-md">

        <div class="flex items-center">
            <h2 class="text-3xl my-4 font-bold grow text-center md:text-left">Proveedores</h2>

            <div class="my-2 flex justify-end hidden md:block">
                <GenericAction
                    title="Nuevo registro"
                    icon="add.png"
                    route="supplier"
                />
            </div>
        </div>

        <router-link class="float-button block md:hidden" :to="{ path: 'supplier' }"></router-link>

        <!-- DataTable Component -->
        <DataTable
            :data="items"
            :columns="columns"
            :onReload="loadItems"
            emptyMessage="No hay proveedores disponibles."
        >
            <template #actions="{ row }">
                <div class="flex justify-center flex-col md:flex-row">
                    <TableAction
                        title="Editar"
                        icon="edit.png"
                        :route="`supplier/${row.id}`"
                    />
                    <TableAction
                        title="Eliminar"
                        icon="delete.png"
                        @click.prevent="deleteItem(row.id)"
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
import GenericAction from '@/components/GenericAction.vue';

const dialogs = inject("swal");

const { items, deleteItem, loadItems } = actionslist({
    endpoint: 'suppliers',
    dialogs
});

onMounted(() => {
    loadItems();
});

// Definir los elementos del breadcrumb
const breadcrumbItems = [
    { title: 'Proveedores' }
];

// Funciones helper para formatear
const getTypeTitle = (id) => {
    let list = {1 : 'Servicios', 2 : 'Piezas'};
    return list[id] || '';
};

const getPersonTypeTitle = (id) => {
    let list = {1 : 'Física', 2 : 'Moral'};
    return list[id] || '';
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
        key: 'type', 
        label: 'Tipo', 
        sortable: true, 
        filterable: true,
        formatter: (value) => getTypeTitle(value)
    },
    { 
        key: 'name', 
        label: 'Nombre', 
        sortable: true, 
        filterable: true 
    },
    { 
        key: 'company_type', 
        label: 'Persona', 
        sortable: true, 
        filterable: true,
        formatter: (value) => getPersonTypeTitle(value)
    },
    { 
        key: 'RFC', 
        label: 'RFC', 
        sortable: true, 
        filterable: true 
    },
    { 
        key: 'active', 
        label: 'Activo', 
        sortable: true, 
        filterable: true,
        formatter: (value) => value === 1 ? 'Si' : 'No'
    }
];

</script>
