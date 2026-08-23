<template>
    <breadcrumb :items="breadcrumbItems" />

    <div class="m-4 rounded bg-white p-4 shadow-md">
        <div class="flex items-center gap-3">
            <h2 class="my-4 grow text-3xl font-bold">
                {{ isEditing ? item.folio : 'Nueva orden de trabajo' }}
            </h2>

            <span v-if="isEditing" class="rounded bg-gray-100 px-3 py-1 font-semibold">
                {{ item.status }}
            </span>
        </div>

        <form class="space-y-6" @submit.prevent="save">
            <fieldset
                
                class="space-y-6 "
            >
                <section>
                    <h3 class="mb-3 text-xl font-bold">Clasificacion</h3>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">

                        <div class="form-item">
                            <label>Tipo de unidad *</label>

                            <select v-model="item.unit_category" required>
                                <option value="">Seleccione</option>
                                <option v-for="category in workOrderCategories" :key="category">
                                    {{ category }}
                                </option>
                            </select>

                            <ErrorText :errors="errors.unit_category" />
                        </div>

                        <div v-if="vehicleCategory" class="form-item">
                            <label>Tipo de mantenimiento *</label>

                            <select v-model="item.maintenance_type" required>
                                <option value="">Seleccione</option>
                                <option>Preventivo</option>
                                <option>Correctivo</option>
                            </select>

                            <ErrorText :errors="errors.maintenance_type" />
                        </div>

                        <div class="form-item">
                            <label>Unidad economica *</label>

                            <select v-model="item.unit_id" required>
                                <option value="">Seleccione</option>
                                <option v-for="unit in catalogs.units" :key="unit.id" :value="unit.id">
                                    {{ unit.econame }}
                                </option>
                            </select>

                            <ErrorText :errors="errors.unit_id" />
                        </div>

                        <div class="form-item">
                            <label>Kilometraje inicial *</label>
                            <input v-model.number="item.initial_mileage" type="number" min="1" required>
                            <ErrorText :errors="errors.initial_mileage" />
                        </div>
                    </div>
                </section>

                <section>
                    <h3 class="mb-3 text-xl font-bold">Datos de operacion</h3>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        <div class="form-item">
                            <label>Fecha de apertura *</label>
                            <input v-model="item.opened_at" type="date" required>
                            <ErrorText :errors="errors.opened_at" />
                        </div>

                        <div class="form-item">
                            <label>Tipo de trabajo *</label>

                            <select v-model="item.work_type" required>
                                <option value="">Seleccione</option>
                                <option>Interno</option>
                                <option>Externo</option>
                            </select>

                            <ErrorText :errors="errors.work_type" />
                        </div>

                        <div class="form-item">
                            <label>Nombre del operador *</label>

                            <select v-model="item.operator_id" required>
                                <option value="">Seleccione</option>
                                <option v-for="operator in catalogs.operators" :key="operator.id" :value="operator.id">
                                    {{ operator.name }}
                                </option>
                            </select>

                            <ErrorText :errors="errors.operator_id" />
                        </div>

                        <div v-if="item.work_type !== 'Externo'" class="form-item">
                            <label>Mecanico responsable *</label>

                            <select v-model="item.mechanic_id" required>
                                <option value="">Seleccione</option>
                                <option v-for="mechanic in catalogs.mechanics" :key="mechanic.id" :value="mechanic.id">
                                    {{ mechanic.name }}
                                </option>
                            </select>

                            <ErrorText :errors="errors.mechanic_id" />
                        </div>


                    </div>
                </section>

                <section>
                    <h3 class="mb-3 text-xl font-bold">Detalle del trabajo</h3>

                    <div class="form-item">
                        <label>Descripcion de la falla *</label>
                        <textarea class="form-control" v-model="item.failure_description" rows="4" required />
                        <ErrorText :errors="errors.failure_description" />
                    </div>
                </section>

                <section v-if="isEditing" class="border-t pt-4">
                    <h3 class="mb-3 text-xl font-bold">Ordenes de compra</h3>

                    <div
                        v-if="isLoadingPurchaseOrders"
                        class="flex min-h-[260px] items-center justify-center"
                    >
                        <div class="flex flex-col items-center gap-3 text-gray-600">
                            <span class="h-10 w-10 animate-spin rounded-full border-4 border-[#18364a] border-t-transparent"></span>

                            <span>Cargando órdenes de compra...</span>
                        </div>
                    </div>

                    <DataTable
                        v-else
                        :data="item.purchase_orders || []"
                        :columns="purchaseOrderColumns"
                        emptyMessage="Esta orden de trabajo no tiene ordenes de compra."
                    >
                        <template #actions="{ row }">
                            <TableAction
                                title="Ver detalle"
                                icon="info.png"
                                :route="`/panel/maintenance-new/purchase-orders/${row.id}`"
                            />
                        </template>
                    </DataTable>
                </section>
            </fieldset>

            <div v-if="item.started_at || item.closed_at" class="border-t pt-3 text-sm text-gray-600">
                <p v-if="item.started_at">
                    Iniciada por {{ item.started_by_user?.name || 'Usuario' }} el {{ formatDateTime(item.started_at) }}
                </p>
                <p v-if="item.closed_at">
                    Cerrada por {{ item.closed_by_user?.name || 'Usuario' }} el {{ formatDateTime(item.closed_at) }}
                </p>
            </div>

            <div class="flex flex-wrap justify-end gap-2 border-t pt-4">
                <router-link to="/panel/maintenance-new/work-orders" class="rounded border px-4 py-2">
                    Cancelar
                </router-link>

                <router-link
                    v-if="isEditing && item.status === 'En Proceso' && hasPermission('maintenances.create')"
                    :to="`/panel/maintenance-new/purchase-orders/new?work_order_id=${item.id}`"
                    class="rounded border border-[#18364a] px-4 py-2 text-[#18364a]"
                >
                    Agregar OC
                </router-link>

                <button
                    v-if="isEditing && item.status === 'Abierto' && hasPermission('maintenances.change_state')"
                    type="button"
                    class="rounded bg-amber-600 px-4 py-2 text-white"
                    @click="changeState(1)"
                >
                    Cambiar a En Proceso
                </button>

                <button
                    v-if="canFinishWorkOrder"
                    type="button"
                    class="rounded bg-red-700 px-4 py-2 text-white"
                    @click="changeState(2)"
                >
                    Finalizar
                </button>

                <button
                    v-if="canSave"
                    type="submit"
                    :disabled="isSaving"
                    class="flex items-center gap-2 rounded bg-[#18364a] px-4 py-2 text-white disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <span
                        v-if="isSaving"
                        class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                    ></span>

                    {{ isSaving ? 'Guardando...' : (isEditing ? 'Actualizar' : 'Crear orden de trabajo') }}
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { computed, inject, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { getWorkshopCatalogsApi } from '../../../apis/WorkshopCatalogApi';
import {
    changeWorkOrderStatusApi,
    createWorkOrderApi,
    getWorkOrderDetailApi,
    updateWorkOrderApi,
} from '../../../apis/OrderWorkApi';
import breadcrumb from '../../../components/breadcrumb.vue';
import DataTable from '../../../components/DataTable.vue';
import ErrorText from '../../../components/ErrorText.vue';
import TableAction from '../../../components/TableAction.vue';
import { usePermissions } from '../../../composables/usePermissions';
import {
    itemWorkOrder,
    workshopCatalogs,
    formatCurrency,
    purchaseOrderColumns,
    vehicleCategories,
    workOrderCategories,
} from '../config/workOrderForm';

const route = useRoute();
const router = useRouter();
const dialogs = inject('swal');
const { hasPermission } = usePermissions();
const isEditing = computed(() => route.params.id && route.params.id !== 'new');
const canSave = computed(() => isEditing.value
    ? item.status !== 'Finalizado' && hasPermission('maintenances.edit')
    : hasPermission('maintenances.create'));

const breadcrumbItems = computed(() => [
    { title: 'Ordenes de trabajo', path: '/panel/maintenance-new/work-orders' },
    { title: isEditing.value ? 'Detalle de OT' : 'Nueva OT' },
]);

const item = reactive(itemWorkOrder());
const catalogs = reactive(workshopCatalogs());
const errors = ref({});
const isLoadingPurchaseOrders = ref(isEditing.value);
const isSaving = ref(false);
const vehicleCategory = computed(() => vehicleCategories.includes(item.unit_category));
const canFinishWorkOrder = computed(() => isEditing.value
    && item.status === 'En Proceso'
    && !(item.purchase_orders || []).some((purchaseOrder) => purchaseOrder.status === 'Pendiente')
    && hasPermission('maintenances.change_state'));

onMounted(async () => {
    try {
        if (isEditing.value) {
            const [catalogData, response] = await Promise.all([
                getWorkshopCatalogsApi(),
                getWorkOrderDetailApi(route.params.id),
            ]);

            Object.assign(catalogs, catalogData);
            Object.assign(item, response.data);
        } else {
            Object.assign(catalogs, await getWorkshopCatalogsApi());
        }
    } catch (error) {
        dialogs.fire('Error', error.response?.data?.message || 'No fue posible cargar la orden', 'error');
        router.push('/panel/maintenance-new/work-orders');
    } finally {
        isLoadingPurchaseOrders.value = false;
    }
});

async function save() {
    if (isSaving.value) return;

    isSaving.value = true;

    try {
        errors.value = {};
        if (item.work_type === 'Externo') item.mechanic_id = null;
        if (!vehicleCategory.value) item.maintenance_type = null;

        if (isEditing.value) {
            await updateWorkOrderApi(item.id, item);
        } else {
            await createWorkOrderApi(item);
        }

        dialogs.fire('Excelente', 'Orden guardada correctamente', 'success');
        router.push('/panel/maintenance-new/work-orders');
    } catch (error) {
        errors.value = error.response?.data?.errors || {};
        dialogs.fire('Error', error.response?.data?.message || 'Revise los campos', 'error');
    } finally {
        isSaving.value = false;
    }
}

async function changeState(status) {
    const label = status === 1 ? 'iniciar el trabajo' : 'finalizar la orden';
    const result = await dialogs.fire({
        title: `¿Desea ${label}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Si, continuar',
        cancelButtonText: 'Cancelar',
    });

    if (!result.isConfirmed) return;

    try {
        const response = await changeWorkOrderStatusApi(item.id, status);
        Object.assign(item, response.data);
        dialogs.fire('Excelente', 'Estado actualizado correctamente', 'success');
    } catch (error) {
        dialogs.fire('Error', error.response?.data?.message || 'No fue posible cambiar el estado', 'error');
    }
}

function formatDateTime(value) {
    return value ? new Date(value).toLocaleString('es-MX') : '';
}

</script>
