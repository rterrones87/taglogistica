<template>
    <breadcrumb :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 rounded shadow-md">

        <h2 class="text-3xl my-4 font-bold text-center md:text-left">Gastos extras</h2>
    
        <form @submit.prevent="saveItem">
    
            <div class="grid grid-cols-1 gap-2">
                <travelcosts v-model:rows="item.formatted_extras_costs" :booth_costs="0" :type="'EXTRA'" :visible="approved(item.service?.approvals_map, 'extra_expenses')"/>
            </div>
            
            <div v-if="approved(item.service?.approvals_map, 'extra_expenses')" class="flex justify-end border-t mt-8 py-2">
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
    import travelcosts from '../../components/travelcosts.vue';
    import FormAction from '@/components/FormAction.vue';
    import {approved} from "../../plugins/approvals";
    
    const dialogs = inject("swal");

    const { item, isEditing, errors, saveItem } = upsert({
        endpoint: 'extras',
        data: {service_id:0, formatted_extras_costs: []},
        dialogs,
        redirectOnCreate: 'services?estado=3'
    });

    // Definir los elementos del breadcrumb
    const breadcrumbItems = [
        { title: 'Viajes', path: '/panel/services' },
        { title: 'Gastos extras', path: ''} 
    ];

</script>
  
  