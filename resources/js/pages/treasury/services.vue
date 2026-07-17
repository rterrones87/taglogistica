<template>
	<breadcrumb :items="breadcrumbItems"/>
	<div class="m-4 bg-white p-4 rounded shadow-md">
		<div class="flex items-center">
            <h2 class="text-3xl my-4 font-bold grow text-center md:text-left">Viajes</h2>
            
            <ExcelExport
                class="w-full md:w-auto md:mx-1"
                endpoint="download/treasury/services"
                :fields="['id', 'folio', 'user', 'order_date', 'operator', 'total', 'payment_date']"
                :headers="['ID', 'Folio/Viaje', 'Usuario/Solicita', 'Fecha/Orden', 'Operador', 'Total', 'Fecha de Pago']"
                fileName="viajes_tesoreria.xlsx"
                buttonLabel="Descargar"
                :filters="getCurrentFilters()"
            />
        </div>

        <SegmentedControl
            :titles="types"
            @OnClicked="(i) => filterData(i)"
        />
        <!--<nav class="tabbar">
          <router-link
            v-for="type in types"
            :key="type.id"
            :to="{ path: '/panel/treasury/services', query: { type: type.id } }"
            :class="{ active: currentType === type.id }"
          >
            {{ type.label }}
          </router-link>
        </nav>-->

        <div class="flex justify-end items-center gap-2 mb-4">
        	<div class="me-2">
                <div class="form-item">
		            <select 
		                v-model="currentTypeHistorical"
		                @change="getByTypeSpent"
		              >
		                <option value="">Todos</option>
		                <option value="1">Gastos iniciales</option>
		                <option value="2">Gastos extras</option>
		            </select>
		        </div>
		    </div>
            <input 
                type="checkbox" 
                v-model="dateFilterEnabled"
                class="w-4 h-4 cursor-pointer"
                title="Habilitar/Deshabilitar filtro de fechas"
            />
            <WeekNavigator 
              v-model="dateRange"
              :disabled="!dateFilterEnabled"
            />
        </div>

        <!-- DataTable Component -->
        <DataTable
            :data="items"
            :columns="currentColumns"
            :onReload="handleReload"
            emptyMessage="No hay viajes disponibles."
        >
            <template #actions="{ row }" v-if="currentType != 3">
                <div class="flex justify-center flex-col md:flex-row">
                    <TableAction
                        title="Pagado"
                        icon="cost.png"
                        @click.prevent="applyPayment(row.id)"
                    />
                    <TableAction
                        title="Detalles"
                        icon="info.png"
                        @click.prevent="showDetails(row.service_id)"
                    />
                </div>
            </template>
        </DataTable>

	</div>

	<div v-if="showInfoModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
	    <div class="bg-white h-screen px-4 pb-4 rounded-md w-full max-w-lg overflow-y-auto">
	      <div class="flex items-center bg-white z-10 sticky left-0 top-0">
	      	<h3 class="text-xl font-bold">Detalles de gastos</h3>
	      	<button 
	      		@click="() => { showInfoModal = false; expenses.value = []; costs.value = []; }" 
	      		class="boder-0 outline-0 ml-auto p-2 m-2 font-bold text-[#18364a]">Cerrar</button>
	  	  </div>

	  	  <ul class="my-2">
			<li 
			class="flex items-center gap-2"
			>
				<b class="w-[140px] me-4">Gastos Totales</b>
				<span class="flex-grow">${{totalCosts + total}}</span>
			</li>
		  </ul>
    	  <hr/>
	      <table v-if="costs.length > 0" class="table mt-3 text-sm">
	      	<caption class="text-left font-bold">Destinos/Casetas</caption>
	      	<thead>
	      		<tr>
	      			<th>Contenedor</th>
	      			<th>Destino</th>
	      			<th>Costo</th>
	      		</tr>
	      	</thead>
	      	<tbody>
	      		<tr v-for="(item, index) in costs" :key="index">
	      			<td>{{item.number}}</td>
	      			<td>{{item.place}}</td>
	      			<td class="text-end">${{Number(item.cost).toFixed(2)}}</td>
	      		</tr>
	      		<tr>
	      			<td><b>Total</b></td>
	      			<td></td>
	      			<td class="text-end">${{ totalCosts.toFixed(2) }}</td>
	      		</tr>
	      	</tbody>
	      </table>

	      <table v-if="expenses" class="table mt-4 text-sm">
	      	<caption class="text-left font-bold">{{types.find(t => t.id == currentType)?.label}}</caption>
	      	<thead>
	      		<tr>
	      			<th>Concepto</th>
	      			<th>Costo</th>
	      		</tr>
	      	</thead>
	      	<tbody>
	      		<tr v-for="item in expenses" :key="item.id">
	      			<td>{{item.concept}}</td>
	      			<td class="text-end">${{Number(item.cost).toFixed(2)}}</td>
	      		</tr>
	      		<tr>
	      			<td><b>Total</b></td>
	      			<td class="text-end">${{ total.toFixed(2) }}</td>
	      		</tr>
	      	</tbody>
	      </table>

	     
	    </div>
	  </div>
