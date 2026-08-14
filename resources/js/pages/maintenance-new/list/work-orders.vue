<template>
    <breadcrumb :items="breadcrumbItems" />

    <div class="m-4 rounded bg-white p-4 shadow-md">
        <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center">
            <h2 class="grow text-3xl font-bold">Ordenes de trabajo</h2>

            <router-link
                v-if="hasPermission('maintenances.create')"
                to="/panel/maintenance-new/work-orders/new"
                class="rounded bg-[#18364a] px-4 py-2 text-center text-white"
            >
                Nueva orden
            </router-link>
        </div>

        <DataTable
            :data="items"
            :columns="columns"
            emptyMessage="No hay ordenes de trabajo registradas."
        >
            <template #actions="{ row }">
                <TableAction
                    title="Ver detalle"
                    icon="edit.png"
                    :route="`/panel/maintenance-new/work-orders/${row.id}`"
                />
            </template>
        </DataTable>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { getWorkOrdersApi } from '../../../apis/OrderWorkApi';
import breadcrumb from '../../../components/breadcrumb.vue';
import DataTable from '../../../components/DataTable.vue';
import TableAction from '../../../components/TableAction.vue';
import { usePermissions } from '../../../composables/usePermissions';

const breadcrumbItems = [
    { title: 'Mantenimientos' },
    { title: 'Ordenes de trabajo' },
];

const items = ref([]);
const { hasPermission } = usePermissions();

const columns = [
    { key: 'folio', label: 'Folio', sortable: true, filterable: true },
    { key: 'unit.econame', label: 'Unidad', sortable: true, filterable: true },
    {
        key: 'unit_category',
        label: 'Tipo',
        sortable: true,
        filterable: true,
        formatter: (value, row) => row.maintenance_type
            ? `${value} / ${row.maintenance_type}`
            : value,
    },
    { key: 'status', label: 'Estado', sortable: true, filterable: true },
    {
        key: 'responsible',
        label: 'Responsable',
        sortable: true,
        filterable: true,
        cellClass: 'whitespace-pre-line',
    },
    { key: 'purchase_orders_count', label: 'OC aprobadas', sortable: true },
    {
        key: 'total_cost',
        label: 'Costo aprobado',
        sortable: true,
        formatter: (value) => Number(value || 0).toLocaleString('es-MX', {
            style: 'currency',
            currency: 'MXN',
        }),
    },
];

onMounted(async () => {
    const response = await getWorkOrdersApi();
    items.value = response.data;
});
</script>
