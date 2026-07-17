<template>
  <div class="datatable-container">
    <!-- Header con botón de resincronización y filtros activos -->
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-2">
        <button
          v-if="onReload"
          @click="handleReload"
          :disabled="isReloading"
          class="px-3 py-2 bg-[#18364a] text-white rounded hover:bg-[#234053] disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
          title="Recargar datos"
        >
          <span v-if="isReloading" class="inline-block animate-spin">⟳</span>
          <span v-else>⟳</span>
          <span class="hidden md:inline">Recargar</span>
        </button>
        
        <button
          v-if="hasActiveFilters"
          @click="clearAllFilters"
          class="px-3 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 text-sm"
          title="Limpiar todos los filtros"
        >
          Limpiar filtros ({{ activeFiltersCount }})
        </button>
      </div>
      
      <div class="text-sm text-gray-600">
        {{ paginationInfo }}
      </div>
    </div>

    <!-- Tabla Desktop -->
    <div class="hidden md:block overflow-x-auto">
      <table class="table" v-if="paginatedData.length > 0">
        <thead>
          <tr>
            <th
              v-for="column in columns"
              :key="column.key"
              class="relative"
            >
              <div class="flex items-center justify-between gap-2">
                <!-- Header clickeable para ordenar -->
                <div
                  @click="column.sortable !== false ? toggleSort(column.key) : null"
                  :class="[
                    'flex items-center gap-1 flex-1',
                    column.sortable !== false ? 'cursor-pointer hover:text-[#2691e4]' : ''
                  ]"
                >
                  <span>{{ column.label }}</span>
                  
                  <!-- Iconos de ordenamiento -->
                  <span v-if="column.sortable !== false" class="text-xs">
                    <span v-if="sortColumn === column.key && sortDirection === 'asc'">↑</span>
                    <span v-else-if="sortColumn === column.key && sortDirection === 'desc'">↓</span>
                    <span v-else class="text-gray-400">↕</span>
                  </span>
                </div>
                
                <!-- Botón de filtro -->
                <button
                  v-if="column.filterable !== false"
                  @click.stop="toggleFilter(column.key, $event)"
                  class="relative p-1 hover:bg-gray-200 rounded"
                  :class="{ 'text-[#2691e4]': filters[column.key] && filters[column.key].length > 0 }"
                  title="Filtrar"
                >
                  <span class="text-sm">▼</span>
                  <span
                    v-if="filters[column.key] && filters[column.key].length > 0"
                    class="absolute -top-1 -right-1 bg-[#2691e4] text-white text-xs rounded-full w-4 h-4 flex items-center justify-center"
                  >
                    {{ filters[column.key].length }}
                  </span>
                </button>
              </div>
            </th>
            
            <!-- Columna de acciones -->
            <th v-if="$slots.actions"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row, index) in paginatedData" :key="getRowKey(row, index)">
            <td
              v-for="column in columns"
              :key="column.key"
              :data-label="column.label"
            >
              {{ getFormattedValue(row, column) }}
            </td>
            
            <!-- Slot de acciones -->
            <td v-if="$slots.actions">
              <slot name="actions" :row="row" :index="index"></slot>
            </td>
          </tr>
        </tbody>
      </table>
      
      <p v-else class="text-center block py-8">{{ emptyMessage }}</p>
    </div>

    <!-- Dropdowns con Teleport (fuera del overflow) -->
    <template v-for="column in columns" :key="'dropdown-' + column.key">
      <Teleport to="body">
        <div
          v-if="activeFilter === column.key && dropdownPositions[column.key]"
          v-click-outside="closeFilter"
          class="fixed bg-white border border-gray-300 rounded shadow-lg z-[9999] w-64 max-h-96 overflow-hidden"
          :style="{
            top: dropdownPositions[column.key].top + 'px',
            left: dropdownPositions[column.key].left + 'px'
          }"
        >
          <div class="p-2 border-b border-gray-200">
            <input
              v-model="filterSearch[column.key]"
              type="text"
              placeholder="Buscar..."
              class="w-full px-2 py-1 border border-gray-300 rounded text-sm"
              @click.stop
            />
          </div>
          
          <div class="p-2 border-b border-gray-200">
            <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-gray-50 p-1 rounded">
              <input
                type="checkbox"
                :checked="isAllSelected(column.key)"
                @change="toggleSelectAll(column.key)"
                @click.stop
              />
              <span class="font-semibold text-black">Seleccionar todos</span>
            </label>
          </div>
          
          <div class="max-h-60 overflow-y-auto p-2">
            <label
              v-for="value in getFilteredUniqueValues(column.key)"
              :key="value"
              class="flex text-start text-black items-center gap-2 text-xs cursor-pointer hover:bg-gray-50 p-1 rounded"
            >
              <input
                type="checkbox"
                :checked="isValueSelected(column.key, value)"
                @change="toggleValue(column.key, value)"
                @click.stop
              />
              <span>{{ value || '(vacío)' }}</span>
            </label>
            
            <div v-if="getFilteredUniqueValues(column.key).length === 0" class="text-sm text-gray-500 text-center py-2">
              No se encontraron valores
            </div>
          </div>
          
          <div class="p-2 border-t border-gray-200 flex gap-2">
            <button
              @click.stop="applyFilter(column.key)"
              class="flex-1 px-2 py-1 bg-[#18364a] text-white rounded text-sm hover:bg-[#234053]"
            >
              Aplicar
            </button>
            <button
              @click.stop="clearFilter(column.key)"
              class="flex-1 px-2 py-1 bg-gray-300 text-gray-700 rounded text-sm hover:bg-gray-400"
            >
              Limpiar
            </button>
          </div>
        </div>
      </Teleport>
    </template>

    <!-- Cards Móvil -->
    <div class="md:hidden">
      <div v-if="paginatedData.length > 0" class="space-y-4">
        <div
          v-for="(row, index) in paginatedData"
          :key="getRowKey(row, index)"
          class="bg-white p-4 rounded shadow-md border border-gray-200"
        >
          <div
            v-for="column in columns"
            :key="column.key"
            class="flex justify-between py-2 border-b border-gray-100 last:border-b-0"
          >
            <span class="font-semibold text-gray-600">{{ column.label }}:</span>
            <span class="text-gray-900">{{ getFormattedValue(row, column) }}</span>
          </div>
          
          <!-- Slot de acciones para móvil -->
          <div v-if="$slots.actions" class="mt-4 pt-4 border-t border-gray-200">
            <slot name="actions" :row="row" :index="index"></slot>
          </div>
        </div>
      </div>
      
      <p v-else class="text-center block py-8">{{ emptyMessage }}</p>
    </div>

    <!-- Controles de Paginación -->
    <div v-if="totalPages > 1" class="mt-4 flex flex-col md:flex-row items-center justify-between gap-4">
      <div class="text-sm text-gray-600">
        Mostrando {{ startRecord }}-{{ endRecord }} de {{ filteredDataCount }} registros
      </div>
      
      <div class="flex items-center gap-2">
        <!-- Primera página -->
        <button
          @click="goToPage(1)"
          :disabled="currentPage === 1"
          class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
          title="Primera página"
        >
          «
        </button>
        
        <!-- Página anterior -->
        <button
          @click="goToPage(currentPage - 1)"
          :disabled="currentPage === 1"
          class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
          title="Anterior"
        >
          ‹
        </button>
        
        <!-- Números de página -->
        <button
          v-for="page in visiblePages"
          :key="page"
          @click="page !== '...' ? goToPage(page) : null"
          :class="[
            'px-3 py-1 border rounded',
            page === currentPage
              ? 'bg-[#18364a] text-white border-[#18364a]'
              : page === '...'
              ? 'border-transparent cursor-default'
              : 'border-gray-300 hover:bg-gray-100'
          ]"
        >
          {{ page }}
        </button>
        
        <!-- Página siguiente -->
        <button
          @click="goToPage(currentPage + 1)"
          :disabled="currentPage === totalPages"
          class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
          title="Siguiente"
        >
          ›
        </button>
        
        <!-- Última página -->
        <button
          @click="goToPage(totalPages)"
          :disabled="currentPage === totalPages"
          class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
          title="Última página"
        >
          »
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';

