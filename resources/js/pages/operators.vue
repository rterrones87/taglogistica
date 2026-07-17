<template>
    <breadcrumb :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 rounded shadow-md">

        <div class="flex items-center">
            <h2 class="text-3xl my-4 font-bold grow text-center md:text-left">Operadores</h2>

            <div class="my-2 flex justify-end hidden md:block">
                <GenericAction
                    title="Nuevo registro"
                    icon="add.png"
                    route="operator"
                />
            </div>
        </div>

        <router-link class="float-button block md:hidden" :to="{ path: 'operator' }"></router-link>

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
                <tr v-for="operator in items" :key="operator.id">
                    <td data-label="ID">{{ operator.id }}</td>
                    <td data-label="Nombre">{{ operator.name }}</td>
                    <td data-label="Activo">{{ operator.active === 1 ? 'Si' : 'No' }}</td>
                    <td>
                        <div class="flex justify-center flex-col md:flex-row">
                            <TableAction
                                title="Editar"
                                icon="edit.png"
                                :route="`operator/${operator.id}`"
                            />
                            <TableAction
                                title="Eliminar"
                                icon="delete.png"
                                @click.prevent="deleteItem(operator.id)"
                            />
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Mensaje si no hay viajes -->
        <p class="text-center block py-8" v-else>No hay operadores disponibles.</p>
    </div>
</template>


<script setup>
import { inject, ref, onMounted } from 'vue';
import { actionslist } from '../composables/actionslist';
import breadcrumb from '../components/breadcrumb.vue';
import TableAction from '@/components/TableAction.vue';
import GenericAction from '@/components/GenericAction.vue';

const dialogs = inject("swal");

const { items, deleteItem, loadItems } = actionslist({
    endpoint: 'operators',
    dialogs
});

onMounted(() => {
    loadItems();
});

// Definir los elementos del breadcrumb
const breadcrumbItems = [
    { title: 'Operadores' } // Último elemento sin path porque es la página actual
];
</script>


