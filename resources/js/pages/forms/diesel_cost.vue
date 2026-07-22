<template>
    <breadcrumb :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 rounded shadow-md">

        <h2 class="text-3xl my-4 font-bold text-center md:text-left">{{ isEditing ? "Editar Precio" : "Nuevo Precio" }}</h2>
    
        <form @submit.prevent="saveItem">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                <div class="form-item">
                    <label>Precio:</label>
                    <input v-model="item.price" type="text" required />
                    <p v-if="errors.price" class="text-red-500 text-sm">{{ errors.price[0] }}</p>
                </div>
            </div>
            <div class="flex justify-end border-t mt-8 py-2">
                <FormAction
                    :title="isEditing ? 'Actualizar' : 'Guardar'"
                />
            </div>
        </form>
    </div>
</template>
  
<script setup>
    import { inject } from "vue";
    import { upsert } from '../../composables/upsert';
    import breadcrumb from '../../components/breadcrumb.vue';
    import FormAction from '@/components/FormAction.vue';

    const dialogs = inject("swal");

    const { item, isEditing, errors, saveItem } = upsert({
        endpoint: 'diesel_cost',
        data: { price: 0 },
        dialogs,
        redirectOnCreate: 'diesel_costs'
    });

    // Definir los elementos del breadcrumb
    const breadcrumbItems = [
        { title: 'Costo Diesel', path: '/panel/diesel_costs' },
        { title: 'Administrar Precio', path: ''} 
    ];
</script>
  