<template>
    <breadcrumb :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 rounded shadow-md">

        <div class="flex items-center">
            <h2 class="text-3xl my-4 font-bold grow">Dashboard</h2>

            
        </div>

         
    </div>
</template>


<script setup>
import { ref } from 'vue';
import breadcrumb from '../components/breadcrumb.vue';


// Definir los elementos del breadcrumb
const breadcrumbItems = ref([
    { title: 'Dashboard' } // Último elemento sin path porque es la página actual
]);


</script>