/**
 * Props del componente DataTable
 */
const props = defineProps({
  // Datos a mostrar en la tabla
  data: {
    type: Array,
    required: true,
    default: () => []
  },
  
  // Configuración de columnas
  // Estructura: [{ key: 'id', label: 'ID', sortable: true, filterable: true, formatter: null }]
  columns: {
    type: Array,
    required: true,
    validator: (columns) => {
      return columns.every(col => col.key && col.label);
    }
  },
  
  // Función para recargar datos (opcional)
  onReload: {
    type: Function,
    default: null
  },
  
  // Mensaje cuando no hay datos
  emptyMessage: {
    type: String,
    default: 'No hay registros disponibles.'
  },
  
  // ID único para la tabla (para keys)
  tableId: {
    type: String,
    default: 'datatable'
  }
});

// ============= ESTADO =============
const itemsPerPage = 25; // Constante de 25 registros por página
const currentPage = ref(1);
const sortColumn = ref(null);
const sortDirection = ref(null); // 'asc' | 'desc' | null
const filters = ref({});
const tempFilters = ref({}); // Filtros temporales antes de aplicar
const activeFilter = ref(null);
const filterSearch = ref({});
const isReloading = ref(false);
const dropdownPositions = ref({}); // Posiciones calculadas para los dropdowns

