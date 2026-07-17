<template>
  <breadcrumb :items="breadcrumbItems"/>
  <div class="m-4 bg-white p-4 rounded shadow-md">

    <div class="flex items-center">
      <h2 class="text-3xl my-4 font-bold grow text-center md:text-left">Autorizaciones</h2>
    </div>

    <router-link class="float-button block md:hidden" :to="{ path: 'approvals' }"></router-link>

    <SegmentedControl
      :titles="estados"
      @OnClicked="(i) => filterData(i)"
    />

    <!-- Tabla -->
    <table class="table" v-if="items.length > 0">
      <thead>
        <tr>
          <th>ID</th>
          <th>Tipo</th>
          <th>Módulo</th>
          <th>Fecha de solicitud</th>
          <th>Solicitud</th> 
          <th>Estatus</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="approval in items" :key="approval.id">
          <td data-label="ID">{{ approval.id }}</td>
          <td data-label="Tipo">{{ approval.kind_humanize }}</td>
          <td data-label="Módulo">{{ formatModule(approval.approvable_type) }}</td>
          <td data-label="Fecha de solicitud">{{ (approval.created_at || '').toString().substring(0, 10) }}</td>
          <td data-label="Solicitud">
            <ul v-if="!isEmptySnapshot(normalizeSnapshot(approval.snapshot))" class="list-disc pl-5">
                <li v-for="(val, key) in normalizeSnapshot(approval.snapshot)" :key="key">
                <span class="font-medium">{{ key }}:</span> {{ String(val) }}
                </li>
                
                  <button 
                    v-if="approval.kind != 'tire_expenses'"
                    class="text-xs rounded border border-[#18364a] px-4 py-1 my-1 text-[#18364a]" 
                    type="button"
                    @click="getApprovalDetails(approval.kind, approval.scope_id)"
                  >
                    Detalles
                  </button>
                
            </ul>
            <span v-else>—</span>
          </td>

          <td data-label="Estatus">
            <span :class="statusClass(approval.status)">
              {{ statusLabel(approval.status) }}
            </span>
          </td>
          <td>
            <div v-if="approval.status == 'pending'" class="flex justify-center flex-col md:flex-row">
              <TableAction
                title="Aprobar"
                icon="approve.png"
                @click.prevent="approveItem(approval.id)"
              />
              <TableAction
                title="Rechazar"
                icon="reject.png"
                @click.prevent="rejectItem(approval.id)"
              />
            </div>
          </td>
        </tr>
      </tbody>
    </table>

    <p class="text-center block py-8" v-else>No hay autorizaciones disponibles.</p>
  </div>

  <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
    <div class="bg-white min-h-screen p-4 rounded-md w-full max-w-lg">
      <div class="flex items-center">
          <h3 class="flex-grow text-xl font-bold mb-4">Detalles</h3>
          <button 
            @click="showModal = false" 
            class="relative items-center rounded-full border border-[#18364a] text-[#18364a] w-6 h-6"
          >
              <span class="relative -top-1">x</span>
          </button>
      </div>
      <div class="w-full overflow-y-auto">
        <ul>
          <li 
            v-for="d in details" :key="d.id"
            class="flex items-center gap-2"
          >
            <b class="w-[140px] me-4">{{d.title}}</b>
            <span class="flex-grow">{{d.description}}</span>
          </li>
        </ul>

        <div v-if="costs.destinations" class="mt-3 mb-1">
            <b class="text-gray-500  py-1 block">Destinos/Casetas</b>
            <hr/>
            <table class="table mt-2 text-sm">
              <thead>
                <tr>
                  <th>Destino</th>
                  <th>Casetas/Costos</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="c in costs.destinations" :key="c.id">
                  <td><b>{{c.name}}</b></td>
                  <td>
                    <ul>
                      <li 
                        v-for="b in c.booths" :key="b.id"
                        class="flex items-center gap-2"
                      >
                          <b class="me-4">{{b.name}}</b>
                          <span class="flex-grow text-end">${{Number(b.cost).toFixed(2)}}</span>
                      </li>
                    </ul>
                  </td>
                </tr>
                <tr>
                  <td><b class="w-16 me-4">TOTAL</b></td>
                  <td class="text-end">
                    ${{ totalBooths.toFixed(2) }}
                  </td>
                </tr>
              </tbody>
            </table>
        </div>

        <div v-if="costs.initials" class="my-1">
            <b class="text-gray-500 py-1 block">Gastos Iniciales</b>
            <hr/>
            <table class="table mt-2 text-sm">
              <thead>
                <tr>
                  <th>Concepto</th>
                  <th>Costo</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="c in costs.initials" :key="c.id">
                  <td><b>{{c.concept}}</b></td>
                  <td class="text-end">${{Number(c.cost).toFixed(2)}}</td>
                </tr>
                <tr>
                  <td><b class="w-16 me-4">TOTAL</b></td>
                  <td class="text-end">
                    ${{ totalInitials.toFixed(2) }}
                  </td>
                </tr>
              </tbody>
            </table>
        </div>

        <div v-if="costs.extras" class="my-1">
            <b class="text-gray-500 py-1 block">Gastos Extras</b>
            <hr/>
            <table class="table mt-2 text-sm">
              <thead>
                <tr>
                  <th>Concepto</th>
                  <th>Costo</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="c in costs.extras" :key="c.id">
                  <td><b>{{c.concept}}</b></td>
                  <td class="text-end">${{Number(c.cost).toFixed(2)}}</td>
                </tr>
                <tr>
                  <td><b class="w-16 me-4">TOTAL</b></td>
                  <td class="text-end">
                    ${{ totalExtras.toFixed(2) }}
                  </td>
                </tr>
              </tbody>
            </table>
        </div>

        <div v-if="maintenances.parts" class="my-1">
            <b class="text-gray-500 py-1 block">Solicitud a Proveedor</b>
            <hr/>
            <table class="table mt-2 text-sm">
              <thead>
                <tr>
                  <th>Concepto</th>
                  <th>Costo</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="p in maintenances.parts" :key="p.id">
                  <td><b>{{p.description}}</b></td>
                  <td class="text-end">${{Number(p.cost).toFixed(2)}}</td>
                </tr>
                <tr>
                  <td><b class="w-16 me-4">TOTAL</b></td>
                  <td class="text-end">
                    ${{ totalParts.toFixed(2) }}
                  </td>
                </tr>
              </tbody>
            </table>

            <div v-if="maintenances.inventory && maintenances.inventory.length > 0" class="mt-2">
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
                    <tr v-for="inv in maintenances.inventory" :key="inv.id">
                      <td><b>{{ inv.inventory.name }}</b></td>
                      <td class="text-end">{{ inv.quantity }}</td>
                    </tr>
                  </tbody>
                </table>
            </div>
        </div>
        

      </div>
      
    </div>
  </div>

