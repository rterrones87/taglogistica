<template>
    <breadcrumb :items="breadcrumbItems" />

    <div class="m-4 rounded bg-white p-4 shadow-md">
        <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center">
            <h2 class="grow text-3xl font-bold">Reporte de fallas</h2>

            <router-link
                to="/panel/maintenance-new/failure-reports/new"
                class="rounded bg-[#18364a] px-4 py-2 text-center text-white"
            >
                Nuevo reporte
            </router-link>
        </div>

        <DataTable
            :data="items"
            :columns="columns"
            emptyMessage="No hay reportes de falla registrados."
        >
            <template #actions="{ row }">
                <TableAction
                    title="Ver"
                    icon="edit.png"
                    :route="`/panel/maintenance-new/failure-reports/${row.id}`"
                />
            </template>
        </DataTable>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { getFailureReportsApi } from '../../../apis/FailureReportApi';
import breadcrumb from '../../../components/breadcrumb.vue';
import DataTable from '../../../components/DataTable.vue';
import TableAction from '../../../components/TableAction.vue';

const breadcrumbItems = [
    { title: 'Mantenimientos' },
    { title: 'Reporte de fallas' },
];

const items = ref([]);

const columns = [
    { key: 'folio', label: 'Folio', sortable: true, filterable: true },
    { key: 'reported_at', label: 'Fecha', sortable: true },
    { key: 'unit.econame', label: 'Unidad', filterable: true },
    { key: 'operator.name', label: 'Operador', filterable: true },
    { key: 'mileage', label: 'Kilometraje', sortable: true },
    { key: 'description', label: 'Descripcion', filterable: true },
    { key: 'status', label: 'Estado', filterable: true },
];

onMounted(async () => {
    const response = await getFailureReportsApi();
    items.value = response.data;
});
</script>
