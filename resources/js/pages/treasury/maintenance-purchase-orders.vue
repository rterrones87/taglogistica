<template>
    <breadcrumb :items="breadcrumbItems" />

    <div class="m-4 rounded bg-white p-4 shadow-md">
        <div class="flex flex-col gap-3 md:flex-row md:items-center">
            <h2 class="grow text-center text-3xl font-bold md:text-left">
                Mantenimientos por pagar
            </h2>

            <button
                type="button"
                class="rounded bg-[#18364a] px-4 py-2 text-white hover:bg-[#234053]"
                @click="downloadPdf"
            >
                Descargar PDF
            </button>
        </div>

        <SegmentedControl
            v-model="currentTab"
            :titles="tabs"
            @OnClicked="changeTab"
        />

        <div class="mb-4 flex flex-col items-end justify-end gap-2 md:flex-row md:items-center">
            <label class="flex cursor-pointer items-center gap-2">
                <input
                    v-model="dateFilterEnabled"
                    type="checkbox"
                    class="h-4 w-4"
                />

                Filtrar por fecha
            </label>

            <WeekNavigator
                v-model="dateRange"
                :disabled="!dateFilterEnabled"
            />
        </div>

        <div v-if="isLoading" class="flex min-h-[320px] items-center justify-center">
            <div class="flex flex-col items-center gap-3 text-gray-600">
                <span class="h-10 w-10 animate-spin rounded-full border-4 border-[#18364a] border-t-transparent"></span>

                <span>Cargando órdenes de compra...</span>
            </div>
        </div>

        <DataTable
            v-else
            :data="items"
            :columns="columns"
            :onReload="loadItems"
            emptyMessage="No hay órdenes de compra en esta pestaña."
        >
            <template #actions="{ row }">
                <div class="flex justify-center gap-2">
                    <TableAction
                        v-if="row.status === 'Pendiente' && hasPermission('treasury.apply_payment')"
                        title="Marcar como pagado"
                        icon="cost.png"
                        @click.prevent="markAsPaid(row)"
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
        @close="closeDetails"
    >
        <div v-if="isLoadingDetail" class="flex min-h-[240px] items-center justify-center">
            <span class="h-10 w-10 animate-spin rounded-full border-4 border-[#18364a] border-t-transparent"></span>
        </div>

        <div v-else-if="selectedItem" class="space-y-4">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <p><strong>OC:</strong> {{ selectedItem.purchase_order?.folio }}</p>
                <p><strong>OT:</strong> {{ selectedItem.purchase_order?.work_order?.folio }}</p>
                <p><strong>Unidad:</strong> {{ selectedItem.purchase_order?.work_order?.unit?.econame || 'N/A' }}</p>
                <p><strong>Proveedor:</strong> {{ selectedItem.purchase_order?.supplier?.name || 'N/A' }}</p>
                <p><strong>Costo con IVA:</strong> {{ formatCurrency(selectedItem.purchase_order?.cost) }}</p>
                <p><strong>Estado:</strong> {{ selectedItem.status }}</p>
                <p><strong>Condición de pago:</strong> {{ paymentCondition(selectedItem.purchase_order) }}</p>
                <p v-if="selectedItem.paid_at"><strong>Fecha de pago:</strong> {{ formatDate(selectedItem.paid_at) }}</p>
                <p class="md:col-span-2"><strong>Descripción:</strong> {{ selectedItem.purchase_order?.description }}</p>
            </div>

            <div class="border-t pt-4">
                <h3 class="mb-2 text-lg font-semibold">Archivos</h3>

                <a
                    v-if="selectedItem.purchase_order?.quotation_file?.url"
                    :href="selectedItem.purchase_order.quotation_file.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mb-2 block text-blue-600 hover:underline"
                >
                    Ver cotización
                </a>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <a
                        v-for="(file, index) in selectedItem.purchase_order?.evidence_files || []"
                        :key="file.id"
                        :href="file.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="rounded border p-2 text-blue-600 hover:bg-gray-50"
                    >
                        <img
                            v-if="file.is_image"
                            :src="file.url"
                            :alt="`Evidencia ${index + 1}`"
                            class="mb-2 h-32 w-full rounded object-cover"
                        />

                        Ver evidencia {{ index + 1 }}
                    </a>
                </div>
            </div>
        </div>
    </BaseModal>