</template>

<script setup>
import { inject, ref, computed, onMounted, watch  } from 'vue';
import axios from 'axios';
import { actionslist } from '../composables/actionslist';
import breadcrumb from '../components/breadcrumb.vue';
import { useRouter } from 'vue-router';
import TableAction from '@/components/TableAction.vue';
import SegmentedControl from '@/components/SegmentedControl.vue';

const router = useRouter();
const dialogs = inject("swal");
const showModal = ref(false);
const details = ref([]);
const costs = ref({});
const maintenances = ref({});

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

const totalParts = computed(() =>
  maintenances.value.parts
    .reduce((sum, b) => sum + (Number(b.cost) || 0), 0)
);

// Estados: Pendiente, Aprobado, Rechazado
const estados = ref([
  { id: 'pending',  label: 'Pendiente',  icon: 'pending.png' },
  { id: 'approved', label: 'Aprobado',   icon: 'complete.png' },
  { id: 'rejected', label: 'Rechazado',  icon: 'cancel.png' },
]);

// Loader de items usando tu helper; solo llena la tabla
const { items, loadItems, loadFilteredItems } = actionslist({
  endpoint: 'approvals',
  dialogs
});

onMounted(() => {
  const filters = {};
  const query = router.currentRoute.value.query;
  if (query.estado) {
    filters.estado = query.estado;
  }
  loadFilteredItems(filters);
});

watch(
  () => router.currentRoute.value.query.estado,
  () => {
    const filters = {};
    const query = router.currentRoute.value.query;
    if (query.estado) {
      filters.estado = query.estado;
    }
    loadFilteredItems(filters);
  }
);

// Breadcrumb
const breadcrumbItems = [
  { title: 'Autorizaciones' }
];

// Filtro por estado
const filterData = (estadoId) => {
  router.push({
    path: "/panel/approvals",
    query: { estado: estadoId },
  });
};

// Helpers UI
const statusLabel = (status) => {
  if (status === 'approved') return 'Aprobado';
  if (status === 'rejected') return 'Rechazado';
  return 'Pendiente';
};

const statusClass = (status) => {
  if (status === 'approved') return 'text-green-600 font-semibold';
  if (status === 'rejected') return 'text-red-600 font-semibold';
  return 'text-yellow-600 font-semibold';
};

const formatModule = (approvableType) => {
  if (!approvableType) return '';
  const parts = approvableType.split('\\');
  const type = parts[parts.length - 1] || approvableType;
  switch(type) {
    case 'Service': return 'Viajes'; break;
    default: return type;
  }
};


