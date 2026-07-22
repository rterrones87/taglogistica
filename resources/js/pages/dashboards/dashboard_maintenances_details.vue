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

        <!-- Tabla para mostrar los datos -->
        <div v-if="items.length > 0" class="max-w-full overflow-x-auto text-sm">
            <table class="table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Transporte</th>
                        <th>Descripción</th>
                        <th>Kms</th>
                        <th>Partes</th>
                        <th>Costos</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="maintenance in items" :key="maintenance.id">
                        <td data-label="Fecha">{{ maintenance.init_date  }}</td>
                        <td data-label="Tipo">{{ maintenance.type }}</td>
                        <td data-label="Transporte">{{ maintenance.unit }}</td>
                        <td data-label="Descripción">{{ maintenance.description }}</td>
                        <td data-label="Kms">{{ maintenance.kms }}</td>
                        <td data-label="Partes">{{ maintenance.parts }}</td>
                        <td data-label="Costos">${{ Number(maintenance.costs).toFixed(2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mensaje si no hay mantenimientos -->
        <p class="text-center block py-8" v-else>No hay mantenimientos disponibles.</p>

    </div>
</template>


<script setup>
import { ref, watch, onMounted, computed, inject } from 'vue';
import axios from 'axios';
import breadcrumb from '@/components/breadcrumb.vue';
import suggestioninput from '@/components/suggestioninput.vue';
import WeekNavigator from '@/components/WeekNavigator.vue';

const dialogs = inject("swal");

// Definir los elementos del breadcrumb
const breadcrumbItems = ref([
    { title: 'Mantenimientos' } // Último elemento sin path porque es la página actual
]);

const items = ref([]);

const unit_id = ref(null);

const dateRange = ref({ start: '', end: '' })

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

    const { data } = await axios.get('dashboard/maintenances-details', { params })

    dialogs.close();

    items.value = data.items;
    
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
