<template>
    <breadcrumb :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 shadow-md">

        <h2 class="text-3xl my-4 font-bold text-center md:text-left">{{ isEditing ? "Editar Viaje" : "Nuevo Viaje" }}</h2>
    
        <form @submit.prevent="saveItem">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                <div class="form-item">
                    <label>Unidad:</label>
                    <select v-model="item.unit_id" type="text" required>
                        <option value="">Selecciona una opción</option>
                        <option value="1">Unidad de prueba</option>
                    </select>
                    <p v-if="errors.unit_id" class="text-red-500 text-sm">{{ errors.unit_id[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Operador:</label>
                    <input v-model="item.operator" type="text" required />
                    <p v-if="errors.operator" class="text-red-500 text-sm">{{ errors.operator[0] }}</p>
                </div>
            </div>
            <div class="flex justify-end border-t mt-8 py-2">
                <button class="form-button" type="submit">{{ isEditing ? "Actualizar" : "Guardar" }}</button>
            </div>
        </form>
    </div>
</template>
  
<script setup>
    import { inject } from "vue";
    import { upsert } from '../../composables/upsert';
    import breadcrumb from '../../components/breadcrumb.vue';

    const dialogs = inject("swal");

    const { item, isEditing, errors, saveItem } = upsert({
        endpoint: 'travels',
        data: { operator: "", unit_id: 0 },
        dialogs
    });

    // Definir los elementos del breadcrumb
    const breadcrumbItems = [
        { title: 'Viajes', path: '/panel/travels' },
        { title: 'Administrar Viaje', path: ''} 
    ];
</script>
  