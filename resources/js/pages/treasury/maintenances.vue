<template>
	<breadcrumb :items="breadcrumbItems"/>
	<div class="m-4 bg-white p-4 rounded shadow-md">
		<div class="flex items-center">
            <h2 class="text-3xl my-4 font-bold grow text-center md:text-left">Mantenimientos</h2>
            
            <ExcelExport
                class="w-full md:w-auto md:mx-1"
                endpoint="download/treasury/maintenances"
                :fields="['id', 'folio', 'unit', 'user', 'approver', 'order_date', 'supplier', 'concepts', 'total', 'description', 'payment_date']"
                :headers="['ID', 'Folio', 'Unidad', 'Usuario/Solicita', 'Usuario/Aprueba', 'Fecha/Orden', 'Proveedor', 'Conceptos', 'Total', 'Descripción', 'Fecha de Pago']"
                fileName="mantenimientos_tesoreria.xlsx"
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
            :to="{ path: '/panel/treasury/maintenances', query: { type: type.id } }"
            :class="{ active: currentType === type.id }"
          >
            {{ type.label }}
          </router-link>
        </nav> -->


        <div class="flex justify-end items-center gap-2 mb-4">
        	<div class="me-2">
                <div class="form-item">
		            <select 
		                v-model="currentTypeHistorical"
		                @change="getByTypeMaintenance"
		              >
		                <option value="">Todos</option>
		                <option value="1">Preventivo</option>
		                <option value="2">Correctivo</option>
		                <option value="3">Reparación mayor</option>
		                <option value="4">Siniestro</option>
		                <option value="5">Rescate carretero</option>
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
            emptyMessage="No hay mantenimientos disponibles."
        >
            <template #actions="{ row }" v-if="currentType != 6">
                <div class="flex justify-center flex-col md:flex-row">
                    <TableAction
                        title="Pagado"
                        icon="cost.png"
                        @click.prevent="applyPayment(row.id)"
                    />
                    <TableAction
                        title="Detalles"
                        icon="info.png"
                        @click.prevent="showDetails(row.id)"
                    />
                </div>
            </template>
        </DataTable>

	</div>

	<BaseModal
		:show="showInfoModal"
		title="Detalles del Mantenimiento"
		height="95%"
		@close="closeModal"
	>
		<ul class="my-2">
			<li 
				v-for="d in details" 
				:key="d.id"
				class="flex items-center gap-2"
			>
				<b class="w-[140px] me-4">{{ d.title }}</b>
				<span class="flex-grow">{{ d.description }}</span>
			</li>
		</ul>
		<hr/>

		<div v-if="supplierRequests.length > 0" class="my-1">
			<b class="text-gray-500 py-1 block">Solicitud a Proveedor</b>
			<hr/>
			<table class="table mt-2 text-sm">
				<thead>
					<tr>
						<th>Concepto</th>
						<th>Cantidad</th>
						<th>Costo</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="item in supplierRequests" :key="item.id">
						<td><b>{{ item.description }}</b></td>
						<td class="text-center">{{ item.quantity || '-' }}</td>
						<td class="text-end">${{ Number(item.cost).toFixed(2) }}</td>
					</tr>
					<tr>
						<td><b>TOTAL</b></td>
						<td></td>
						<td class="text-end">${{ totalSupplierRequests.toFixed(2) }}</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div v-if="inventoryRequests.length > 0" class="mt-2">
			<b class="text-gray-500 py-1 block">Solicitud a Inventario</b>
			<hr/>
			<table class="table mt-2 text-sm">
				<thead>
					<tr>
						<th>Producto</th>
						<th>Cantidad</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="inv in inventoryRequests" :key="inv.id">
						<td><b>{{ inv.inventory.name }}</b></td>
						<td class="text-end">{{ inv.quantity }}</td>
					</tr>
				</tbody>
			</table>
		</div>
	</BaseModal>
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
import BaseModal from '../../components/BaseModal.vue';
import axios from 'axios';

	const dialogs = inject("swal");
	const route = useRoute();
	const router = useRouter();

	const { items, loadItems, loadFilteredItems } = actionslist({
	    endpoint: 'treasury/maintenances',
	    dialogs
	});

	// Definir los elementos del breadcrumb
	const breadcrumbItems = [
	    { title: 'Mantenimientos' } // Último elemento sin path porque es la página actual
	];

