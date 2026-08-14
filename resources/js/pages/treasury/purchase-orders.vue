<template>
    <breadcrumb :items="breadcrumbItems" />

    <div class="m-4 rounded bg-white p-4 shadow-md">
        <div class="mb-4 flex items-center">
            <h2 class="grow text-center text-3xl font-bold md:text-left">
                Ordenes de compra aprobadas
            </h2>
        </div>

        <DataTable
            :data="items"
            :columns="columns"
            :onReload="loadItems"
            emptyMessage="No hay ordenes de compra aprobadas."
        >
            <template #actions="{ row }">
                <div class="flex justify-center gap-2">
                    <TableAction
                        v-if="!row.treasury_accepted_at && hasPermission('treasury.accept_purchase_orders')"
                        title="Aceptar"
                        icon="approve.png"
                        @click.prevent="acceptOrder(row)"
                    />

                    <TableAction
                        title="Ver detalle"
                        icon="info.png"
                        @click.prevent="showDetails(row.id)"
                    />
                </div>
            </template>
        </DataTable>
    </div>

    <BaseModal
        :show="showModal"
        title="Detalle de la orden de compra"
        @close="showModal = false"
    >
        <div v-if="selectedOrder" class="space-y-3">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <p><strong>OC:</strong> {{ selectedOrder.folio }}</p>
                <p><strong>OT:</strong> {{ selectedOrder.work_order?.folio }}</p>
                <p><strong>Unidad:</strong> {{ selectedOrder.work_order?.unit?.econame || 'N/A' }}</p>
                <p><strong>Proveedor:</strong> {{ selectedOrder.supplier?.name || 'N/A' }}</p>
                <p><strong>Costo:</strong> {{ formatCurrency(selectedOrder.cost) }}</p>
                <p><strong>Condicion:</strong> {{ selectedOrder.payment_condition || 'Por confirmar' }}</p>
                <p class="md:col-span-2"><strong>Descripcion:</strong> {{ selectedOrder.description }}</p>
            </div>

            <div class="flex gap-4 border-t pt-3">
                <a
                    v-if="selectedOrder.quotation_url"
                    :href="selectedOrder.quotation_url"
                    target="_blank"
                    class="text-blue-600"
                >
                    Ver cotizacion
                </a>

                <a
                    v-if="selectedOrder.evidence_url"
                    :href="selectedOrder.evidence_url"
                    target="_blank"
                    class="text-blue-600"
                >
                    Ver evidencia
                </a>
            </div>
        </div>
    </BaseModal>
</template>

<script setup>
import { inject, onMounted, ref } from 'vue';
import {
    acceptTreasuryPurchaseOrderApi,
    getTreasuryPurchaseOrderDetailApi,
    getTreasuryPurchaseOrdersApi,
} from '../../apis/PurchaseOrderApi';
import BaseModal from '../../components/BaseModal.vue';
import DataTable from '../../components/DataTable.vue';
import TableAction from '../../components/TableAction.vue';
import breadcrumb from '../../components/breadcrumb.vue';
import { usePermissions } from '../../composables/usePermissions';

const dialogs = inject('swal');
const { hasPermission } = usePermissions();
const items = ref([]);
const selectedOrder = ref(null);
const showModal = ref(false);

const breadcrumbItems = [
    { title: 'Tesoreria' },
    { title: 'Ordenes de compra' },
];

const columns = [
    { key: 'folio', label: 'OC', sortable: true, filterable: true },
    { key: 'work_order.folio', label: 'OT', sortable: true, filterable: true },
    { key: 'work_order.unit.econame', label: 'Unidad', filterable: true },
    { key: 'supplier.name', label: 'Proveedor', filterable: true },
    { key: 'cost', label: 'Costo', formatter: (value) => formatCurrency(value) },
    { key: 'status', label: 'Estado', filterable: true },
    {
        key: 'treasury_accepted_at',
        label: 'Tesoreria',
        formatter: (value) => value ? 'Aceptada' : 'Por aceptar',
    },
];

onMounted(loadItems);

async function loadItems() {
    const response = await getTreasuryPurchaseOrdersApi();
    items.value = response.data;
}

async function showDetails(id) {
    try {
        const response = await getTreasuryPurchaseOrderDetailApi(id);
        selectedOrder.value = response.data;
        showModal.value = true;
    } catch (error) {
        dialogs.fire('Error', error.response?.data?.message || 'No fue posible consultar la orden', 'error');
    }
}

async function acceptOrder(order) {
    const result = await dialogs.fire({
        title: 'Aceptar orden de compra',
        text: `¿Desea aceptar la orden ${order.folio} en Tesoreria?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Si, aceptar',
        cancelButtonText: 'Cancelar',
    });

    if (!result.isConfirmed) return;

    try {
        await acceptTreasuryPurchaseOrderApi(order.id);
        await loadItems();
        dialogs.fire('Excelente', 'Orden de compra aceptada', 'success');
    } catch (error) {
        dialogs.fire('Error', error.response?.data?.message || 'No fue posible aceptar la orden', 'error');
    }
}

function formatCurrency(value) {
    return Number(value || 0).toLocaleString('es-MX', {
        style: 'currency',
        currency: 'MXN',
    });
}
</script>
