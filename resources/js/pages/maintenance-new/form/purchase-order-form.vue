<template>
    <breadcrumb :items="breadcrumbItems" />

    <div class="m-4 rounded bg-white p-4 shadow-md">
        <div class="flex items-center gap-3">
            <h2 class="my-4 grow text-3xl font-bold">
                {{ isEditing ? item.folio : 'Nueva orden de compra' }}
            </h2>

            <span v-if="isEditing" class="rounded bg-gray-100 px-3 py-1 font-semibold">
                {{ item.status }}
            </span>
        </div>

        <form class="space-y-6" enctype="multipart/form-data" @submit.prevent="save">
            <fieldset
                :disabled="isEditing && item.status === 'Cerrada'"
                class="space-y-6 disabled:opacity-70"
            >
                <section>
                    <h3 class="mb-3 text-xl font-bold">Orden de trabajo relacionada</h3>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div class="form-item">
                            <label>Folio de OT *</label>

                            <select v-model="item.work_order_id" required :disabled="isEditing">
                                <option value="">Seleccione</option>
                                <option v-for="order in catalogs.work_orders" :key="order.id" :value="order.id">
                                    {{ order.folio }} - {{ order.unit?.econame }}
                                </option>
                            </select>

                            <p v-if="errors.work_order_id" class="text-sm text-red-500">
                                {{ errors.work_order_id[0] }}
                            </p>
                        </div>

                        <div class="form-item">
                            <label>Unidad economica</label>
                            <input :value="selectedUnitName" disabled>
                        </div>
                    </div>
                </section>

                <section>
                    <h3 class="mb-3 text-xl font-bold">Datos de la compra</h3>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div class="form-item">
                            <label>Proveedor *</label>

                            <select v-model="item.supplier_id" required>
                                <option value="">Seleccione</option>
                                <option v-for="supplier in catalogs.suppliers" :key="supplier.id" :value="supplier.id">
                                    {{ supplier.name }}
                                </option>
                            </select>
                        </div>

                        <div class="form-item">
                            <label>Costo *</label>
                            <input v-model.number="item.cost" type="number" min="0.01" step="0.01" required>
                        </div>

                        <div class="form-item md:col-span-2">
                            <label>Descripcion *</label>
                            <textarea class="form-control" v-model="item.description" rows="4" required />
                        </div>

                        <div class="form-item">
                            <label>Condicion de pago</label>

                            <select v-model="item.payment_condition">
                                <option :value="null">Por confirmar</option>
                                <option>Contado</option>
                                <option>Credito</option>
                            </select>
                        </div>

                        <div v-if="item.payment_condition === 'Credito'" class="form-item">
                            <label>Dias de credito *</label>
                            <input v-model.number="item.credit_days" type="number" min="1" required>
                        </div>

                        <div class="form-item">
                            <label>PDF de cotizacion</label>
                            <input type="file" accept="application/pdf" @change="setFile('quotation', $event)">
                            <a v-if="item.quotation_url" :href="item.quotation_url" target="_blank" class="text-sm text-blue-600">
                                Ver cotizacion actual
                            </a>
                        </div>

                        <div class="form-item">
                            <label>Evidencia</label>
                            <input type="file" accept="application/pdf,image/*" @change="setFile('evidence', $event)">
                            <a v-if="item.evidence_url" :href="item.evidence_url" target="_blank" class="text-sm text-blue-600">
                                Ver evidencia actual
                            </a>
                        </div>
                    </div>
                </section>
            </fieldset>

            <p v-if="item.closed_at" class="text-sm text-gray-600">
                Cerrada por {{ item.closed_by_user?.name || 'Usuario' }} el {{ formatDateTime(item.closed_at) }}
            </p>

            <div class="flex justify-end gap-2 border-t pt-4">
                <router-link to="/panel/maintenance-new/purchase-orders" class="rounded border px-4 py-2">
                    Cancelar
                </router-link>

                <button
                    v-if="isEditing && item.status === 'Abierta' && hasPermission('maintenance_new.close_purchase_order')"
                    type="button"
                    class="rounded bg-red-700 px-4 py-2 text-white"
                    @click="closeOrder"
                >
                    Cerrar OC
                </button>

                <button
                    v-if="!isEditing || item.status !== 'Cerrada'"
                    type="submit"
                    class="rounded bg-[#18364a] px-4 py-2 text-white"
                >
                    {{ isEditing ? 'Actualizar' : 'Crear orden de compra' }}
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
    closePurchaseOrderApi,
    createPurchaseOrderApi,
    getPurchaseOrderDetailApi,
    updatePurchaseOrderApi,
} from '../../../apis/PurchaseOrderApi';
import breadcrumb from '../../../components/breadcrumb.vue';
import { usePermissions } from '../../../composables/usePermissions';

