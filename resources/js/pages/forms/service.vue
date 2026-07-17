<template>
    <breadcrumb :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 rounded shadow-md">

        <h2 class="text-3xl my-4 font-bold text-center md:text-left">{{ isEditing ? "Editar Viaje" : "Nuevo Viaje" }}</h2>
    
        <form @submit.prevent="saveItem">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                <div class="form-item">
                    <label>Cliente</label>
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <suggestioninput
                            v-model="item.client_id"
                            url="clients"
                            valueKey="id"
                            textKey="name"
                            :textValue="item.client.name"
                            :disabled="!canEditServiceFields"
                            />
                        </div>
                    </div>
                    <p v-if="errors.client_id" class="text-red-500 text-sm">{{ errors.client_id[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Tipo de Operación:</label>
                    <select v-model="item.type_operation" type="text" required :disabled="!canEditServiceFields">
                        <option value="1">Importación</option>
                        <option value="2">Exportación</option>
                        <option value="3">Carga Suelta</option>
                    </select>
                    <p v-if="errors.type_operation" class="text-red-500 text-sm">{{ errors.type_operation[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Terminal:</label>
                    <autocompleteinput v-model="item.terminal" url="catalog/terminals" :disabled="!canEditServiceFields"/>
                    <p v-if="errors.terminal" class="text-red-500 text-sm">{{ errors.terminal[0] }}</p>
                </div>
                
                <div class="form-item">
                    <label>Fecha Despacho:</label>
                    <VueDatePicker auto-apply format="yyyy-MM-dd" model-type="yyyy-MM-dd" utc="preserve" :enable-time-picker="false" v-model="item.dispatch_date" :disabled="!canEditServiceFields" />
                    <p v-if="errors.dispatch_date" class="text-red-500 text-sm">{{ errors.dispatch_date[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Fecha Entrega:</label>
                    <VueDatePicker auto-apply format="yyyy-MM-dd" model-type="yyyy-MM-dd" utc="preserve" :enable-time-picker="false" v-model="item.delivery_date" :disabled="!canEditServiceFields" />
                    <p v-if="errors.delivery_date" class="text-red-500 text-sm">{{ errors.delivery_date[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Unidad</label>
                    <UnitTypeSelect
                        v-model="item.type_unit"
                        :disabled="!canEditServiceFields"
                        :required="true"
                    />
                    <p v-if="errors.type_unit" class="text-red-500 text-sm">{{ errors.type_unit[0] }}</p>
                </div>
                <label>
                    <input type="checkbox" v-model="isIMO" :disabled="!canEditServiceFields"/>
                    <span class="mx-1">¿Es IMO?</span>
                </label>
            </div>
            <div>
                <h3 class="text-xl my-4 font-bold text-center md:text-left">Contenedores</h3>
        
                <div class="grid grid-cols-1 gap-2">
                    <clientcontainers 
                        v-model:rows="item.containers" 
                        :client_id="item.client_id" 
                        :errors="errors"
                        :disabled="!canEditServiceFields"
                        :disabledContainerNumber="!canEditContainers"
                    />
                    <p v-if="errors.containers" class="text-red-500 text-sm mt-2">{{ errors.containers[0] }}</p>
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
    import { inject, computed, ref } from "vue";
    import { upsert } from '../../composables/upsert';
    import { usePermissions } from '../../composables/usePermissions';
    import breadcrumb from '../../components/breadcrumb.vue';
    import suggestioninput from '../../components/suggestioninput.vue';
    import autocompleteinput from '../../components/autocompleteinput.vue';
    import UnitTypeSelect from '../../components/UnitTypeSelect.vue';
    import clientcontainers from '../../components/clientcontainers.vue';
    import FormAction from '@/components/FormAction.vue';
    // import clientslistmodal from "../../components/clientslistmodal.vue";

    const dialogs = inject("swal");
    const { hasPermission } = usePermissions();

    const isIMO = computed({
        get: () => item.value.IMO === 1,
        set: (val) => {
            item.value.IMO = val ? 1 : 0;
        }
    });

    const { item, isEditing, errors, saveItem } = upsert({
        endpoint: 'services',
        data: { dispatch_date: "", delivery_date: "", client_id: 0, type_operation: 0, terminal: "", type_unit: "",IMO:0, client: {name: ""}, containers: [], status_id: 0},
        dialogs,
        redirectOnCreate: 'services'
    });

    // Computed property para determinar si se pueden editar los contenedores (número de contenedor)
    const canEditContainers = computed(() => {
        // Si no tiene permiso de edición, no puede editar
        if (!hasPermission('services.edit')) return false;
        
        // Si es nuevo registro, puede editar
        if (!isEditing.value) return true;
        
        // Si está en estado "En Espera" (state_id = 1), puede editar todo
        if (item.value.state_id == 1) return true;
        
        // Excepción especial para exportaciones (type_operation == 2):
        // Pueden editar el número de contenedor en estados 2 (Programado) y 3 (En Ruta)
        if (item.value.type_operation == 2) {
            // En estado "Programado" (state_id = 2), puede editar número de contenedor
            if (item.value.state_id == 2) {
                return true;
            }
            
            // En estado "En Ruta" (state_id = 3), puede editar SOLO si substate_id <= 9
            if (item.value.state_id == 3 && 
                item.value.substate_id != null &&
                item.value.substate_id > 0 && 
                item.value.substate_id <= 9) {
                return true;
            }
        }
        
        return false;
    });

    // Computed property para determinar si se pueden editar los campos generales del servicio
    // (cliente, tipo operación, terminal, fechas, tipo unidad, IMO)
    const canEditServiceFields = computed(() => {
        // Si no tiene permiso de edición, no puede editar
        if (!hasPermission('services.edit')) return false;
        
        // Si es nuevo registro, puede editar
        if (!isEditing.value) return true;
        
        // Solo puede editar campos generales si está en estado "En Espera"
        return item.value.state_id == 1;
    });

    // Definir los elementos del breadcrumb
    const breadcrumbItems = [
        { title: 'Viajes', path: '/panel/services' },
        { title: 'Administrar Viaje', path: ''} 
    ];

    

    
</script>
  