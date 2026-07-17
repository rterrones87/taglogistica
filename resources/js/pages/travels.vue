<template>
    <breadcrumb :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 shadow-md">

        <div class="flex items-center">
            <h2 class="text-3xl my-4 font-bold grow text-center md:text-left">Viajes</h2>

            <div class="my-2 flex justify-end hidden md:block">
                <router-link class="button icon-create" :to="{ path: 'travel' }">Nuevo registro</router-link>
            </div>
        </div>

        <router-link class="float-button block md:hidden" :to="{ path: 'travel' }"></router-link>

        <!-- Tabla para mostrar los datos -->
        <table class="table" v-if="items.length > 0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Activo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="travel in items" :key="travel.id">
                    <td data-label="ID">{{ travel.id }}</td>
                    <td data-label="Nombre">{{ travel.operator }}</td>
                    <td data-label="Activo">{{ travel.zombie === 0 ? 'Si' : 'No' }}</td>
                    <td>
                        <div class="flex justify-center">
                            <router-link class="tb-update" :to="{ path: `travel/${travel.id}` }" title="Editar"></router-link>
                            <button class="tb-delete" @click.prevent="deleteItem(travel.id)" title="Eliminar"></button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Mensaje si no hay viajes -->
        <p class="text-center block py-8" v-else>No hay viajes disponibles.</p>
    </div>
</template>


<script setup>
import { inject, onMounted } from 'vue';
import { actionslist } from '../composables/actionslist';
import breadcrumb from '../components/breadcrumb.vue';

const dialogs = inject("swal");

const { items, deleteItem, loadItems } = actionslist({
    endpoint: 'travels',
    dialogs
});

onMounted(() => {
    loadItems();
});

// Definir los elementos del breadcrumb
const breadcrumbItems = [
    { title: 'Viajes' } // Último elemento sin path porque es la página actual
];
</script>