const route = useRoute();
const router = useRouter();
const dialogs = inject('swal');
const { hasPermission } = usePermissions();
const isEditing = computed(() => route.params.id && route.params.id !== 'new');

const breadcrumbItems = computed(() => [
    { title: 'Ordenes de compra', path: '/panel/maintenance-new/purchase-orders' },
    { title: isEditing.value ? 'Detalle de OC' : 'Nueva OC' },
]);

const item = reactive({
    work_order_id: route.query.work_order_id ? Number(route.query.work_order_id) : '',
    supplier_id: '',
    description: '',
    cost: '',
    payment_condition: null,
    credit_days: null,
});

const catalogs = reactive({ work_orders: [], suppliers: [] });
const errors = ref({});
const quotation = ref(null);
const evidence = ref(null);

const selectedWorkOrder = computed(() => catalogs.work_orders.find((order) => order.id === item.work_order_id));
const selectedUnitName = computed(() => selectedWorkOrder.value?.unit?.econame || item.work_order?.unit?.econame || '');

onMounted(async () => {

    Object.assign(catalogs, await getWorkshopCatalogsApi());

    if (isEditing.value) {
        const response = await getPurchaseOrderDetailApi(route.params.id);
        console.log(response.data)
        Object.assign(item, response.data);
    }
});

function setFile(type, event) {
    const file = event.target.files[0] || null;

    if (type === 'quotation') {
        quotation.value = file;
    } else {
        evidence.value = file;
    }
}

function buildFormData() {
    const formData = new FormData();

    Object.entries(item).forEach(([key, value]) => {
        if (value !== null && typeof value !== 'object') {
            formData.append(key, value);
        }
    });

    if (quotation.value) formData.append('quotation', quotation.value);
    if (evidence.value) formData.append('evidence', evidence.value);

    return formData;
}

async function save() {
    try {
        errors.value = {};
        const formData = buildFormData();

        if (isEditing.value) {
            await updatePurchaseOrderApi(item.id, formData);
        } else {
            await createPurchaseOrderApi(formData);
        }

        dialogs.fire('Excelente', 'Orden guardada correctamente', 'success');
        router.push('/panel/maintenance-new/purchase-orders');
        
    } catch (error) {
        errors.value = error.response?.data?.errors || {};
        dialogs.fire('Error', error.response?.data?.message || 'Revise los campos', 'error');
    }
}

async function closeOrder() {
    const result = await dialogs.fire({
        title: '¿Desea cerrar esta orden de compra?',
        text: 'Debe contar con cotizacion y evidencia adjuntas.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Si, cerrar',
        cancelButtonText: 'Cancelar',
    });

    if (!result.isConfirmed) return;

    try {
        const response = await closePurchaseOrderApi(item.id);
        Object.assign(item, response.data);
        dialogs.fire('Excelente', 'Orden de compra cerrada', 'success');
    } catch (error) {
        dialogs.fire('Error', error.response?.data?.message || 'No fue posible cerrar la orden', 'error');
    }
}

function formatDateTime(value) {
    return value ? new Date(value).toLocaleString('es-MX') : '';
}
</script>
