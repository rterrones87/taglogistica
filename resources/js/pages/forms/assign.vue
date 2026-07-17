<template>
    <breadcrumb :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 rounded shadow-md">

        <h2 class="text-3xl my-4 font-bold text-center md:text-left">Asignar Viaje</h2>
    
        <!--
        <nav class="tabbar">
            <router-link class="active" :to="{ path: `/panel/service/${item.id}` }">Asignación</router-link>
            <router-link :to="{ path: `/panel/service/historical/${item.id}` }">Historicos</router-link>
        </nav>
        -->
        <SegmentedControl
            :titles="types"
            @OnClicked="(i) => filterData(i)"
        />

        <form @submit.prevent="saveItem">

            <h3 class="text-xl my-4 font-bold text-center md:text-left">Asignación para el flete</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                <div class="form-item">
                    <label>Transporte:</label>
                    <suggestioninput
                    v-model="item.unit_id"
                    url="units"
                    valueKey="id"
                    textKey="econame"
                    :textValue="item.unit?.econame || ''"
                    :disabled="item.state_id == 2 || hasOnlyDieselPermission"
                    />
                    <p v-if="errors.unit_id" class="text-red-500 text-sm">{{ errors.unit_id[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Operador:</label>
                    <remoteselect
                        v-model="item.operator_id"
                        endpoint="operators"
                        valueKey="id"
                        textKey="name"
                        :disabled="item.state_id == 2 || hasOnlyDieselPermission"
                    />
                    <p v-if="errors.operator" class="text-red-500 text-sm">{{ errors.operator[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Diesel Requerido:</label>
                    <input v-model="item.diesel" type="text" :disabled="!canEditDiesel" required />
                    <p v-if="errors.diesel" class="text-red-500 text-sm">{{ errors.diesel[0] }}</p>
                </div>
                
            </div>

            <h3 class="text-xl my-4 font-bold text-center md:text-left">
                Asignación para 
                <span v-if="item.type_operation == 1 || item.type_operation == 3">entrega de vacío</span>
                <span v-if="item.type_operation == 2">recolección de vacio</span>
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                <div class="form-item">
                    <label>Transporte:</label>
                    <suggestioninput
                    v-model="item.aux_unit_id"
                    url="units"
                    valueKey="id"
                    textKey="econame"
                    :textValue="item.aux_unit?.econame || ''"
                    :disabled="hasOnlyDieselPermission"
                    />
                    <!-- :disabled="item.state_id == 2" -->
                    <p v-if="errors.unit_id" class="text-red-500 text-sm">{{ errors.aux_unit_id[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Operador:</label>
                    <remoteselect
                        v-model="item.aux_operator_id"
                        endpoint="operators"
                        valueKey="id"
                        textKey="name"
                        :disabled="hasOnlyDieselPermission"
                    />
                    <!-- :disabled="item.state_id == 2" -->
                    <p v-if="errors.operator" class="text-red-500 text-sm">{{ errors.aux_operator[0] }}</p>
                </div>
            </div>

            <template v-if="item.type_operation == 2">
                <h3 class="text-xl my-4 font-bold text-center md:text-left">
                    Asignación para ingreso de lleno
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                    <div class="form-item">
                        <label>Transporte:</label>
                        <suggestioninput
                        v-model="item.aux2_unit_id"
                        url="units"
                        valueKey="id"
                        textKey="econame"
                        :textValue="item.aux2_unit?.econame || ''"
                        :disabled="hasOnlyDieselPermission"
                        />
                        <!-- :disabled="item.state_id == 2" -->
                        <p v-if="errors.unit_id" class="text-red-500 text-sm">{{ errors.aux2_unit_id[0] }}</p>
                    </div>
                    <div class="form-item">
                        <label>Operador:</label>
                        <remoteselect
                            v-model="item.aux2_operator_id"
                            endpoint="operators"
                            valueKey="id"
                            textKey="name"
                            :disabled="hasOnlyDieselPermission"
                        />
                        <!-- :disabled="item.state_id == 2" -->
                        <p v-if="errors.operator" class="text-red-500 text-sm">{{ errors.aux2_operator[0] }}</p>
                    </div>
                </div>
            </template>

            <h3 class="text-xl my-4 font-bold text-center md:text-left">Contenedores</h3>
    
            <div class="grid grid-cols-1 gap-2">
                <clientcontainers v-if="!hasOnlyDieselPermission" v-model:rows="item.containers" :client_id="item.client_id"/>
                <div v-else class="p-4 bg-gray-100 rounded text-gray-600">
                    <p>No tienes permiso para editar contenedores</p>
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
    import { inject, ref, watch, computed } from "vue";
    import { useRoute, useRouter } from 'vue-router';
    import { upsert } from '../../composables/upsert';
    import { usePermissions } from '../../composables/usePermissions';
    import breadcrumb from '../../components/breadcrumb.vue';
    import clientcontainers from '../../components/clientcontainers.vue';
    import suggestioninput from '../../components/suggestioninput.vue';
    import remoteselect from '../../components/remoteselect.vue';
    import FormAction from '@/components/FormAction.vue';
    import {approved} from "../../plugins/approvals";
    import SegmentedControl from '@/components/SegmentedControl.vue';
    
    const dialogs = inject("swal");
    const route = useRoute();
    const router = useRouter();

    const { item, isEditing, errors, saveItem } = upsert({
        endpoint: 'services',
        data: { unit_id: 0,operator_id: 0, aux_unit_id: 0, aux_operator_id: 0, operator: {name: ""},unit: {econame: ""}, diesel: "", containers: [], state_id: 0, aux_operator: {name: ""}, aux_unit: {econame: ""}, aux_dos_operator: {name: ""}, aux_dos_unit: {econame: ""}},
        dialogs,
        redirectOnCreate: 'services'
    });

    // Definir los elementos del breadcrumb
    const breadcrumbItems = [
        { title: 'Viajes', path: '/panel/services' },
        { title: 'Asignar Viaje', path: ''} 
    ];

    const currentType = ref("1");

    const types = ref([
      { id: '1', label: 'Asignación', icon : 'assign.png'  },
      { id: '2', label: 'Historicos', icon : 'historical.png'  },
    ])

    const filterData = (i) => {
        currentType.value = i;
        router.push({
            path: i==1? `/panel/service/${item.value.id}` : `/panel/service/historical/${item.value.id}`
        });
    }

    // Usar composable de permisos
    const { hasPermission } = usePermissions();

    // Computed property para detectar si el usuario solo tiene permiso de editar diesel
    const hasOnlyDieselPermission = computed(() => {
        const hasAssignDiesel = hasPermission('services.assign_diesel');
        const hasFullAssign = hasPermission('services.assign');
        // Si solo tiene assign_diesel pero NO tiene assign completo
        return hasAssignDiesel && !hasFullAssign;
    });

    // Computed property para saber si el campo diesel puede editarse
    const canEditDiesel = computed(() => {
        // Debe tener al menos uno de los dos permisos
        const hasAnyDieselPermission =  hasPermission('services.assign_diesel');
        // Y la aprobación debe estar en estado que permita edición
        const isApproved = approved(item.value.approvals_map, 'initial_diesel_required');
        return hasAnyDieselPermission && isApproved;
    });

    // Ref para saber si el operator_id ya existía al cargar (modo edición)
    const hadInitialOperator = ref(false);
    
    // Detectar si ya venía con operator_id desde el endpoint
    watch(() => item.value.operator_id, (newOperatorId) => {
        if (newOperatorId && newOperatorId !== 0 && newOperatorId !== "" && newOperatorId !== "0") {
            if (!hadInitialOperator.value) {
                hadInitialOperator.value = true;
            }
        }
    }, { immediate: true });
    
    // Watcher para auto-asignar el chofer principal a los campos auxiliares
    // Solo funciona si NO había operator_id inicial (modo creación)
    watch(() => item.value.operator_id, (newOperatorId) => {
        // Si ya había un operator_id inicial, no hacer nada
        if (hadInitialOperator.value) {
            return;
        }
        
        // Solo asignar en modo creación cuando se selecciona por primera vez
        if (newOperatorId && newOperatorId !== 0 && newOperatorId !== "" && newOperatorId !== "0") {
            item.value.aux_operator_id = newOperatorId;
            item.value.aux2_operator_id = newOperatorId;
            hadInitialOperator.value = true;
        }
    });

</script>
  
  