</template>

<script setup>
	import { inject, ref, computed, onMounted, watch } from 'vue';
	import { useRoute, useRouter } from 'vue-router';
	import breadcrumb from '../../components/breadcrumb.vue';
	import { actionslist } from '../../composables/actionslist';
	import DataTable from '@/components/DataTable.vue';
	import TableAction from '@/components/TableAction.vue';
	import SegmentedControl from '@/components/SegmentedControl.vue';
	import WeekNavigator from '@/components/WeekNavigator.vue';
	import ExcelExport from '../../components/ExcelExport.vue';
	import axios from 'axios';
	
	const dialogs = inject("swal");
	const route = useRoute();
	const router = useRouter();

	const { items, loadItems, loadFilteredItems } = actionslist({
	    endpoint: 'treasury/services',
	    dialogs
	});

	// Definir los elementos del breadcrumb
	const breadcrumbItems = [
	    { title: 'Viajes' } // Último elemento sin path porque es la página actual
	];

	onMounted(async () => {
	    // Inicializar dateRange con semana actual si no tiene valores
	    if (!dateRange.value.start || !dateRange.value.end) {
	        const today = new Date();
	        const dayOfWeek = today.getDay();
	        const diff = dayOfWeek === 0 ? -6 : 1 - dayOfWeek; // Lunes
	        
	        const startOfWeek = new Date(today);
	        startOfWeek.setDate(today.getDate() + diff);
	        
	        const endOfWeek = new Date(startOfWeek);
	        endOfWeek.setDate(startOfWeek.getDate() + 6); // Domingo
	        
	        dateRange.value = {
	            start: startOfWeek.toISOString().split('T')[0],
	            end: endOfWeek.toISOString().split('T')[0]
	        };
	    }
	    
	    const filters = {};
	    if (route.query.type) filters.type = route.query.type;
	    if (route.query.htype) filters.htype = route.query.htype;
	    if (dateFilterEnabled.value && dateRange.value.start && dateRange.value.end) {
	        filters.start_date = dateRange.value.start;
	        filters.end_date = dateRange.value.end;
	    }
	    await loadFilteredItems(filters);
	});

	watch(
	    () => router.currentRoute.value.query,
	    async () => {
	        const filters = {};
	        if (route.query.type) filters.type = route.query.type;
	        if (route.query.htype) filters.htype = route.query.htype;
	        if (dateFilterEnabled.value && dateRange.value.start && dateRange.value.end) {
	            filters.start_date = dateRange.value.start;
	            filters.end_date = dateRange.value.end;
	        }
	        await loadFilteredItems(filters);
	    }
	);

	const handleReload = async () => {
	    const filters = {};
	    if (route.query.type) filters.type = route.query.type;
	    if (route.query.htype) filters.htype = route.query.htype;
	    if (dateFilterEnabled.value && dateRange.value.start && dateRange.value.end) {
	        filters.start_date = dateRange.value.start;
	        filters.end_date = dateRange.value.end;
	    }
	    await loadFilteredItems(filters);
	};
	const showInfoModal = ref(false);
	const expenses = ref([]);
	const costs = ref([]);

	const total = computed(() => {
	  return expenses.value.reduce((acc, item) => acc + Number(item.cost), 0)
	});

	const totalCosts = computed(() => {
	  return costs.value.reduce((acc, item) => acc + Number(item.cost), 0)
	});

	const types = ref([
	  { id: '1', label: 'Gastos iniciales', icon : 'credit_card.png'  },
	  { id: '2', label: 'Gastos extras', icon : 'checkbook.png'  },
	  { id: '3', label: 'Historial', icon : 'historical.png' }
	])

