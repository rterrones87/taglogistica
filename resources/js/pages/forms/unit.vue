<template>
    <breadcrumb v-if="!isModal" :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 rounded shadow-md">

        <h2 class="text-3xl my-4 font-bold text-center md:text-left">{{ isEditing ? "Editar Unidad" : "Nueva Unidad" }}</h2>
    
        <form @submit.prevent="saveItem">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                <div class="form-item">
                    <label>Tipo de Unidad:</label>
                    <select v-model="item.type" :disabled="blockAllExceptTag" required>
                        <option value="1">Tractocamión</option>
                        <option value="2">Remolque</option>
                    </select>
                    <p v-if="errors.type" class="text-red-500 text-sm">{{ errors.type[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Nombre de la Unidad:</label>
                    <input v-model="item.econame" type="text" :disabled="blockAllExceptTag" required />
                    <p v-if="errors.econame" class="text-red-500 text-sm">{{ errors.econame[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Marca de la Unidad:</label>
                    <input v-model="item.brand" type="text" :disabled="blockAllExceptTag || item.type == 2"/>
                    <p v-if="errors.model" class="text-red-500 text-sm">{{ errors.model[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Modelo de la Unidad:</label>
                    <input v-model="item.model" type="text" :disabled="blockAllExceptTag" />
                    <p v-if="errors.model" class="text-red-500 text-sm">{{ errors.model[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Cantidad de Llantas:</label>
                    <input v-model="item.llantas" type="text" :disabled="blockAllExceptTag" />
                    <p v-if="errors.llantas" class="text-red-500 text-sm">{{ errors.llantas[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Tipo de Remolque:</label>
                    <select v-model="item.trailer" :disabled="blockAllExceptTag || item.type == 1">
                        <option value="1">Porta Contenedor</option>
                        <option value="2">Plana</option>
                        <option value="3">Dolly</option>
                        <option value="4">Caja Refrigerada</option>
                    </select>
                    <p v-if="errors.trailer" class="text-red-500 text-sm">{{ errors.trailer[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Número de TAG:</label>
                    <input v-model="item.TAG" type="text" :disabled="!isModal" />
                    <p v-if="errors.TAG" class="text-red-500 text-sm">{{ errors.TAG[0] }}</p>
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
    import { inject, watch  } from "vue";
    import { upsert } from '../../composables/upsert';
    import breadcrumb from '../../components/breadcrumb.vue';
    import FormAction from '@/components/FormAction.vue';

    const props = defineProps({
        isModal: {
            type: Boolean,
            default: false
        },
        idUnit: {
            type: Number | null,
            default: null
        },
        blockAllExceptTag: {
            type: Boolean,
            default: false
        }
    });

    const dialogs = inject("swal");
    const emit = defineEmits(['saved']);

    const { item, isEditing, errors, saveItem } = upsert({
        endpoint: 'units',
        data: { id: props.idUnit, type: 0, econame: "", brand: "", model: "", llantas: 0, trailer: 0, TAG: "" },
        dialogs,
        ignoreRouteParams: props.isModal,
        onCreatedListener: () => {
            emit('saved');
        },
        redirectOnCreate: props.isModal ? null : 'units'
    });

    watch(() => item.value.type, (newType) => {
        // Si es Tractocamión (1), desactiva y limpia "trailer"
        if (newType == 1) {
            item.value.trailer = null;
        }

        // Si es Remolque (2), desactiva y limpia "brand" y "TAG"
        if (newType == 2) {
            item.value.brand = "";
            item.value.TAG = "";
        }
    });

    // Definir los elementos del breadcrumb
    const breadcrumbItems = [
        { title: 'Unidades', path: '/panel/units' },
        { title: 'Administrar Unidad', path: ''} 
    ];
</script>
  