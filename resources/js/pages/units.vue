<template>
    <breadcrumb :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 rounded shadow-md">

        <div class="flex items-center">
            <h2 class="text-3xl my-4 font-bold grow text-center md:text-left">Unidades</h2>

            <div class="my-2 flex justify-end hidden md:block">
                <GenericAction
                    title="Nuevo registro"
                    icon="add.png"
                    route="unit"
                />
            </div>
        </div>
        
        <router-link class="float-button block md:hidden" :to="{ path: 'unit' }"></router-link>

        <!-- DataTable Component -->
        <DataTable
            :data="items"
            :columns="columns"
            :onReload="loadItems"
            emptyMessage="No hay unidades disponibles."
        >
            <template #actions="{ row }">
                <div class="flex justify-center flex-col md:flex-row">
                    <TableAction
                        title="Editar"
                        icon="edit.png"
                        :route="`unit/${row.id}`"
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
    endpoint: 'units',
    dialogs
});

onMounted(() => {
    loadItems();
});

// Definir los elementos del breadcrumb
const breadcrumbItems = [
    { title: 'Unidades' } 
];

// Función helper para formatear tipo de unidad
const getTypeTitle = (id) => {
    let list = {1 : 'Tractocamión', 2 : 'Remolque'};
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
        key: 'econame', 
        label: 'Nombre', 
        sortable: true, 
        filterable: true 
    },
    { 
        key: 'brand', 
        label: 'Marca', 
        sortable: true, 
        filterable: true 
    },
    { 
        key: 'model', 
        label: 'Modelo', 
        sortable: true, 
        filterable: true 
    },
    { 
        key: 'TAG', 
        label: 'TAG', 
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

