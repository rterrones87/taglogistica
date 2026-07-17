<template>
    <breadcrumb :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 rounded shadow-md">

        <h2 class="text-3xl my-4 font-bold text-center md:text-left">Perfil de usuario</h2>

        <form @submit.prevent="saveItem">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                <label>Nombre:</label>
                    
                <div class="form-item grow">
                    <input v-model="item.name" type="text" disabled />
                </div>
                <label>Perfil:</label>
                    
                <div class="form-item">
                    <input v-model="item.role.name" type="text" disabled/>
                </div>
                <label>Correo Electrónico:</label>
                <div class="form-item">
                    <input v-model="item.email" type="text" disabled/>
                </div>
            </div>
            <h2 class="text-3xl my-4 font-bold text-center md:text-left">Cambiar Contraseña</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                <label>Contraseña:</label>
                    
                <div class="form-item">
                    <input v-model="item.password" type="password" required />
                    <p v-if="errors.password" class="text-red-500 text-sm">{{ errors.password[0] }}</p>
                </div>
                <label>Confirmar Contraseña:</label>
                    
                <div class="form-item">
                    <input v-model="item.password_confirmation" type="password" required />
                </div>
            </div>
            <div class="flex justify-end border-t mt-8 py-2">
                <FormAction
                    title="Actualizar"
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
        endpoint: 'users',
        data: { name: "", email: "", role_id: 0, password: "", password_confirmation: "", role: {name:""} },
        dialogs,
        custom_put: 'password'
    });

    // Definir los elementos del breadcrumb
    const breadcrumbItems = [
        { title: 'Administrar Perfil', path: ''} 
    ];
</script>
  