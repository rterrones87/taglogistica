<template>
    <breadcrumb :items="breadcrumbItems"/>
    
    <!-- ServiceCard - Información del viaje -->
    <ServiceCard v-if="item.service_id" :serviceId="item.service_id" />
    
    <div class="m-4 bg-white p-4 rounded shadow-md">

        <h2 class="text-3xl my-4 font-bold text-center md:text-left">Gastos</h2>
    
        <form @submit.prevent="saveItem">
            <h3 class="text-xl my-4 font-bold text-center md:text-left">Gastos iniciales del viaje</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2 items-center">
                
                <div class="form-item">
                    <label>Carta porte:</label>
                    <input v-model="item.waybill" type="text" required/>
                    <p v-if="errors.waybill" class="text-red-500 text-sm">{{ errors.waybill[0] }}</p>
                </div>

                <div class="form-item">
                    <label>Precio del viaje:</label>
                    <CurrencyInput
                        v-model="item.travel_cost"
                        :min="1"
                        :disabled="(item.service?.state_id !== 1 && item.service?.state_id !== 2) || !approved(item.service?.approvals_map, 'initial_expenses')"
                        required
                    />
                    <p v-if="errors.travel_cost" class="text-red-500 text-sm">{{ errors.travel_cost[0] }}</p>
                </div>
                
            </div>

            <h3 class="text-xl my-4 font-bold text-center md:text-left">Casetas</h3>
            <div class="grid grid-cols-1 gap-2">
                <destinocasetas 
                    @OnAggregate="handleAggregate"
                    :visible="(item.service?.state_id === 1 || item.service?.state_id === 2) && approved(item.service?.approvals_map, 'initial_expenses')"
                    v-model:rows="item.formatted_destinations"
                />
            </div>

            <h3 class="text-xl my-4 font-bold text-center md:text-left">Gastos del viaje</h3>

            <div class="grid grid-cols-1 gap-2">
                <travelcosts 
                    :visible="(item.service?.state_id === 1 || item.service?.state_id === 2) && approved(item.service?.approvals_map, 'initial_expenses')"
                    v-model:rows="item.formatted_initial_costs" 
                    :booth_costs="item.booth_costs"/>
            </div>
            
            <div v-if="(item.service?.state_id === 1 && approved(item.service?.approvals_map, 'initial_expenses')) || item.service?.state_id >= 2" class="flex justify-end border-t mt-8 py-2">
                <FormAction
                    :title="isEditing ? 'Actualizar' : 'Guardar'"
                />
            </div>
        </form>
    </div>
</template>

 
<script setup>
    import { inject, ref } from "vue";
    import { upsert } from '../../composables/upsert';
    import breadcrumb from '../../components/breadcrumb.vue';
    import travelcosts from '../../components/travelcosts.vue';
    import destinocasetas from '../../components/destinocasetas.vue';
    import FormAction from '@/components/FormAction.vue';
    import CurrencyInput from '@/components/CurrencyInput.vue';
    import ServiceCard from '@/components/ServiceCard.vue';
    import {approved} from "../../plugins/approvals";
    
    const dialogs = inject("swal");
 

    const { item, isEditing, errors, saveItem } = upsert({
        endpoint: 'costs',
        data: {service_id:0, waybill:'', booth_costs:0, travel_cost: 0, formatted_initial_costs: [],  formatted_destinations: []},
        dialogs,
        redirectOnCreate: 'services'
    });

    // Definir los elementos del breadcrumb
    const breadcrumbItems = [
        { title: 'Viajes', path: '/panel/services' },
        { title: 'Gastos', path: ''} 
    ];

    function handleAggregate(data) {
        console.log("hea!!!!!!")
        console.log(data)
    }


</script>
  
  