</template>

<script setup>
import { computed, inject, onMounted, ref, watch } from 'vue';
import {
    getTreasuryMaintenanceDetailApi,
    getTreasuryMaintenancesApi,
    payTreasuryMaintenanceApi,
} from '../../apis/TreasuryMaintenanceApi';
import BaseModal from '../../components/BaseModal.vue';
import DataTable from '../../components/DataTable.vue';
import SegmentedControl from '../../components/SegmentedControl.vue';
import TableAction from '../../components/TableAction.vue';
import WeekNavigator from '../../components/WeekNavigator.vue';
import breadcrumb from '../../components/breadcrumb.vue';
import { usePermissions } from '../../composables/usePermissions';
import {
    downloadTreasuryMaintenancesPdf,
    formatCurrency,
    formatDate,
    paymentCondition,
    treasuryMaintenanceBreadcrumbItems,
    treasuryMaintenanceColumns,
    treasuryMaintenanceTabs,
} from './config/maintenancePurchaseOrders';

const dialogs = inject('swal');
const { hasPermission } = usePermissions();
const items = ref([]);
const currentTab = ref('pending');
const dateFilterEnabled = ref(false);
const dateRange = ref({ start: '', end: '' });
const isLoading = ref(false);
const isLoadingDetail = ref(false);
const showModal = ref(false);
const selectedItem = ref(null);

const breadcrumbItems = treasuryMaintenanceBreadcrumbItems;
const tabs = treasuryMaintenanceTabs;
const columns = treasuryMaintenanceColumns;

const filters = computed(() => ({
    status: currentTab.value === 'pending'
        ? 'Pendiente'
        : currentTab.value === 'paid' ? 'Pagado' : undefined,
    start_date: dateFilterEnabled.value ? dateRange.value.start : undefined,
    end_date: dateFilterEnabled.value ? dateRange.value.end : undefined,
}));

onMounted(loadItems);

watch([dateFilterEnabled, dateRange], () => {
    loadItems();
}, { deep: true });

async function loadItems() {
    try {
        isLoading.value = true;
        const response = await getTreasuryMaintenancesApi(filters.value);
        items.value = response.data;
    } catch (error) {
        dialogs.fire('Error', error.response?.data?.message || 'No fue posible consultar Tesorería.', 'error');
    } finally {
        isLoading.value = false;
    }
}

function changeTab(tab) {
    currentTab.value = tab;
    loadItems();
}

async function markAsPaid(item) {
    const result = await dialogs.fire({
        title: 'Marcar como pagado',
        text: `¿Confirma que la orden ${item.purchase_order?.folio} ya fue pagada?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, marcar como pagado',
        cancelButtonText: 'Cancelar',
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        await payTreasuryMaintenanceApi(item.id);
        await loadItems();
        dialogs.fire('Excelente', 'La orden de compra fue marcada como pagada.', 'success');
    } catch (error) {
        dialogs.fire('Error', error.response?.data?.message || 'No fue posible registrar el pago.', 'error');
    }
}

async function showDetails(id) {
    showModal.value = true;
    isLoadingDetail.value = true;
    selectedItem.value = null;

    try {
        const response = await getTreasuryMaintenanceDetailApi(id);
        selectedItem.value = response.data;
    } catch (error) {
        showModal.value = false;
        dialogs.fire('Error', error.response?.data?.message || 'No fue posible consultar el detalle.', 'error');
    } finally {
        isLoadingDetail.value = false;
    }
}

function closeDetails() {
    showModal.value = false;
    selectedItem.value = null;
}

function downloadPdf() {
    if (!items.value.length) {
        dialogs.fire('Sin información', 'No hay registros para descargar con los filtros actuales.', 'info');
        return;
    }

    downloadTreasuryMaintenancesPdf(items.value, currentTab.value);
}
</script>
