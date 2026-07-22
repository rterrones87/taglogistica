<template>
    <breadcrumb :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 rounded shadow-md">

        <div class="flex items-center">
            <h2 class="text-3xl my-4 font-bold grow text-center md:text-left">Clientes</h2>

            <div v-if="!isRole('Logística')" class="my-2 flex justify-end hidden md:block">
                <GenericAction
                    title="Nuevo registro"
                    icon="add.png"
                    route="client"
                />
            </div>
        </div>
        
        <router-link class="float-button block md:hidden" :to="{ path: 'client' }"></router-link>

        <!-- Tabla para mostrar los datos -->
        <table class="table" v-if="items.length > 0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Persona</th>
                    <th>RFC</th>
                    <th>Activo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="client in items" :key="client.id">
                    <td data-label="ID">{{ client.id }}</td>
                    <td data-label="Nombre">{{ client.name }}</td>
                    <td data-label="Persona">{{ getPersonTypeTitle(client.company_type) }}</td>
                    <td data-label="RFC">{{ client.RFC }}</td>
                    <td data-label="Activo">{{ client.active === 1 ? 'Activo' : 'Inactivo' }}</td>
                    <td>
                        <div v-if="!isRole('Logística')" class="flex justify-center flex-col md:flex-row">
                            <TableAction
                                title="Editar"
                                icon="edit.png"
                                :route="`client/${client.id}`"
                            />
                            <TableAction
                                title="Eliminar"
                                icon="delete.png"
                                @click.prevent="deleteItem(client.id)"
                            />
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Mensaje si no hay clientes -->
        <p class="text-center block py-8" v-else>No hay clientes disponibles.</p>
    </div>
</template>

<script setup>
import { inject } from 'vue';
import { actionslist } from '../composables/actionslist';
import breadcrumb from '../components/breadcrumb.vue';
import TableAction from '@/components/TableAction.vue';
import GenericAction from '@/components/GenericAction.vue';

const dialogs = inject("swal");

const { items, deleteItem } = actionslist({
    endpoint: 'clients',
    dialogs
});

const isRole = (role) => { 
    return role === localStorage.getItem('user_role');
}

// Definir los elementos del breadcrumb
const breadcrumbItems = [
    { title: 'Clientes' } // Último elemento sin path porque es la página actual
];

const getPersonTypeTitle = (id) => {
    let list = {1 : 'Física', 2 : 'Moral'};
    return list[id];
};

</script>
