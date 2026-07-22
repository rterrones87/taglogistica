<template>
    <breadcrumb :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 rounded shadow-md">

        <div class="flex items-center flex-col md:flex-row">
            <h2 class="text-3xl my-4 font-bold grow">Mantenimientos</h2>
            <!-- Controles de navegación de fechas -->
            <WeekNavigator 
              v-model="dateRange"
            />
        </div>

        <!-- Filtros -->
        <div class="w-full mb-6 grid grid-cols-1 md:grid-cols-3 gap-2">
          
            <div class="form-item">
              <label>Unidad</label>
              <suggestioninput 
                v-model="unit_id"
                url="units"
                valueKey="id"
                textKey="econame"
                :emptySelection="true"/>
            </div>
            
        </div>

        <!-- Gráficos -->
        <div class="w-full grid grid-cols-1 md:grid-cols-2 gap-6">

            <BarChart 
                :chartData="barServicesNumber" 
            />

            <BarChart 
                :chartData="barServicesAmount" 
            />

            <DoughnutChart 
                :chartData="pieServicesType" 
            />

        </div>

    </div>
</template>


<script setup>
import { ref, watch, onMounted, computed, inject } from 'vue';
import axios from 'axios';
import breadcrumb from '@/components/breadcrumb.vue';
import suggestioninput from '@/components/suggestioninput.vue';
import WeekNavigator from '@/components/WeekNavigator.vue';

import { DoughnutChart, BarChart } from 'vue-chart-3';
import { Chart, registerables } from "chart.js";

Chart.register(...registerables);

const dialogs = inject("swal");

// Definir los elementos del breadcrumb
const breadcrumbItems = ref([
    { title: 'Mantenimientos' } // Último elemento sin path porque es la página actual
]);

const unit_id = ref(null);

const dateRange = ref({ start: '', end: '' })

const barServicesNumber = ref(null)
const barServicesAmount = ref(null)
const pieServicesType = ref(null)

const loadDashboardData = async () => {
  try {
    dialogs.fire({
        title: "Procesando...",
        text: "Por favor, espere",
        allowOutsideClick: false,
        didOpen: () => dialogs.showLoading()
    });

    const params = {
      unit_id: unit_id.value || ''
    }
    
    if (dateRange.value.start && dateRange.value.end) {
      params.start_date = dateRange.value.start
      params.end_date = dateRange.value.end
    }

    const { data } = await axios.get('dashboard/maintenances', { params })

    dialogs.close();

    barServicesNumber.value = {
      labels: data.labels,
      datasets: [
        {
          label: 'Cantidad',
          backgroundColor: '#234053',
          data: data.servicesNumber
        }
      ]
    }

    barServicesAmount.value = {
      labels: data.labels,
      datasets: [
        {
          label: 'Costo',
          backgroundColor: '#234053',
          data: data.servicesAmount
        }
      ]
    }

    pieServicesType.value = {
      labels: data.servicesType.labels,
      datasets: [
        {
          label: 'Cantidad',
          data: data.servicesType.values,
          backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#FF000', '#CE0000']
        }
      ]
    }

  } catch (error) {
    console.error('Error cargando dashboard:', error)
    dialogs.close();
  } 
}


watch([unit_id, dateRange], () => {
  loadDashboardData()
}, { deep: true })

onMounted(loadDashboardData)


</script>