onMounted(async () => {
	    // Inicializar dateRange con semana actual si no tiene valores
	    if (!dateRange.value.start || !dateRange.value.end) {
	        const today = new Date();
	        const dayOfWeek = today.getDay();
	        const diff = -dayOfWeek; // Domingo (día 0)
	        
	        const startOfWeek = new Date(today);
	        startOfWeek.setDate(today.getDate() + diff);
	        
	        const endOfWeek = new Date(startOfWeek);
	        endOfWeek.setDate(startOfWeek.getDate() + 6); // Sábado
	        
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
	const details = ref([]);
	const supplierRequests = ref([]);
	const inventoryRequests = ref([]);

	const totalSupplierRequests = computed(() => {
		return supplierRequests.value.reduce((sum, item) => sum + (Number(item.cost) || 0), 0);
	});

	const types = ref([
	  { id: '1', label: 'Preventivo', icon : 'preventive.png'  },
	  { id: '2', label: 'Correctivo', icon : 'corrective.png'  },
	  { id: '3', label: 'Reparación mayor', icon : 'repare.png'  },
	  { id: '4', label: 'Siniestro', icon : 'car_crash.png'  },
	  { id: '5', label: 'Rescate carretero', icon : 'rescue.png'  },
	  { id: '6', label: 'Historial', icon : 'historical.png' }
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

	const getByTypeMaintenance = () => {
	  router.push({
	    path: "/panel/treasury/maintenances",
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
	            	type : 'maintenance'
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

			const {data} = await axios.get(`treasury/maintenances/details/${id}`);

			// Llenar detalles generales
			details.value = [
				{id: 1, title: "Folio:", description: data.maintenance.folio},
				{id: 2, title: "Unidad:", description: data.maintenance.unit},
				{id: 3, title: "Tipo de Mantto:", description: data.maintenance.type_maintenance},
				{id: 4, title: "Kms:", description: data.maintenance.kms},
				{id: 5, title: "Descripción:", description: data.maintenance.description},
				{id: 6, title: "Proveedor:", description: data.treasury_order.supplier_name || 'N/A'},
				{id: 7, title: "Factura:", description: data.treasury_order.invoice_required ? 'Sí' : 'No'},
				{id: 8, title: "Total:", description: '$' + Number(data.treasury_order.total).toFixed(2)}
			];

			// Llenar solicitudes del proveedor
			supplierRequests.value = data.supplier_requests || [];

			// Llenar solicitudes de inventario
			inventoryRequests.value = data.inventory_requests || [];

			dialogs.close();
			showInfoModal.value = true;

		} catch (error) {
			dialogs.close();
			console.error("Error obteniendo detalles:", error);
			dialogs.fire("Lo sentimos!", "Ocurrio un error inesperado, intente de nuevo.", "error");
		}
	}

	const closeModal = () => {
		showInfoModal.value = false;
		details.value = [];
		supplierRequests.value = [];
		inventoryRequests.value = [];
	}

	const filterData = (i) => {
		currentType.value = i;
	    router.push({
	        path: "/panel/treasury/maintenances",
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
	    label: 'Folio', 
	    sortable: true, 
	    filterable: true 
	},
	{ 
	    key: 'unit', 
	    label: 'Unidad', 
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
    key: 'approver', 
    label: 'Usuario/Aprueba', 
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
	        key: 'supplier', 
	        label: 'Proveedor', 
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
	        key: 'description', 
	        label: 'Descripción', 
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
	    label: 'Folio', 
	    sortable: true, 
	    filterable: true 
	},
	{ 
	    key: 'unit', 
	    label: 'Unidad', 
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
    key: 'approver', 
    label: 'Usuario/Aprueba', 
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
        key: 'supplier', 
        label: 'Proveedor', 
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
	        key: 'description', 
	        label: 'Descripción', 
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
	    return currentType.value == 6 ? columnsWithPaymentDate : columnsWithActions;
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