// ============= COMPUTED PROPERTIES =============

/**
 * Obtiene valores únicos de una columna
 */
const uniqueColumnValues = computed(() => {
  const values = {};
  props.columns.forEach(column => {
    if (column.filterable !== false) {
      const uniqueSet = new Set();
      props.data.forEach(row => {
        // Usar filterValue si existe, sino usar el path normal
        const value = column.filterValue
          ? column.filterValue(row)
          : getValueByPath(row, column.key);
        
        // Para el filtro, NO aplicar formatter si filterValue existe
        const filterableValue = column.filterValue
          ? value
          : (column.formatter ? column.formatter(value, row) : value);
        
        uniqueSet.add(String(filterableValue ?? ''));
      });
      values[column.key] = Array.from(uniqueSet).sort();
    }
  });
  return values;
});

/**
 * Datos filtrados
 */
const filteredData = computed(() => {
  let result = [...props.data];
  
  // Aplicar filtros activos
  Object.keys(filters.value).forEach(columnKey => {
    const filterValues = filters.value[columnKey];
    if (filterValues && filterValues.length > 0) {
      const column = props.columns.find(col => col.key === columnKey);
      if (column) {
        result = result.filter(row => {
          // Usar filterValue si existe, sino el path normal
          const value = column.filterValue
            ? column.filterValue(row)
            : getValueByPath(row, columnKey);
          
          // Para matching, NO aplicar formatter si filterValue existe
          const compareValue = column.filterValue
            ? value
            : (column.formatter ? column.formatter(value, row) : value);
          
          return filterValues.includes(String(compareValue ?? ''));
        });
      }
    }
  });
  
  return result;
});

/**
 * Datos filtrados y ordenados
 */
const sortedData = computed(() => {
  if (!sortColumn.value || !sortDirection.value) {
    return filteredData.value;
  }
  
  const column = props.columns.find(col => col.key === sortColumn.value);
  if (!column) return filteredData.value;
  
  const sorted = [...filteredData.value].sort((a, b) => {
    const valueA = getValueByPath(a, sortColumn.value);
    const valueB = getValueByPath(b, sortColumn.value);
    
    // Manejar valores null/undefined (ponerlos al final)
    if (valueA == null && valueB == null) return 0;
    if (valueA == null) return 1;
    if (valueB == null) return -1;
    
    // Comparación según tipo
    let comparison = 0;
    if (typeof valueA === 'number' && typeof valueB === 'number') {
      comparison = valueA - valueB;
    } else {
      comparison = String(valueA).localeCompare(String(valueB), undefined, { numeric: true });
    }
    
    return sortDirection.value === 'asc' ? comparison : -comparison;
  });
  
  return sorted;
});

/**
 * Datos paginados (después de filtrar y ordenar)
 */
const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  const end = start + itemsPerPage;
  return sortedData.value.slice(start, end);
});

/**
 * Total de páginas
 */
const totalPages = computed(() => {
  return Math.ceil(sortedData.value.length / itemsPerPage);
});

/**
 * Páginas visibles en el paginador (máximo 5)
 */
