<template>
    <breadcrumb :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 rounded shadow-md">

        <div class="flex items-center flex-col md:flex-row">
            <h2 class="text-3xl my-4 font-bold grow">Viajes</h2>
            <!-- Controles de navegación de fechas -->
            <WeekNavigator 
              v-model="dateRange"
            />
        </div>

        <!-- Filtros -->
        <div class="w-full mb-6 grid grid-cols-1 md:grid-cols-3 gap-2">
          
            <div class="form-item">
              <label>Cliente</label>
              <suggestioninput 
                v-model="client_id"
                url="clients"
                valueKey="id"
                textKey="name"
                :emptySelection="true"/>
            </div>
            <div class="form-item">
              <label>Operador</label>
              <suggestioninput 
                v-model="operator_id"
                url="operators"
                valueKey="id"
                textKey="name"
                :emptySelection="true"/>
            </div>
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

        <!-- Totales -->
        <ul class="grid grid-cols-1 md:grid-cols-6 gap-3 text-center text-lg bg-[#18364a] p-2 rounded text-white my-2">
          <li class="py-1 md:py-0 border-b border-white/20 md:border-b-0 md:border-r">
            ${{ totalBooths.toFixed(2) }}
            <small class="w-full text-sm block opacity-70">Caseta</small>
            <small class="w-full text-sm block">{{ getPercent(totalBooths) }}%</small>
          </li>
          <li class="py-1 md:py-0 border-b border-white/20 md:border-b-0 md:border-r">
            ${{ totalExpenses.toFixed(2) }}
            <small class="w-full text-sm block opacity-70">Gastos</small>
            <small class="w-full text-sm block">{{ getPercent(totalExpenses) }}%</small>
          </li>
          <li class="py-1 md:py-0 border-b border-white/20 md:border-b-0 md:border-r">
            ${{ totalPayments.toFixed(2) }}
            <small class="w-full text-sm block opacity-70">Sueldos</small>
            <small class="w-full text-sm block">{{ getPercent(totalPayments) }}%</small>
          </li>
          <li class="py-1 md:py-0 border-b border-white/20 md:border-b-0 md:border-r">
            {{ totalDiesel }}
            <small class="w-full text-sm block opacity-70">Lts Diesel</small>
          </li>
          <li class="py-1 md:py-0 border-b border-white/20 md:border-b-0 md:border-r">
            ${{ totalCostDiesel.toFixed(2) }}
            <small class="w-full text-sm block opacity-70">Diesel</small>
            <small class="w-full text-sm block">{{ getPercent(totalCostDiesel) }}%</small>
          </li>
          <li>
            ${{ totalComition.toFixed(2) }}
            <small class="w-full text-sm block opacity-70">Comisión</small>
            <small class="w-full text-sm block">{{ commissionPercent }}%</small>
          </li>
        </ul>

        <ul class="grid grid-cols-1 md:grid-cols-3 gap-3 text-center bg-[#18364a] p-2 rounded text-white text-2xl my-2">
          <li class="py-1 md:py-0 border-b border-white/20 md:border-b-0 md:border-r">
            ${{ totalTravel.toFixed(2) }}
            <small class="w-full block opacity-70">Flete</small>
          </li>
          <li class="py-1 md:py-0 border-b border-white/20 md:border-b-0 md:border-r">
            ${{ utility.toFixed(2) }}
            <small class="w-full block opacity-70">Utilidad</small>
          </li>
          <li>
            {{ rentability.toFixed(1) }}%
            <small class="w-full block opacity-70">Rentabilidad</small>
          </li>
        </ul>

        <!-- Tabla para mostrar los datos -->
        <div v-if="items.length > 0" class="max-w-full overflow-x-auto text-sm">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tomar</th>
                        <th>Posicionar</th>
                        <th>Tipo</th>
                        <th>Transporte</th>
                        <th>Ref/Booking</th>
                        <th>Contenedor</th>
                        <th>Cliente</th>
                        <th>Destino</th>
                        <th>CCP</th>
                        <th>Casetas</th>
                        <th>Gastos</th>
                        <th>Sueldos</th>
                        <th>Diesel Lts</th>
                        <th>Diesel Costo</th>
                        <th>Comisión</th>
                        <th>Flete</th>
                        <th>Utilidad</th>
                        <th>Rentabilidad</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="service in items" :key="service.id">
                        <td data-label="Tomar">{{ service.dispatch_date  }}</td>
                        <td data-label="Posicionar">{{ service.delivery_date  }}</td>
                        <td data-label="Tipo">{{ service.type }}</td>
                        <td data-label="Transporte">{{ service.unit }}</td>
                        <td data-label="Ref/Booking">{{ service.ref }}</td>
                        <td data-label="Contenedor">{{ service.containers }}</td>
                        <td data-label="Cliente">{{ service.client }}</td>
                        <td data-label="Destino">{{ service.destines }}</td>
                        <td data-label="CCP">{{ service.ccp }}</td>
                        <td data-label="Casetas">${{ Number(service.booths).toFixed(2) }}</td>
                        <td data-label="Gastos">${{ Number(service.expenses).toFixed(2) }}</td>
                        <td data-label="Sueldos">${{ Number(service.payments).toFixed(2) }}</td>
                        <td data-label="Diesel Lts">{{ service.diesel }}</td>
                        <td data-label="Diesel Costo">${{ Number(service.diesel_cost).toFixed(2) }}</td>
                        <td data-label="Comisión">${{ Number(service.comition).toFixed(2) }} ({{ Number(service.commission_percent).toFixed(1) }}%)</td>
                        <td data-label="Flete">${{ Number(service.flete).toFixed(2) }}</td>
                        <td data-label="Utilidad">${{ Number(service.utility).toFixed(2) }}</td>
                        <td data-label="Rentabilidad">{{ Number(service.rentability).toFixed(1) }}%</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Gráfico PIE de Costos Operativos -->
        <div v-if="items.length > 0 && pieOperativeCosts" class="w-full mt-8">
            <h3 class="text-xl font-bold mb-4">Distribución de Costos Operativos</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <DoughnutChart :chartData="pieOperativeCosts" />
                </div>
                <div class="flex flex-col justify-center">
                    <div class="space-y-2">
                        <div class="text-sm">
                            <strong>Flete Total:</strong> ${{ (pieOperativeCosts.datasets[0].data.reduce((a, b) => a + (Number(pieOperativeCosts.labels[pieOperativeCosts.datasets[0].data.indexOf(b)]) ? 0 : b), 0) || 0).toFixed(2) }}
                        </div>
                        <div class="text-xs space-y-1 mt-3">
                            <div v-for="(label, idx) in pieOperativeCosts.labels" :key="idx" class="flex items-center">
                                <span class="inline-block w-3 h-3 rounded-full mr-2" :style="{backgroundColor: pieOperativeCosts.datasets[0].backgroundColor[idx]}"></span>
                                <span>{{ label }}: {{ pieOperativeCosts.datasets[0].data[idx] }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mensaje si no hay usuarios -->
        <p class="text-center block py-8" v-else>No hay viajes disponibles.</p>

    </div>
</template>


<script setup>
import { ref, watch, onMounted, computed, inject } from 'vue';
import axios from 'axios';
import breadcrumb from '@/components/breadcrumb.vue';
import suggestioninput from '@/components/suggestioninput.vue';
import WeekNavigator from '@/components/WeekNavigator.vue';
import { DoughnutChart } from 'vue-chart-3';
import { Chart, registerables } from "chart.js";

Chart.register(...registerables);

const dialogs = inject("swal");

// Definir los elementos del breadcrumb
const breadcrumbItems = ref([
    { title: 'Viajes' } // Último elemento sin path porque es la página actual
]);

const items = ref([]);
const pieOperativeCosts = ref(null);

const client_id = ref(null);
const operator_id = ref(null);
const unit_id = ref(null);

const dateRange = ref({ start: '', end: '' })

const getPercent = (v) => {
  const travel = Number(totalTravel.value) || 0
  return travel > 0 ? ((Number(v) / travel) * 100).toFixed(1) : 0
}

const totalBooths = computed(() =>
  (items.value || [])
    .flatMap(d => d.booths || 0)
    .reduce((sum, b) => sum + (Number(b) || 0), 0)
);

const totalExpenses = computed(() =>
  (items.value || [])
    .flatMap(d => d.expenses || 0)
    .reduce((sum, b) => sum + (Number(b) || 0), 0)
);

const totalPayments = computed(() =>
  (items.value || [])
    .flatMap(d => d.payments || 0)
    .reduce((sum, b) => sum + (Number(b) || 0), 0)
);

const totalDiesel = computed(() =>
  (items.value || [])
    .flatMap(d => d.diesel || 0)
    .reduce((sum, b) => sum + (Number(b) || 0), 0)
);

const totalCostDiesel = computed(() =>
  (items.value || [])
    .flatMap(d => d.diesel_cost || 0)
    .reduce((sum, b) => sum + (Number(b) || 0), 0)
);

const totalComition = computed(() =>
  (items.value || [])
    .flatMap(d => d.comition || 0)
    .reduce((sum, b) => sum + (Number(b) || 0), 0)
);

const totalTravel = computed(() =>
  (items.value || [])
    .flatMap(d => d.flete || 0)
    .reduce((sum, b) => sum + (Number(b) || 0), 0)
);

const commissionPercent = computed(() => {
  const travel = Number(totalTravel.value) || 0
  const comition = Number(totalComition.value) || 0
  return travel > 0 ? ((comition / travel) * 100).toFixed(1) : 0
})

const utility = computed(() => {
  const travel = Number(totalTravel.value) || 0
  const comition = Number(totalComition.value) || 0
  const diesel = Number(totalCostDiesel.value) || 0
  const payments = Number(totalPayments.value) || 0
  const expenses = Number(totalExpenses.value) || 0
  const booths = Number(totalBooths.value) || 0

  return travel - (comition + diesel + payments + expenses + booths)
})


const rentability = computed(() => {
  return utility.value > 0 ? ((utility.value/totalTravel.value) * 100) : 0
})

const loadDashboardData = async () => {
  try {
    dialogs.fire({
        title: "Procesando...",
        text: "Por favor, espere",
        allowOutsideClick: false,
        didOpen: () => dialogs.showLoading()
    });

    const params = {
      client_id: client_id.value || '',
      operator_id: operator_id.value || '',
      unit_id: unit_id.value || ''
    }
    
    if (dateRange.value.start && dateRange.value.end) {
      params.start_date = dateRange.value.start
      params.end_date = dateRange.value.end
    }

    const { data } = await axios.get('dashboard/services-details', { params })

    dialogs.close();

    items.value = data.items;
    
    // Procesar datos del PIE chart
    if (data.operativeCostsPie && data.operativeCostsPie.values && data.operativeCostsPie.values.length > 0) {
      pieOperativeCosts.value = {
        labels: data.operativeCostsPie.labels,
        datasets: [
          {
            label: 'Distribución de Costos',
            data: data.operativeCostsPie.values,
            backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#FF000', '#CE0000', '#4BC0C0']
          }
        ]
      }
    } else {
      pieOperativeCosts.value = null
    }
    
  } catch (error) {
    console.error('Error cargando dashboard:', error)
    dialogs.close();
  } 
}


watch([client_id, operator_id, unit_id, dateRange], () => {
  loadDashboardData()
}, { deep: true })

onMounted(loadDashboardData)

</script>