const normalizeSnapshot = (snapshot) => {
  if (!snapshot || typeof snapshot !== 'object' || Array.isArray(snapshot)) {
    return {};
  }
  return snapshot;
};

const isEmptySnapshot = (obj) => {
  return !obj || Object.keys(obj).length === 0;
};

// Acciones: Aprobar / Rechazar con dialogs (swal)
const approveItem = async (id) => {
  const result = await dialogs.fire({
    title: "Aprobar autorización",
    text: "¿Estás seguro(a) que deseas aprobar esta solicitud?",
    showCancelButton: true,
    confirmButtonText: "Aprobar",
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

      await axios.post(`approvals/${id}/approve`);

      dialogs.close();
      await dialogs.fire("¡Excelente!", "La solicitud ha sido aprobada.", "success");
      await loadItems();
    } catch (error) {
      dialogs.close();
      console.error("Error aprobando la solicitud:", error);
      await dialogs.fire("Error", "No se pudo aprobar la solicitud.", "error");
    }
  }
};

const rejectItem = async (id) => {
  const result = await dialogs.fire({
    title: "Rechazar autorización",
    text: "¿Estás seguro(a) que deseas rechazar esta solicitud?",
    showCancelButton: true,
    confirmButtonText: "Rechazar",
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

      await axios.post(`approvals/${id}/reject`);

      dialogs.close();
      await dialogs.fire("Hecho", "La solicitud ha sido rechazada.", "success");
      await loadItems();
    } catch (error) {
      dialogs.close();
      console.error("Error rechazando la solicitud:", error);
      await dialogs.fire("Error", "No se pudo rechazar la solicitud.", "error");
    }
  }
};


const getApprovalDetails = async (kind, scope_id) => {
  

  try {
    dialogs.fire({
      title: "Procesando...",
      text: "Por favor, espere",
      allowOutsideClick: false,
      didOpen: () => dialogs.showLoading()
    });

    ////initial_diesel_required, maintenance_expenses, extra_expenses

    if(kind == 'maintenance_expenses')
    {
        var {data} = await axios.get(`maintenances/${scope_id}`);

        dialogs.close();

        costs.value = {};
        maintenances.value = {};

        maintenances.value = {
          inventory: data.inventory_requests,
          parts: data.parts_supplier_requests
        };

        details.value = [];

        details.value = [
          {id:1, title:"Folio: ", description:data.folio},
          {id:2, title:"Kms: ", description:data.kms},
          {id:3, title:"Tipo de Mantto: ", description:data.type_maintenance.name},
          {id:4, title:"Unidad: ", description:data.unit.econame},
          {id:5, title:"Descripción: ", description:data.description},
          {id:6, title:"Costo de Mantto", description:"$" + totalParts.value.toFixed(2)}
        ];



    }
    else
    {
        var {data} = await axios.get(`services/${scope_id}`);

        dialogs.close();

        costs.value = {};
        maintenances.value = {};

        if(kind == 'initial_expenses') {
          costs.value = {
            destinations: data.cost.formatted_destinations,
            initials: data.cost.formatted_booth_costs
          };
        }

        if(kind == 'extra_expenses') {
          costs.value = {
            extras: data.cost.formatted_extras_costs,
          };
        }

        details.value = [];
        details.value = [
          {id:1, title:"Folio: ", description:data.folio},
          {id:2, title:"Cliente: ", description:data.client.name},
          {id:3, title:"Destino(s): ", description:getAllDestine(data)},
          {id:4, title:"Operador: ", description:data.operator?.name || 'Sin asignar'},
          {id:5, title:"Unidad: ", description:data.unit?.econame || 'Sin asignar'}
        ];

        if(kind == 'initial_expenses') {
          details.value.push({id:6, title:"Total Casetas", description:"$" + totalBooths.value.toFixed(2)});
          details.value.push({id:7, title:"Gastos Iniciales", description:"$" + totalInitials.value.toFixed(2)});
        }
        
        if(kind == 'extra_expenses') {
          details.value.push({id:8, title:"Gastos Extras", description:"$" + totalExtras.value.toFixed(2)});
        }

        if(kind == 'initial_diesel_required') {
          details.value.push({id:9, title:"Diesel Requerido", description: data.diesel + " Litros"});
        }
    }

    showModal.value = true;  

  } catch (error) {
    dialogs.close();
    console.error("Error procesando la solicitud:", error);
    await dialogs.fire("Error", "No se pudo encontrar información.", "error");
  }
  
}

const getAllDestine = (item) => {
    if (!item || !item.containers || !Array.isArray(item.containers)) {
        return '';
    }

    return item.containers
        .map(container => container.place.name)
        .join(', ');
};

</script>