const visiblePages = computed(() => {
  const pages = [];
  const total = totalPages.value;
  const current = currentPage.value;
  
  if (total <= 5) {
    for (let i = 1; i <= total; i++) {
      pages.push(i);
    }
  } else {
    if (current <= 3) {
      pages.push(1, 2, 3, 4, '...', total);
    } else if (current >= total - 2) {
      pages.push(1, '...', total - 3, total - 2, total - 1, total);
    } else {
      pages.push(1, '...', current - 1, current, current + 1, '...', total);
    }
  }
  
  return pages;
});

/**
 * Indica si hay filtros activos
 */
const hasActiveFilters = computed(() => {
  return Object.keys(filters.value).some(key => 
    filters.value[key] && filters.value[key].length > 0
  );
});

/**
 * Cuenta de filtros activos
 */
const activeFiltersCount = computed(() => {
  return Object.keys(filters.value).reduce((count, key) => {
    return count + (filters.value[key]?.length || 0);
  }, 0);
});

/**
 * Información de paginación
 */
const paginationInfo = computed(() => {
  if (sortedData.value.length === 0) return '';
  const start = (currentPage.value - 1) * itemsPerPage + 1;
  const end = Math.min(currentPage.value * itemsPerPage, sortedData.value.length);
  return `${start}-${end} de ${sortedData.value.length}`;
});

/**
 * Número del primer registro mostrado
 */
const startRecord = computed(() => {
  return sortedData.value.length === 0 ? 0 : (currentPage.value - 1) * itemsPerPage + 1;
});

/**
 * Número del último registro mostrado
 */
const endRecord = computed(() => {
  return Math.min(currentPage.value * itemsPerPage, sortedData.value.length);
});

/**
 * Total de registros filtrados
 */
const filteredDataCount = computed(() => {
  return sortedData.value.length;
});

// ============= MÉTODOS =============

/**
 * Obtiene un valor de un objeto por path (soporta propiedades anidadas)
 */
function getValueByPath(obj, path) {
  return path.split('.').reduce((acc, part) => acc?.[part], obj);
}

/**
 * Obtiene el valor formateado de una celda
 */
function getFormattedValue(row, column) {
  const value = getValueByPath(row, column.key);
  if (column.formatter) {
    return column.formatter(value, row);
  }
  return value ?? '';
}

/**
 * Genera key única para cada fila
 */
function getRowKey(row, index) {
  return row.id || `${props.tableId}-row-${index}`;
}

/**
 * Toggle ordenamiento de una columna
 */
function toggleSort(columnKey) {
  if (sortColumn.value === columnKey) {
    // Ciclo: asc -> desc -> null
    if (sortDirection.value === 'asc') {
      sortDirection.value = 'desc';
    } else if (sortDirection.value === 'desc') {
      sortDirection.value = null;
      sortColumn.value = null;
    }
  } else {
    sortColumn.value = columnKey;
    sortDirection.value = 'asc';
  }
}

/**
 * Abre/cierra dropdown de filtro
 */
function toggleFilter(columnKey, event) {
  if (activeFilter.value === columnKey) {
    activeFilter.value = null;
    delete dropdownPositions.value[columnKey];
  } else {
    activeFilter.value = columnKey;
    
    // Calcular posición del botón
    nextTick(() => {
      const button = event.target.closest('button');
      if (button) {
        const rect = button.getBoundingClientRect();
        const dropdownWidth = 256; // 16rem = 256px (w-64)
        
        // Calcular left para que el dropdown termine en el botón
        let left = rect.right - dropdownWidth + window.scrollX;
        
        // Asegurar que no se salga del lado izquierdo de la pantalla
        if (left < 0) {
          left = 8; // 8px de margen desde el borde izquierdo
        }
        
        dropdownPositions.value[columnKey] = {
          top: rect.bottom + 4 + window.scrollY, // 4px de margen
          left: left
        };
      }
    });
    
    // Inicializar filtros temporales con los actuales
    if (!tempFilters.value[columnKey]) {
      tempFilters.value[columnKey] = [...(filters.value[columnKey] || [])];
    }
  }
}

/**
 * Cierra el dropdown de filtro
 */
function closeFilter() {
  activeFilter.value = null;
  filterSearch.value = {};
  dropdownPositions.value = {};
}

/**
 * Obtiene valores únicos filtrados por búsqueda
 */
