<template>
    <breadcrumb v-if="!isModal" :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 rounded shadow-md">

        <h2 class="text-3xl my-4 font-bold text-center md:text-left">{{ isEditing ? "Editar Cliente" : "Nuevo Cliente" }}</h2>
    
        <form @submit.prevent="saveItem">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                <div class="form-item">
                    <label>Nombre del Cliente:</label>
                    <input v-model="item.name" type="text" required />
                    <p v-if="errors.name" class="text-red-500 text-sm">{{ errors.name[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Tipo de Persona:</label>
                    <select v-model="item.company_type" required>
                        <option value="1">Física</option>
                        <option value="2">Moral</option>
                    </select>
                    <p v-if="errors.company_type" class="text-red-500 text-sm">{{ errors.company_type[0] }}</p>
                </div>
                <div class="form-item">
                    <label>RFC:</label>
                    <input v-model="item.RFC" type="text" required />
                    <p v-if="errors.RFC" class="text-red-500 text-sm">{{ errors.RFC[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Código Postal:</label>
                    <input v-model="item.zip" type="text" />
                    <p v-if="errors.zip" class="text-red-500 text-sm">{{ errors.zip[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Nombre del Contacto:</label>
                    <input v-model="item.contact_name" type="text" />
                    <p v-if="errors.contact_name" class="text-red-500 text-sm">{{ errors.contact_name[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Correo Electrónico:</label>
                    <input v-model="item.contact_email" type="email" />
                    <p v-if="errors.contact_email" class="text-red-500 text-sm">{{ errors.contact_email[0] }}</p>
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
    import { inject, computed } from 'vue';
    import { upsert } from '../../composables/upsert';
    import breadcrumb from '../../components/breadcrumb.vue';
    import FormAction from '@/components/FormAction.vue';
    
    const props = defineProps({
        isModal: {
            type: Boolean,
            default: false
        },
        idClient: {
            type: Number | null,
            default: null
        }
    });


    const dialogs = inject("swal");
    const emit = defineEmits(['saved']);

    const { item: rawItem, isEditing, errors, saveItem } = upsert({
        endpoint: 'clients',
        data: { id: props.idClient, name: "", company_type: 0, RFC: "", zip: "", contact_name: "", contact_email: ""},
        dialogs,
        ignoreRouteParams: props.isModal,
        onCreatedListener: () => {
            emit('saved');
        },
        redirectOnCreate: props.isModal? null : 'clients'
    });
    
    // Proteger item para asegurar que siempre sea un objeto
    const item = computed({
        get() {
            if (typeof rawItem.value !== 'object' || rawItem.value === null) {
                console.error('item corrupto detectado:', rawItem.value);
                rawItem.value = { name: "", company_type: 0, RFC: "", zip: "" };
            }
            return rawItem.value;
        },
        set(value) {
            rawItem.value = value;
        }
    });

    // Breadcrumb personalizado
    const breadcrumbItems = [
        { title: 'Clientes', path: '/panel/clients' },
        { title: 'Administrar Cliente', path: '' }
    ];
</script>
  