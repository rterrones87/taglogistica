<template>
    <breadcrumb :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 rounded shadow-md">

        <h2 class="text-3xl my-4 font-bold text-center md:text-left">{{ isEditing ? "Editar Mantenimiento" : "Nuevo Mantenimiento" }}</h2>
    
        <form @submit.prevent="saveItem">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                <div class="form-item">
                    <label>Tipo de Mantenimiento:</label>
                    <remoteselect
                        v-model="item.type_maintenance_id"
                        endpoint="types/maintenances"
                        valueKey="id"
                        textKey="name"
                        :disabled="!approved(item.approvals_map, 'maintenance_expenses')"
                      />
                    <p v-if="errors.type_maintenance_id" class="text-red-500 text-sm">{{ errors.type_maintenance_id[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Unidad:</label>
                    <suggestioninput
                        v-model="item.unit_id"
                        url="units"
                        valueKey="id"
                        textKey="econame"
                        :textValue="item.unit?.econame || ''"
                        :disabled="!approved(item.approvals_map, 'maintenance_expenses')"
                    />
                    <p v-if="errors.unit_id" class="text-red-500 text-sm">{{ errors.unit_id[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Kms:</label>
                    <input v-model="item.kms" type="text" :disabled="!approved(item.approvals_map, 'maintenance_expenses')" required />
                    <p v-if="errors.kms" class="text-red-500 text-sm">{{ errors.kms[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Fecha:</label>
                    <VueDatePicker auto-apply format="yyyy-MM-dd" model-type="yyyy-MM-dd" utc="preserve" :enable-time-picker="false" v-model="item.init_date" :disabled="!approved(item.approvals_map, 'maintenance_expenses')" required/>
                    <p v-if="errors.init_date" class="text-red-500 text-sm">{{ errors.init_date[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Descripcion:</label>
                    <input v-model="item.description" type="text" :disabled="!approved(item.approvals_map, 'maintenance_expenses')" required />
                    <p v-if="errors.description" class="text-red-500 text-sm">{{ errors.description[0] }}</p>
                </div>
            </div>

            <div>
                <h3 class="text-xl my-4 font-bold text-center md:text-left">Solicitud a Proveedor</h3>
        
                <div class="grid grid-cols-1 gap-2">
                    <partssupplierrequest v-model:rows="item.parts_supplier_requests" :disabled="isDisabled"/>
                </div>
            </div>

            <div>
                <h3 class="text-xl my-4 font-bold text-center md:text-left">Solicitud de Inventario</h3>
        
                <div class="grid grid-cols-1 gap-2">
                    <inventoryrequest v-model:rows="item.inventory_requests" :disabled="isDisabled"/>
                </div>
            </div>

            <div  v-if="approved(item.approvals_map, 'maintenance_expenses')"  class="flex justify-end border-t mt-8 py-2">
                <FormAction
                    :title="isEditing ? 'Actualizar' : 'Guardar'"
                />
            </div>
        </form>
    </div>
</template>
  
<script setup>
    import { inject, computed } from "vue";
    import { upsert } from '../../composables/upsert';
    import breadcrumb from '../../components/breadcrumb.vue';
    import remoteselect from '../../components/remoteselect.vue';
    import suggestioninput from '../../components/suggestioninput.vue';
    import inventoryrequest from '../../components/inventoryrequest.vue';
    import partssupplierrequest from '../../components/partssupplierrequest.vue';
    import FormAction from '@/components/FormAction.vue';
    import {approved} from "../../plugins/approvals";
    
    const dialogs = inject("swal");

    const { item, isEditing, errors, saveItem } = upsert({
        endpoint: 'maintenances',
        data: { type_maintenance_id: 0, unit_id: 0, kms: '', init_date: '', description: '', inventory_requests : [], parts_supplier_requests : [] },
        dialogs,
        redirectOnCreate: 'maintenances'
    });

    // Computed property para determinar si los campos deben estar deshabilitados
    const isDisabled = computed(() => !approved(item.value.approvals_map, 'maintenance_expenses'));

    // Definir los elementos del breadcrumb
    const breadcrumbItems = [
        { title: 'Mantenimientos', path: '/panel/maintenances' },
        { title: 'Administrar Mantenimiento', path: ''} 
    ];
</script>
  