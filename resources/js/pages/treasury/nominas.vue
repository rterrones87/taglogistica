<template>
	<breadcrumb :items="breadcrumbItems"/>
	<div class="m-4 bg-white p-4 rounded shadow-md">
		<div class="flex items-center">
            <h2 class="text-3xl my-4 font-bold grow text-center md:text-left">Nominas</h2>
        </div>

        <SegmentedControl
            :titles="types"
            @OnClicked="(i) => filterData(i)"
        />
        
        <!-- DataTable Component -->
        <DataTable
            :data="items"
            :columns="currentColumns"
            :onReload="handleReload"
            emptyMessage="No hay nominas."
        >
            <template #actions="{ row }">
                <div class="flex justify-center flex-col md:flex-row">
                    <TableAction
                        v-if="currentType==1"
                        title="Pagado"
                        icon="cost.png"
                        @click="applyPayment(row.id)"
                    />
                    <TableAction
                        v-else
                        title="Comprobante"
                        icon="request.png"
                        @click="downloadDocument(`nomina-${row.order_date}`,`treasury/payments/pdf/${row.id}`)"
                    />
                    <TableAction
                        title="Detalles"
                        icon="info.png"
                        @click="showDetails(row.id)"
                    />
                </div>
            </template>
        </DataTable>

         <PdfGenerator 
	        ref="pdfGen" 
	        :title="pdfDoc"
	     >
	        <template #pdf="{ data }">
	            <PdfNomina 
	                :data="data"
	                :item="item"
	            />
	        </template>
	    </PdfGenerator>

	</div>

	<BaseModal
		:show="showInfoModal"
		title="Detalles de gastos"
		height="95%"
		@close="() => { showInfoModal = false; payments.value = []; discounts.value = []; }"
	>
		<div class="max-w-full overflow-x-auto">
		  	  <table v-if="payments" class="table">
		      	<caption class="text-left font-bold">Viajes</caption>
		      	<thead>
		      		<tr>
		      			<th>Folio</th>
	                    <th>Tipo de operación</th>
	                    <th>Tipo de operador</th>
	                    <th>Cliente</th>
	                    <th>Destinos</th>
	                    <th>Pago</th>
		      		</tr>
		      	</thead>
		      	<tbody>
		      		<tr v-for="item in payments" :key="item.id">
		      			<td>{{ item.folio }}</td>
	                    <td>{{ getOperationTypeTitle(item.type_operation) }}</td>
	                    <td>{{ item.operator_role }}</td>
	                    <td>{{ item.client }}</td>
	                    <td>{{ getAllDestine(item) }}</td>
	                    <td>${{item.amount}}</td>
		      		</tr>
		      		<tr>
		      			<td colspan="4"><b>Total</b></td>
		      			<td>${{ total }}</td>
		      		</tr>
		      	</tbody>
		      </table>
	  	  </div>

	      <table v-if="discounts.length > 0" class="table mt-4">
	      	<caption class="text-left font-bold">Descuentos</caption>
	      	<thead>
	      		<tr>
	      			<th>Concepto</th>
	      			<th>Descuento</th>
	      		</tr>
	      	</thead>
	      	<tbody>
	      		<tr v-for="(item, index) in discounts" :key="index">
	      			<td>{{item.title}}</td>
	      			<td>${{item.total}}</td>
	      		</tr>
	      		<tr>
	      			<td><b>Total</b></td>
	      			<td>${{ totalDiscounts }}</td>
	      		</tr>
	      	</tbody>
	      </table>
	</BaseModal>

	<BaseModal
		:show="showModal"
		title="Detalles"
		height="95%"
		@close="showModal = false"
	>
        <ul>
          <li 
            v-for="d in details" :key="d.id"
            class="flex items-center gap-2"
          >
            <b class="w-[140px] me-4">{{d.title}}</b>
            <span class="flex-grow">{{d.description}}</span>
          </li>
        </ul>
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
	import PdfGenerator from '@/components/PdfGenerator.vue';
	import PdfNomina from '@/pages/formats/PdfNomina.vue';
	import BaseModal from '../../components/BaseModal.vue';
	import axios from 'axios';
	
	const pdfGen = ref(null)
	const pdfDoc = ref('')

	const downloadDocument = (doc, url) => {
	    pdfDoc.value = doc
	    pdfGen.value.generate(url)
	}

	const dialogs = inject("swal");
	const route = useRoute();
	const router = useRouter();

	const { items, loadItems, loadFilteredItems } = actionslist({
	    endpoint: 'treasury/payments',
	    dialogs
	});

	// Definir los elementos del breadcrumb
	const breadcrumbItems = [
	    { title: 'Nominas' } // Último elemento sin path porque es la página actual
	];

	onMounted(() => {
	    const filters = {};
	    if (route.query.type) filters.type = route.query.type;
	    loadFilteredItems(filters);
	});

	watch(
	    () => router.currentRoute.value.query,
	    () => {
	        const filters = {};
	        if (route.query.type) filters.type = route.query.type;
	        loadFilteredItems(filters);
	    }
	);

	const handleReload = async () => {
	    const filters = {};
	    if (route.query.type) filters.type = route.query.type;
	    return await loadFilteredItems(filters);
	};
	const showInfoModal = ref(false);
	const payments = ref([]);
	const discounts = ref([]);

	const showModal = ref(false);
	const details = ref([]);
	const costs = ref({});
	
	const totalBooths = computed(() =>
	  costs.value.destinations
	    .flatMap(d => d.booths)
	    .reduce((sum, b) => sum + (Number(b.cost) || 0), 0)
	);

	const totalInitials = computed(() =>
	  costs.value.initials
	    .reduce((sum, b) => sum + (Number(b.cost) || 0), 0)
	);

	const totalExtras = computed(() =>
	  costs.value.extras
	    .reduce((sum, b) => sum + (Number(b.cost) || 0), 0)
	);


	const total = computed(() => {
	  return payments.value.reduce((acc, item) => acc + Number(item.amount), 0)
	});

	const totalDiscounts = computed(() => {
	  return discounts.value.reduce((acc, item) => acc + Number(item.total), 0)
	});

	const types = ref([
	  { id: '1', label: 'Pendientes', icon : 'overview.png'  },
	  { id: '2', label: 'Pagados', icon : 'approve.png'  }
	])

	const currentType = ref("1");
	
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
	            	type : 'payment'
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

      		const {data} = await axios.get(`treasury/payments/details/${id}`);

            payments.value = data.payments || [];
            discounts.value = data.discounts || [];

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
	        path: "/panel/treasury/nominas",
	        query: {
	          type: i
	        },
	    });
	}

	const getOperationTypeTitle = (id) => {
        let list = {1 : 'Importación', 2 : 'Exportación', 3 : 'Carga Suelta'};
        return list[id];
    };

    const getDetails = async (service_id) => {
  

		  try {
		    dialogs.fire({
		      title: "Procesando...",
		      text: "Por favor, espere",
		      allowOutsideClick: false,
		      didOpen: () => dialogs.showLoading()
		    });

		    
	        var {data} = await axios.get(`services/${service_id}`);

	        dialogs.close();

	        costs.value = {};

	        costs.value = {
	           destinations: data.cost.formatted_destinations,
	           initials: data.cost.formatted_initial_costs,
	           extras: data.cost.formatted_extras_costs
	        };
	       
	        details.value = [];
        details.value = [
          {id:1, title:"Folio: ", description:data.folio},
          {id:2, title:"Cliente: ", description:data.client.name},
          {id:3, title:"Destino(s): ", description:getAllDestine(data)},
          {id:4, title:"Operador: ", description:data.operator.name},
          {id:5, title:"Unidad: ", description:data.unit.econame}
        ];

	        
           details.value.push({id:6, title:"Total Casetas", description:"$" + totalBooths.value.toFixed(2)});
           details.value.push({id:7, title:"Gastos Iniciales", description:"$" + totalInitials.value.toFixed(2)});
        
           details.value.push({id:8, title:"Gastos Extras", description:"$" + totalExtras.value.toFixed(2)});
        
	       details.value.push({id:9, title:"Diesel Requerido", description: data.diesel + " Litros"});
	        
	    

		   showModal.value = true;  

		  } catch (error) {
		    dialogs.close();
		    console.error("Error procesando la solicitud:", error);
		    await dialogs.fire("Error", "No se pudo encontrar información.", "error");
		  }
		  
	}

		
    const getAllDestine = (item) => {
        if (!item || !item.destines || !Array.isArray(item.destines)) {
            return '';
        }

        return item.destines
            .map(destine => destine.name)
            .join(', ');
    };

    // Configuración de columnas para el DataTable (sin fecha de pago)
    const columnsWithoutPaymentDate = [
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
            key: 'operator', 
            label: 'Operador', 
            sortable: true, 
            filterable: true 
        },
        { 
            key: 'order_date', 
            label: 'Semana a pagar', 
            sortable: true, 
            filterable: true 
        },
        { 
            key: 'total', 
            label: 'Total', 
            sortable: true, 
            filterable: true,
            formatter: (value) => `$${value}`
        }
    ];

    // Configuración de columnas para el DataTable (con fecha de pago)
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
            key: 'operator', 
            label: 'Operador', 
            sortable: true, 
            filterable: true 
        },
        { 
            key: 'order_date', 
            label: 'Semana a pagar', 
            sortable: true, 
            filterable: true 
        },
        { 
            key: 'total', 
            label: 'Total', 
            sortable: true, 
            filterable: true,
            formatter: (value) => `$${value}`
        },
        { 
            key: 'payment_date', 
            label: 'Fecha de pago', 
            sortable: true, 
            filterable: true 
        }
    ];

    // Computed para cambiar columnas según el tipo
    const currentColumns = computed(() => {
        return currentType.value == 2 ? columnsWithPaymentDate : columnsWithoutPaymentDate;
    });

</script>