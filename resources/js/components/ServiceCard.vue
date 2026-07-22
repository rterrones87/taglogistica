<template>
  <div class="service-card m-4 shadow-md overflow-hidden border-2 border-[#18364a]">
    <!-- Loading state -->
    <div v-if="loading" class="bg-gray-100 p-4 animate-pulse">
      <div class="h-6 bg-gray-300 rounded w-1/3 mb-2"></div>
      <div class="h-4 bg-gray-200 rounded w-1/2"></div>
    </div>
    
    <!-- Error state -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 p-4 rounded">
      <p class="text-red-600 font-semibold">{{ error }}</p>
      <button 
        @click="fetchService" 
        class="mt-2 text-sm text-red-700 hover:text-red-800 underline"
      >
        Reintentar
      </button>
    </div>
    
    <!-- Content -->
    <div v-else-if="service">
      <!-- Header colapsable -->
      <div 
        @click="toggleExpand" 
        :class="headerClasses"
        class="transition-all duration-300"
      >
        <div class="flex items-center gap-2">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
          <span class="font-bold text-lg">Detalles del viaje: #{{ service.folio }}</span>
        </div>
        <svg 
          :class="['w-6 h-6 transition-transform duration-300', isExpanded ? 'rotate-180' : '']"
          fill="none" 
          stroke="currentColor" 
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
      </div>
      
      <!-- Contenido expandible -->
      <div class="expand-wrapper" :class="{ 'is-expanded': isExpanded }">
        <div class="bg-white overflow-hidden">
          <div class="p-4 md:p-6 space-y-6">
            <!-- Información General -->
            <div class="section">
              <h3 class="text-lg font-bold text-[#18364a] mb-3 border-b pb-2">
                Información General
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="info-item">
                  <span class="font-semibold text-gray-700">Cliente:</span>
                  <span class="text-gray-900 ml-2">{{ service.client?.name || 'N/A' }}</span>
                </div>
                <div class="info-item">
                  <span class="font-semibold text-gray-700">Tipo de Operación:</span>
                  <span class="text-gray-900 ml-2">{{ getOperationType(service.type_operation) }}</span>
                </div>
                <div class="info-item md:col-span-2">
                  <span class="font-semibold text-gray-700">Terminal:</span>
                  <span class="text-gray-900 ml-2">{{ service.terminal || 'N/A' }}</span>
                </div>
              </div>
            </div>
            
            <!-- Operadores -->
            <div class="section">
              <h3 class="text-lg font-bold text-[#18364a] mb-3 border-b pb-2">
                Operadores
              </h3>
              <div class="space-y-2">
                <div
                  v-for="so in (service.service_operators || [])"
                  :key="so.service_operator_type_id"
                  class="info-item"
                >
                  <span class="font-semibold text-gray-700">{{ so.type?.name || 'Operador' }}:</span>
                  <span class="text-gray-900 ml-2">{{ so.operator?.name || 'No asignado' }}</span>
                </div>
                <div v-if="!service.service_operators || service.service_operators.length === 0" class="info-item">
                  <span class="text-gray-500 italic">Sin operadores asignados</span>
                </div>
              </div>
            </div>
            
            <!-- Contenedores -->
            <div class="section">
              <h3 class="text-lg font-bold text-[#18364a] mb-3 border-b pb-2">
                Contenedores
              </h3>
              <div v-if="service.containers && service.containers.length > 0" class="overflow-x-auto">
                <table class="table text-sm">
                  <thead>
                    <tr>
                      <th class="text-left">Orden de Compra</th>
                      <th class="text-left">No. Contenedor</th>
                      <th class="text-left">Tipo de Unidad</th>
                      <th class="text-left">Destino</th>
                      <th class="text-left">Dirección</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="container in service.containers" :key="container.id">
                      <td class="font-semibold">{{ container.order_number || 'N/A' }}</td>
                      <td class="font-mono text-gray-800">{{ container.container_number || 'N/A' }}</td>
                      <td>{{ container.container_type || 'N/A' }}</td>
                      <td>{{ container.place?.name || 'N/A' }}</td>
                      <td class="text-sm">{{ container.address || 'N/A' }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <p v-else class="text-gray-500 italic">No hay contenedores registrados</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
  serviceId: {
    type: Number,
    required: true
  }
});

const service = ref(null);
const loading = ref(false);
const error = ref(null);
const isExpanded = ref(false);
const hasAttemptedFetch = ref(false);

const fetchService = async () => {
  // Si no hay serviceId válido, no intentar cargar
  if (!props.serviceId || props.serviceId === 0) {
    hasAttemptedFetch.value = false;
    return;
  }
  
  loading.value = true;
  error.value = null;
  hasAttemptedFetch.value = true;
  
  try {
    const response = await axios.get(`/services/${props.serviceId}`);
    service.value = response.data;
  } catch (err) {
    console.error('Error fetching service:', err);
    
    if (err.response?.status === 404) {
      error.value = 'Servicio no encontrado';
    } else if (err.response?.status === 403) {
      error.value = 'No tienes permisos para ver este servicio';
    } else {
      error.value = 'Error al cargar el servicio. Por favor, intenta de nuevo.';
    }
  } finally {
    loading.value = false;
  }
};

const toggleExpand = () => {
  isExpanded.value = !isExpanded.value;
};

const getOperationType = (type) => {
  const types = {
    1: 'Importación',
    2: 'Exportación',
    3: 'Carga Suelta'
  };
  return types[type] || 'N/A';
};

const headerClasses = computed(() => {
  return [
    'flex justify-between items-center p-4 cursor-pointer',
    isExpanded.value 
      ? 'bg-white text-[#18364a] border-b border-gray-200' 
      : 'bg-[#18364a] text-white'
  ];
});


// Watch para detectar cambios en serviceId
watch(
  () => props.serviceId,
  (newId) => {
    if (newId && newId > 0 && !hasAttemptedFetch.value) {
      fetchService();
    }
  },
  { immediate: true }
);
</script>

<style scoped>
.expand-wrapper {
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.expand-wrapper.is-expanded {
  max-height: 2000px;
}

.info-item {
  @apply flex flex-wrap items-baseline;
}

/* Animación de pulse para loading */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
