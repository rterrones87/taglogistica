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
                :disabled="isEditing && item.status === 'Cerrado'"
                class="space-y-6 disabled:opacity-70"
            >
                <section>
                    <h3 class="mb-3 text-xl font-bold">Clasificacion</h3>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">

                        <div class="form-item">
                            <label>Tipo de unidad *</label>

                            <select v-model="item.unit_category" required>
                                <option value="">Seleccione</option>
                                <option v-for="category in categories" :key="category">
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
                            <label>Nombre del operador *</label>

                            <select v-model="item.operator_id" required>
                                <option value="">Seleccione</option>
                                <option v-for="operator in catalogs.operators" :key="operator.id" :value="operator.id">
                                    {{ operator.name }}
                                </option>
                            </select>

                            <ErrorText :errors="errors.operator_id" />
                        </div>

                        <div class="form-item">
                            <label>Mecanico responsable *</label>

                            <select v-model="item.mechanic_id" required>
                                <option value="">Seleccione</option>
                                <option v-for="mechanic in catalogs.mechanics" :key="mechanic.id" :value="mechanic.id">
                                    {{ mechanic.name }}
                                </option>
                            </select>

                            <ErrorText :errors="errors.mechanic_id" />
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

                        <div v-if="item.work_type === 'Externo'" class="form-item">
                            <label>Proveedor *</label>

                            <select v-model="item.supplier_id" required>
                                <option value="">Seleccione</option>
                                <option v-for="supplier in catalogs.suppliers" :key="supplier.id" :value="supplier.id">
                                    {{ supplier.name }}
                                </option>
                            </select>

                            <ErrorText :errors="errors.supplier_id" />
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

                <section v-if="isEditing && item.purchase_orders?.length" class="border-t pt-4">
                    <h3 class="mb-3 text-xl font-bold">Ordenes de compra vinculadas</h3>

                    <div
                        v-for="purchaseOrder in item.purchase_orders"
                        :key="purchaseOrder.id"
                        class="flex justify-between border-b py-2"
                    >
                        <span>
                            {{ purchaseOrder.folio }} - {{ purchaseOrder.supplier?.name }} ({{ purchaseOrder.status }})
                        </span>
                        <strong>{{ formatCurrency(purchaseOrder.cost) }}</strong>
                    </div>
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
                    v-if="isEditing && item.status !== 'Cerrado'"
                    :to="`/panel/maintenance-new/purchase-orders/new?work_order_id=${item.id}`"
                    class="rounded border border-[#18364a] px-4 py-2 text-[#18364a]"
                >
                    Agregar OC
                </router-link>

                <button
                    v-if="isEditing && item.status === 'Abierto' && hasPermission('maintenance_new.start_work_order')"
                    type="button"
                    class="rounded bg-amber-600 px-4 py-2 text-white"
                    @click="changeState('start')"
                >
                    Cambiar a En Proceso
                </button>

                <button
                    v-if="isEditing && item.status === 'En Proceso' && hasPermission('maintenance_new.close_work_order')"
                    type="button"
                    class="rounded bg-red-700 px-4 py-2 text-white"
                    @click="changeState('close')"
                >
                    Cerrar OT
                </button>

                <button
                    v-if="!isEditing || item.status !== 'Cerrado'"
                    type="submit"
                    class="rounded bg-[#18364a] px-4 py-2 text-white"
                >
                    {{ isEditing ? 'Actualizar' : 'Crear orden de trabajo' }}
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { computed, defineComponent, h, inject, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { getWorkshopCatalogsApi } from '../../../apis/WorkshopCatalogApi';
import {
    closeWorkOrderApi,
    createWorkOrderApi,
    getWorkOrderDetailApi,
    startWorkOrderApi,
    updateWorkOrderApi,
} from '../../../apis/OrderWorkApi';
import breadcrumb from '../../../components/breadcrumb.vue';
import { usePermissions } from '../../../composables/usePermissions';

const ErrorText = defineComponent({
    props: { errors: Array },
    setup: (props) => () => props.errors?.length
        ? h('p', { class: 'text-sm text-red-500' }, props.errors[0])
        : null,
});

const route = useRoute();
const router = useRouter();
const dialogs = inject('swal');
const { hasPermission } = usePermissions();
const isEditing = computed(() => route.params.id && route.params.id !== 'new');

const breadcrumbItems = computed(() => [
    { title: 'Ordenes de trabajo', path: '/panel/maintenance-new/work-orders' },
    { title: isEditing.value ? 'Detalle de OT' : 'Nueva OT' },
]);

const categories = [
    'Tractor', 'Remolque', 'Dolly', 'Plataforma', 'Caja Refrigerada',
    'Gastos de accidentes', 'Gastos de gruas', 'Mala operacion del operador', 'Rescate carretero',
];
const vehicleCategories = categories.slice(0, 5);

const item = reactive({
    unit_category: '',
    maintenance_type: '',
    unit_id: '',
    initial_mileage: 1,
    opened_at: new Date().toISOString().slice(0, 10),
    operator_id: '',
    mechanic_id: '',
    failure_description: '',
    work_type: '',
    supplier_id: null,
});

const catalogs = reactive({
    units: [],
    operators: [],
    mechanics: [],
    suppliers: [],
});
const errors = ref({});
const vehicleCategory = computed(() => vehicleCategories.includes(item.unit_category));

onMounted(async () => {
    Object.assign(catalogs, await getWorkshopCatalogsApi());

    if (isEditing.value) {
        const response = await getWorkOrderDetailApi(route.params.id);
        Object.assign(item, response.data);
    }
});

async function save() {
    try {
        errors.value = {};
        if (item.work_type !== 'Externo') item.supplier_id = null;
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
    }
}

async function changeState(action) {
    const label = action === 'start' ? 'iniciar el trabajo' : 'cerrar la orden';
    const result = await dialogs.fire({
        title: `¿Desea ${label}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Si, continuar',
        cancelButtonText: 'Cancelar',
    });

    if (!result.isConfirmed) return;

    try {
        const response = action === 'start'
            ? await startWorkOrderApi(item.id)
            : await closeWorkOrderApi(item.id);
        Object.assign(item, response.data);
        dialogs.fire('Excelente', 'Estado actualizado correctamente', 'success');
    } catch (error) {
        dialogs.fire('Error', error.response?.data?.message || 'No fue posible cambiar el estado', 'error');
    }
}

function formatDateTime(value) {
    return value ? new Date(value).toLocaleString('es-MX') : '';
}

function formatCurrency(value) {
    return Number(value || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
}
</script>