function getFilteredUniqueValues(columnKey) {
  const values = uniqueColumnValues.value[columnKey] || [];
  const search = filterSearch.value[columnKey]?.toLowerCase() || '';
  
  if (!search) return values;
  
  return values.filter(value => 
    String(value).toLowerCase().includes(search)
  );
}

/**
 * Verifica si todos los valores están seleccionados
 */
function isAllSelected(columnKey) {
  const allValues = uniqueColumnValues.value[columnKey] || [];
  const selected = tempFilters.value[columnKey] || [];
  return allValues.length > 0 && selected.length === allValues.length;
}

/**
 * Verifica si un valor específico está seleccionado
 */
function isValueSelected(columnKey, value) {
  const selected = tempFilters.value[columnKey] || [];
  return selected.includes(value);
}

/**
 * Toggle selección de un valor
 */
function toggleValue(columnKey, value) {
  if (!tempFilters.value[columnKey]) {
    tempFilters.value[columnKey] = [];
  }
  
  const index = tempFilters.value[columnKey].indexOf(value);
  if (index > -1) {
    tempFilters.value[columnKey].splice(index, 1);
  } else {
    tempFilters.value[columnKey].push(value);
  }
}

/**
 * Toggle seleccionar/deseleccionar todos
 */
function toggleSelectAll(columnKey) {
  const allValues = uniqueColumnValues.value[columnKey] || [];
  if (isAllSelected(columnKey)) {
    tempFilters.value[columnKey] = [];
  } else {
    tempFilters.value[columnKey] = [...allValues];
  }
}

/**
 * Aplica el filtro de una columna
 */
function applyFilter(columnKey) {
  filters.value[columnKey] = [...(tempFilters.value[columnKey] || [])];
  closeFilter();
  
  // Si después de filtrar la página actual no existe, volver a página 1
  if (currentPage.value > totalPages.value) {
    currentPage.value = 1;
  }
}

/**
 * Limpia el filtro de una columna
 */
function clearFilter(columnKey) {
  filters.value[columnKey] = [];
  tempFilters.value[columnKey] = [];
  closeFilter();
}

/**
 * Limpia todos los filtros
 */
function clearAllFilters() {
  filters.value = {};
  tempFilters.value = {};
  filterSearch.value = {};
  currentPage.value = 1;
}

/**
 * Navega a una página específica
 */
function goToPage(page) {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page;
  }
}

/**
 * Maneja la resincronización
 */
async function handleReload() {
  if (!props.onReload || isReloading.value) return;
  
  isReloading.value = true;
  
  try {
    await props.onReload();
    
    // Resetear estados
    clearAllFilters();
    sortColumn.value = null;
    sortDirection.value = null;
    currentPage.value = 1;
  } catch (error) {
    console.error('Error al recargar datos:', error);
  } finally {
    isReloading.value = false;
  }
}

/**
 * Maneja scroll y resize para cerrar dropdown
 */
function handleScrollOrResize() {
  if (activeFilter.value) {
    closeFilter();
  }
}

// ============= WATCHERS =============

// Cuando cambian los datos, resetear a página 1 si la actual no existe
watch(() => props.data, () => {
  if (currentPage.value > totalPages.value && totalPages.value > 0) {
    currentPage.value = 1;
  }
});

// ============= LIFECYCLE HOOKS =============

// Agregar listeners de scroll y resize
onMounted(() => {
  window.addEventListener('scroll', handleScrollOrResize, true);
  window.addEventListener('resize', handleScrollOrResize);
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScrollOrResize, true);
  window.removeEventListener('resize', handleScrollOrResize);
});

// ============= DIRECTIVA PERSONALIZADA =============

// Directiva para detectar clicks fuera del dropdown
const vClickOutside = {
  mounted(el, binding) {
    el.clickOutsideEvent = function(event) {
      if (!(el === event.target || el.contains(event.target))) {
        binding.value(event);
      }
    };
    document.addEventListener('click', el.clickOutsideEvent);
  },
  unmounted(el) {
    document.removeEventListener('click', el.clickOutsideEvent);
  }
};
</script>

<style scoped>
/* Estilos adicionales si son necesarios */
.datatable-container {
  @apply w-full;
}

/* Animación de spin para el botón de reload */
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>
