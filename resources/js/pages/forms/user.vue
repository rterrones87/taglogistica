<template>
    <breadcrumb v-if="!isModal" :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 rounded shadow-md">

        <h2 class="text-3xl my-4 font-bold text-center md:text-left">{{ isEditing ? "Editar Usuario" : "Nuevo Usuario" }}</h2>
    
        <form @submit.prevent="saveItem">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                <div class="form-item">
                    <label>Nombre:</label>
                    <input v-model="item.name" type="text" required />
                    <p v-if="errors.name" class="text-red-500 text-sm">{{ errors.name[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Perfil:</label>
                    <select v-model="item.role_id" :disabled="fixedRoleId !== null" required>
                        <option v-for="role in roles" :key="role.id" :value="role.id">
                            {{ role.name }}
                        </option>
                    </select>
                    <p v-if="errors.role_id" class="text-red-500 text-sm">{{ errors.role_id[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Correo Electrónico:</label>
                    <input v-model="item.email" type="text" required />
                    <p v-if="errors.email" class="text-red-500 text-sm">{{ errors.email[0] }}</p>
                </div>
            </div>
            <div v-if="item.id == null" class="grid grid-cols-1 md:grid-cols-3 gap-2">
                <div class="form-item">
                    <label>Contraseña:</label>
                    <input v-model="item.password" type="password" required />
                    <p v-if="errors.password" class="text-red-500 text-sm">{{ errors.password[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Confirmar Contraseña:</label>
                    <input v-model="item.password_confirmation" type="password" required />
                </div>
            </div>
            <div class="flex justify-end border-t mt-8 py-2">
                <FormAction
                    :title="isEditing ? 'Actualizar' : 'Guardar'"
                />
                <!-- <button class="form-button" type="submit">{{ isEditing ? "Actualizar" : "Guardar" }}</button> -->
            </div>
        </form>
    </div>
</template>
  
<script setup>
    import { ref, onMounted, inject, watch } from "vue";
    import { upsert } from '../../composables/upsert';
    import breadcrumb from '../../components/breadcrumb.vue';
    import FormAction from '@/components/FormAction.vue';
    import axios from "axios";

    const props = defineProps({
        isModal: {
            type: Boolean,
            default: false
        },
        idUser: {
            type: Number | null,
            default: null
        },
        fixedRoleId: {
            type: Number | null,
            default: null
        }
    });

    const roles = ref([]);
    const dialogs = inject("swal");
    const emit = defineEmits(['saved']);

    const initialRoleId = props.fixedRoleId !== null ? props.fixedRoleId : 0;

    const { item, isEditing, errors, saveItem } = upsert({
        endpoint: 'users',
        data: { id: props.idUser, name: "", email: "", role_id: initialRoleId, password: "", password_confirmation: "" },
        dialogs,
        ignoreRouteParams: props.isModal,
        onCreatedListener: () => {
            emit('saved');
        },
        redirectOnCreate: props.isModal ? null : 'users'
    });

    // Watch para mantener role_id fijo cuando fixedRoleId está presente
    if (props.fixedRoleId !== null) {
        watch(() => item.value, (newItem) => {
            if (newItem.role_id !== props.fixedRoleId) {
                newItem.role_id = props.fixedRoleId;
            }
        }, { deep: true });
    }

    
    // Definir los elementos del breadcrumb
    const breadcrumbItems = [
        { title: 'Usuarios', path: '/panel/users' },
        { title: 'Administrar Usuario', path: ''} 
    ];
    
    onMounted(async () => {
        await loadRoles();
    });

    const loadRoles = async () => {
        if (roles.value.length === 0) {
            try {
                const response = await axios.get('roles')
                roles.value = response.data
            } catch (error) {
                console.error('Error al cargar roles:', error)
            }
        }
    }
</script>
  