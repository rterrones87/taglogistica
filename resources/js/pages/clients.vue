<template>
    <breadcrumb :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 rounded shadow-md">

        <div class="flex items-center">
            <h2 class="text-3xl my-4 font-bold grow text-center md:text-left">Clientes</h2>

            <div v-if="hasPermission('clients.create')" class="my-2 flex justify-end hidden md:block">
                <GenericAction
                    title="Nuevo registro"
                    icon="add.png"
                    route="client"
                />
            </div>
        </div>
        
        <router-link class="float-button block md:hidden" :to="{ path: 'client' }"></router-link>

        <!-- DataTable Component -->
        <DataTable
            :data="items"
            :columns="columns"
            :onReload="loadItems"
            emptyMessage="No hay clientes disponibles."
        >
            <template #actions="{ row }">
                <div class="flex justify-center flex-col md:flex-row">
                    <TableAction
                        v-if="hasPermission('clients.edit')"
                        title="Editar"
                        icon="edit.png"
                        :route="`client/${row.id}`"
                    />
                    <TableAction
                        v-if="hasPermission('clients.delete')"
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
import { usePermissions } from '../composables/usePermissions';
import breadcrumb from '../components/breadcrumb.vue';
import DataTable from '@/components/DataTable.vue';
import TableAction from '@/components/TableAction.vue';
import GenericAction from '@/components/GenericAction.vue';

const dialogs = inject("swal");

// Composable de permisos
const { hasPermission } = usePermissions();

const { items, deleteItem, loadItems } = actionslist({
    endpoint: 'clients',
    dialogs
});

onMounted(() => {
    loadItems();
});

// Definir los elementos del breadcrumb
const breadcrumbItems = [
    { title: 'Clientes' }
];

// Helper function para formatear tipo de persona
const getPersonTypeTitle = (id) => {
    let list = {1 : 'Física', 2 : 'Moral'};
    return list[id];
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
        key: 'contact_name', 
        label: 'Contacto', 
        sortable: true, 
        filterable: true 
    },
    { 
        key: 'contact_email', 
        label: 'Email Contacto', 
        sortable: true, 
        filterable: true 
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
        formatter: (value) => value === 1 ? 'Activo' : 'Inactivo'
    }
];

</script>
