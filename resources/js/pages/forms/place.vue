<template>
    <breadcrumb v-if="!isModal" :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 rounded shadow-md">

        <h2 class="text-3xl my-4 font-bold text-center md:text-left">{{ isEditing ? "Editar Destino" : "Nuevo Destino" }}</h2>
    
        <form @submit.prevent="saveItem">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                <div class="form-item">
                    <label>Nombre del destino:</label>
                    <input v-model="item.name" type="text" required />
                    <p v-if="errors.name" class="text-red-500 text-sm">{{ errors.name[0] }}</p>
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

    const props = defineProps({
        isModal: {
            type: Boolean,
            default: false
        },
        idPlace: {
            type: Number | null,
            default: null
        }
    });

    const dialogs = inject("swal");
    const emit = defineEmits(['saved']);

    const { item, isEditing, errors, saveItem } = upsert({
        endpoint: 'places',
        data: { id: props.idPlace, name: "" },
        dialogs,
        ignoreRouteParams: props.isModal,
        onCreatedListener: () => {
            emit('saved');
        },
        redirectOnCreate: props.isModal ? null : 'places'
    });

    // Definir los elementos del breadcrumb
    const breadcrumbItems = [
        { title: 'Destinos', path: '/panel/places' },
        { title: 'Administrar Destino', path: ''} 
    ];
</script>
  
