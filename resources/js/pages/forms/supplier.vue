<template>
    <breadcrumb :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 rounded shadow-md">

        <h2 class="text-3xl my-4 font-bold text-center md:text-left">{{ isEditing ? "Editar Proveedor" : "Nuevo Proveedor" }}</h2>
    
        <form @submit.prevent="saveItem">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                <div class="form-item">
                    <label>Tipo de Proveedor:</label>
                    <select v-model="item.type" required>
                        <option value="1">Servicios</option>
                        <option value="2">Piezas</option>
                    </select>
                    <p v-if="errors.type" class="text-red-500 text-sm">{{ errors.type[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Nombre del Proveedor:</label>
                    <input v-model="item.name" type="text" required />
                    <p v-if="errors.name" class="text-red-500 text-sm">{{ errors.name[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Razón Social:</label>
                    <input v-model="item.taxID" type="text" :disabled="disabledFields"/>
                    <p v-if="errors.taxID" class="text-red-500 text-sm">{{ errors.taxID[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Tipo de Persona:</label>
                    <select v-model="item.company_type" required :disabled="disabledFields">
                        <option value="1">Física</option>
                        <option value="2">Moral</option>
                    </select>
                    <p v-if="errors.company_type" class="text-red-500 text-sm">{{ errors.company_type[0] }}</p>
                </div>
                <div class="form-item">
                    <label>RFC:</label>
                    <input v-model="item.RFC" type="text" :disabled="disabledFields"/>
                    <p v-if="errors.RFC" class="text-red-500 text-sm">{{ errors.RFC[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Código Postal:</label>
                    <input v-model="item.zip" type="text" :disabled="disabledFields"/>
                    <p v-if="errors.zip" class="text-red-500 text-sm">{{ errors.zip[0] }}</p>
                </div>
                <label>
                    <input type="checkbox" v-model="invoiceRequired"/>
                    <span class="mx-1">Emite Factura?</span>
                </label>
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
    import { inject, computed, ref } from "vue";
    import { upsert } from '../../composables/upsert';
    import breadcrumb from '../../components/breadcrumb.vue';
    import FormAction from '@/components/FormAction.vue';

    const dialogs = inject("swal");

    const { item, isEditing, errors, saveItem } = upsert({
        endpoint: 'suppliers',
        data: { type: 0, name: "", taxID: "", company_type: 0, RFC: "", zip: "", invoice_required : false },
        dialogs,
        redirectOnCreate: 'suppliers'
    });

    // Definir los elementos del breadcrumb
    const breadcrumbItems = [
        { title: 'Proveedores', path: '/panel/suppliers' },
        { title: 'Administrar Proveedor', path: ''} 
    ];

    const disabledFields = ref(!item.value.invoice_required);

    const invoiceRequired = computed({
        get: () => { 
            disabledFields.value = !item.value.invoice_required;
            return item.value.invoice_required === 1 
        },
        set: (val) => {
            item.value.invoice_required = val ? 1 : 0;
            disabledFields.value = !val;
            if(!val)
            {
                item.value.zip = "";
                item.value.RFC = "";
                item.value.company_type = 0;
                item.value.taxID = "";
            }

        }
    });
</script>
  