const currentType = ref("1");
const currentTypeHistorical = ref(route.query.htype || "");
const dateRange = ref({ start: '', end: '' });
const dateFilterEnabled = ref(true);

	watch(dateRange, async (newRange) => {
	    if (!dateFilterEnabled.value) return;
	    if (!newRange.start || !newRange.end) return;
	    
	    const filters = {};
	    if (route.query.type) filters.type = route.query.type;
	    if (route.query.htype) filters.htype = route.query.htype;
	    filters.start_date = newRange.start;
	    filters.end_date = newRange.end;
	    await loadFilteredItems(filters);
	}, { deep: true });

	watch(dateFilterEnabled, async (newValue) => {
	    // Recargar datos con o sin filtro de fechas
	    const filters = {};
	    if (route.query.type) filters.type = route.query.type;
	    if (route.query.htype) filters.htype = route.query.htype;
	    
	    if (newValue && dateRange.value.start && dateRange.value.end) {
	        filters.start_date = dateRange.value.start;
	        filters.end_date = dateRange.value.end;
	    }
	    
	    await loadFilteredItems(filters);
	});

	const getByTypeSpent = () => {
	  router.push({
	    path: "/panel/treasury/services",
	    query: {
	      ...route.query,          
	      htype: currentTypeHistorical.value 
	    },
	  });
	};

	const applyPayment = async (id) => {
		const result = await dialogs.fire({
            title: "Marcar como pagado",
            text: "¿Estás seguro(a) que deseas ejecutar esta acción?",
            showCancelButton: true,
            confirmButtonText: "Aceptar",
            cancelButtonText: "Cancelar"
        });

        if (result.isConfirmed) {
        	try {
	            dialogs.fire({
	                title: "Procesando...",
	                text: "Por favor, espere",
	                allowOutsideClick: false,
	                didOpen: () => dialogs.showLoading()
	            });

	            // Ejecutar la solicitud de pago
	            await axios.put(`treasury/apply-payment`, {
	            	id : id,
	            	type : 'service'
	            });
	            
	           
	            dialogs.close();
	            dialogs.fire("Excelente!", "El pago se ha aplicado correctamente.", "success");

	            await loadItems();

	        } catch (error) {
	            dialogs.close();
	            dialogs.fire("Lo sentimos!", "Ocurrio un error inesperado, intente de nuevo.", "error");
	        }
        }
	}

	const showDetails = async (id) => {
		try {
            dialogs.fire({
                title: "Procesando...",
                text: "Por favor, espere",
                allowOutsideClick: false,
                didOpen: () => dialogs.showLoading()
            });

      		const path = currentType.value=="1" ? "init" : "ext";
            const {data} = await axios.get(`treasury/${path}/expenses/${id}`);

            expenses.value = data.expenses;
            costs.value = data.costs || [];

            dialogs.close();
            showInfoModal.value = true;

        } catch (error) {
            dialogs.close();
            dialogs.fire("Lo sentimos!", "Ocurrio un error inesperado, intente de nuevo.", "error");
        }
		
	}

	const filterData = (i) => {
		currentType.value = i;
	    router.push({
	        path: "/panel/treasury/services",
	        query: {
	          type: i
	        },
	    });
	}

	// Configuración de columnas para el DataTable (sin fecha de pago, con acciones)
	const columnsWithActions = [
	    { 
	        key: 'id', 
	        label: 'ID', 
	        sortable: true, 
	        filterable: true 
	    },
	    { 
	        key: 'folio', 
	        label: 'Folio/Viaje', 
	        sortable: true, 
	        filterable: true 
	    },
	    { 
	        key: 'user', 
	        label: 'Usuario/Solicita', 
	        sortable: true, 
	        filterable: true 
	    },
	    { 
	        key: 'order_date', 
	        label: 'Fecha/Orden', 
	        sortable: true, 
	        filterable: true 
	    },
    { 
        key: 'operator', 
        label: 'Operador', 
        sortable: true, 
        filterable: true 
    },
	    { 
	        key: 'total', 
	        label: 'Total', 
	        sortable: true, 
	        filterable: true 
	    }
	];

	// Configuración de columnas para el DataTable (con fecha de pago, sin acciones)
	const columnsWithPaymentDate = [
	    { 
	        key: 'id', 
	        label: 'ID', 
	        sortable: true, 
	        filterable: true 
	    },
	    { 
	        key: 'folio', 
	        label: 'Folio/Viaje', 
	        sortable: true, 
	        filterable: true 
	    },
	    { 
	        key: 'user', 
	        label: 'Usuario/Solicita', 
	        sortable: true, 
	        filterable: true 
	    },
	    { 
	        key: 'order_date', 
	        label: 'Fecha/Orden', 
	        sortable: true, 
	        filterable: true 
	    },
    { 
        key: 'operator', 
        label: 'Operador', 
        sortable: true, 
        filterable: true 
    },
	    { 
	        key: 'total', 
	        label: 'Total', 
	        sortable: true, 
	        filterable: true 
	    },
	    { 
	        key: 'payment_date', 
	        label: 'Fecha de Pago', 
	        sortable: true, 
	        filterable: true 
	    }
	];

	// Computed para cambiar columnas según el tipo
	const currentColumns = computed(() => {
	    return currentType.value == 3 ? columnsWithPaymentDate : columnsWithActions;
	});

	const getCurrentFilters = () => {
		const filters = {};
		if (currentType.value) filters.type = currentType.value;
		if (currentTypeHistorical.value) filters.htype = currentTypeHistorical.value;
		if (dateFilterEnabled.value && dateRange.value.start && dateRange.value.end) {
			filters.start_date = dateRange.value.start;
			filters.end_date = dateRange.value.end;
		}
		return filters;
	};

</script>