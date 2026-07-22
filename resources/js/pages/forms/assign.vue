<template>
    <breadcrumb :items="breadcrumbItems"/>
    
    <!-- ServiceCard - Información del viaje -->
    <ServiceCard v-if="item.id" :serviceId="item.id" />
    
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

            <!-- Secciones dinámicas por tipo de operador -->
            <div v-for="opType in operatorTypes" :key="opType.id">
                <h3 class="text-xl my-4 font-bold text-center md:text-left">
                    Asignación para {{ opType.name }}
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                    <div class="form-item">
                        <label>Transporte:</label>
                        <suggestioninput
                            v-if="operatorsMap[opType.id]"
                            v-model="operatorsMap[opType.id].unit_id"
                            url="units"
                            valueKey="id"
                            textKey="econame"
                            :textValue="operatorsMap[opType.id].unit_name || ''"
                            :disabled="
                                (opType.is_main  && (item.state_id == 2 || hasOnlyDieselPermission)) ||
                                (!opType.is_main && item.state_id > 4)
                            "
                        />
                    </div>
                <div class="form-item">
                        <label>Operador:</label>
                        <remoteselect
                            v-if="operatorsMap[opType.id]"
                            v-model="operatorsMap[opType.id].operator_id"
                            endpoint="operators"
                            valueKey="id"
                            textKey="name"
                            :disabled="
                                (opType.is_main  && (item.state_id == 2 || hasOnlyDieselPermission)) ||
                                (!opType.is_main && item.state_id > 4)
                            "
                        />
                    </div>
                    <!-- Tarifa: solo para operadores no principales -->
                    <div v-if="!opType.is_main" class="form-item">
                        <label>Tarifa:</label>
                        <select
                            v-if="operatorsMap[opType.id]"
                            v-model="operatorsMap[opType.id].rate_id"
                            class="p-[.40rem] w-full border border-slate-300"
                            :disabled="item.state_id > 4"
                        >
                            <option :value="null">-- Sin tarifa --</option>
                            <option
                                v-for="rate in (opType.rates || [])"
                                :key="rate.id"
                                :value="rate.id"
                            >
                                {{ rate.name }} — ${{ Number(rate.amount).toFixed(2) }}
                            </option>
                        </select>
                    </div>

                    <!-- Diesel extra: solo para operadores no principales -->
                    <div v-if="!opType.is_main" class="form-item">
                        <label>Diesel Extra (litros):</label>
                        <input
                            v-if="operatorsMap[opType.id]"
                            v-model="operatorsMap[opType.id].diesel"
                            type="number"
                            min="0"
                            step="0.01"
                            placeholder="0"
                            class="p-[.40rem] w-full border border-slate-300"
                            :disabled="
                                operatorsMap[opType.id].diesel_status === 'pending' ||
                                operatorsMap[opType.id].diesel_status === 'approved'
                            "
                        />
                        <p v-if="operatorsMap[opType.id]?.diesel_status === 'pending'"
                           class="text-yellow-600 text-sm mt-1">Aprobación pendiente</p>
                        <p v-if="operatorsMap[opType.id]?.diesel_status === 'approved'"
                           class="text-green-600 text-sm mt-1">Aprobado</p>
                        <p v-if="operatorsMap[opType.id]?.diesel_status === 'rejected'"
                           class="text-red-600 text-sm mt-1">Rechazado — puedes re-enviar</p>
                    </div>
                </div>
            </div>

            <!-- Diesel (siempre visible) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mt-2">
                <div class="form-item">
                    <label>Diesel Requerido:</label>
                    <input v-model="item.diesel" type="text" :disabled="!canEditDiesel" />
                    <p v-if="errors.diesel" class="text-red-500 text-sm">{{ errors.diesel[0] }}</p>
                </div>
            </div>

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
    import axios from 'axios';
    import { upsert } from '../../composables/upsert';
    import { usePermissions } from '../../composables/usePermissions';
    import breadcrumb from '../../components/breadcrumb.vue';
    import clientcontainers from '../../components/clientcontainers.vue';
    import suggestioninput from '../../components/suggestioninput.vue';
    import remoteselect from '../../components/remoteselect.vue';
    import FormAction from '@/components/FormAction.vue';
    import ServiceCard from '@/components/ServiceCard.vue';
    import {approved} from "../../plugins/approvals";
    import SegmentedControl from '@/components/SegmentedControl.vue';
    
    const dialogs = inject("swal");
    const route = useRoute();
    const router = useRouter();

    const { item, isEditing, errors, saveItem } = upsert({
        endpoint: 'services',
        data: { diesel: '', containers: [], state_id: 0, serviceOperators: [] },
        dialogs,
        redirectOnCreate: 'services'
    });

    // Catálogo dinámico de tipos de operador
    const operatorTypes = ref([]);
    const operatorsMap  = ref({});

    // Cargar tipos cuando se conoce el type_operation del servicio
    // Nota: Laravel serializa las relaciones en snake_case, por eso usamos
    // item.value.service_operators (no serviceOperators)
    watch(() => item.value.type_operation, async (typeOp) => {
        if (!typeOp) return;
        const { data } = await axios.get(`service-operator-types?type_operation=${typeOp}`);
        operatorTypes.value = data;
        data.forEach(t => {
            const existing = item.value.service_operators?.find(
                so => so.service_operator_type_id == t.id
            );
            // Para auxiliares: buscar diesel previo vinculado a este service_operator
            const dieselRecord = !t.is_main
                ? item.value.diesel_history?.find(
                    d => d.service_operator_id === existing?.id
                  )
                : null;

            operatorsMap.value[t.id] = {
                service_operator_type_id: t.id,
                operator_id:   existing?.operator_id ?? null,
                unit_id:       existing?.unit_id     ?? null,
                unit_name:     existing?.unit?.econame ?? '',
                rate_id:       existing?.rate_id     ?? null,
                diesel:        dieselRecord?.amount  ?? null,
                diesel_status: dieselRecord?.status  ?? null,
            };
        });
    }, { immediate: true });

    // Mantener item.value.operators sincronizado para que el composable upsert lo envíe
    // Incluye rate_id (para amount_bonus) y diesel (para extra_diesel de auxiliares)
    watch(operatorsMap, (newMap) => {
        item.value.operators = Object.values(newMap)
            .filter(op => op.operator_id)
            .map(op => ({
                service_operator_type_id: op.service_operator_type_id,
                operator_id:              op.operator_id,
                unit_id:                  op.unit_id ?? 0,
                rate_id:                  op.rate_id ?? null,
                diesel:                   op.diesel  ?? null,
            }));
    }, { deep: true });

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

    // Watch eliminado: ya no se auto-propaga operator_id a auxiliares
    // La asignación ahora se maneja via operatorsMap dinámico

</script>
  
  