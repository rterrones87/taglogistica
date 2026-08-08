<template>
    <breadcrumb :items="breadcrumbItems" />

    <div class="m-4 rounded bg-white p-4 shadow-md">
        <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center">
            <h2 class="grow text-3xl font-bold">Ordenes de compra</h2>

            <router-link
                to="/panel/maintenance-new/purchase-orders/new"
                class="rounded bg-[#18364a] px-4 py-2 text-center text-white"
            >
                Nueva orden
            </router-link>
        </div>

        <DataTable
            :data="items"
            :columns="columns"
            emptyMessage="No hay ordenes de compra registradas."
        >
            <template #actions="{ row }">
                <TableAction
                    title="Ver"
                    icon="edit.png"
                    :route="`/panel/maintenance-new/purchase-orders/${row.id}`"
                />
            </template>
        </DataTable>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { getPurchaseOrdersApi } from '../../../apis/PurchaseOrderApi';
import breadcrumb from '../../../components/breadcrumb.vue';
import DataTable from '../../../components/DataTable.vue';
import TableAction from '../../../components/TableAction.vue';

const breadcrumbItems = [
    { title: 'Mantenimientos' },
    { title: 'Ordenes de compra' },
];

const items = ref([]);

const columns = [
    { key: 'folio', label: 'OC', sortable: true, filterable: true },
    { key: 'work_order.folio', label: 'Folio OT', sortable: true, filterable: true },
    { key: 'work_order.unit.econame', label: 'Unidad', filterable: true },
    { key: 'supplier.name', label: 'Proveedor', filterable: true },
    {
        key: 'cost',
        label: 'Costo',
        sortable: true,
        formatter: (value) => Number(value).toLocaleString('es-MX', {
            style: 'currency',
            currency: 'MXN',
        }),
    },
    { key: 'status', label: 'Estado', filterable: true },
];

onMounted(async () => {
    const response = await getPurchaseOrdersApi();
    items.value = response.data;
});
</script>
