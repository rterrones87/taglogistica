<template>
    <breadcrumb :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 rounded shadow-md">

        <h2 class="text-3xl my-4 font-bold text-center md:text-left">{{ isEditing ? "Editar Inventario" : "Nuevo Inventario" }}</h2>
    
        <form @submit.prevent="handleSubmit">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                <div class="form-item">
                    <label>Nombre:</label>
                    <input v-model="item.name" type="text" required :disabled="item.id"/>
                    <p v-if="errors.name" class="text-red-500 text-sm">{{ errors.name[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Presentación:</label>
                    <input v-model="item.presentation" type="text" required :disabled="item.id"/>
                    <p v-if="errors.presentation" class="text-red-500 text-sm">{{ errors.presentation[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Marca:</label>
                    <input v-model="item.brand" type="text" required :disabled="item.id"/>
                    <p v-if="errors.brand" class="text-red-500 text-sm">{{ errors.brand[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Cantidad:</label>
                    <input v-model="item.quantity" type="text" required :disabled="item.id"/>
                    <p v-if="errors.quantity" class="text-red-500 text-sm">{{ errors.quantity[0] }}</p>
                </div>
                <div class="form-item" v-if="item.id">
                    <label>Agregar Inventario:</label>
                    <input v-model="item.inventory" type="text" required/>
                    <p v-if="errors.inventory" class="text-red-500 text-sm">{{ errors.inventory[0] }}</p>
                </div>
            </div>
            <div class="flex justify-end border-t mt-8 py-2">
                <FormAction
                    :title="isEditing ? 'Actualizar' : 'Guardar'"
                />
            </div>
        </form>

        <div class="grid grid-cols-1 gap-2">
            <table class="table">
                <thead>
                    <tr>
                        <th>Responsable</th>
                        <th>Cantidad Anterior</th>
                        <th>Entrada/Salida</th>
                        <th>Cantidad Actual</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, index) in item.stocks" :key="index">
                        <td>{{ row.user.name }}</td>
                        <td>{{ row.last_quantity }}</td>
                        <td>{{ row.quantity }}</td>
                        <td>{{ row.new_quantity }}</td>
                        <td>{{ row.date }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>
</template>
  
<script setup>
    import { inject, ref } from "vue";
    import { upsert } from '../../composables/upsert';
    import breadcrumb from '../../components/breadcrumb.vue';
    import FormAction from '@/components/FormAction.vue';
    
    const dialogs = inject("swal");

    const { item, isEditing, errors, saveItem, loadItem } = upsert({
        endpoint: 'inventories',
        data: { name: "", presentation: "", brand: "", quantity: 0, inventory: 0, stocks: [] },
        dialogs,
        redirectOnCreate: 'inventories'
    });

    // Definir los elementos del breadcrumb
    const breadcrumbItems = [
        { title: 'Inventarios', path: '/panel/inventories' },
        { title: 'Administrar Inventario', path: ''} 
    ];

    const handleSubmit = async () => {
        
        item.value.user_id = localStorage.getItem('user_id'); 
        
        await saveItem();
        if(item.value.id > 0)
        {
            await loadItem(item.value.id);
            console.log("se llamo solo en edit");
        }
            
    };

</script>
  