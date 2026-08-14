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
            <fieldset class="space-y-6">
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

                            <select v-model="item.supplier_id" required :disabled="isEditing">
                                <option value="">Seleccione</option>
                                <option v-for="supplier in catalogs.suppliers" :key="supplier.id" :value="supplier.id">
                                    {{ supplier.name }}
                                </option>
                            </select>
                        </div>

                        <div class="form-item">
                            <label>Costo *</label>
                            <input v-model.number="item.cost" type="number" min="0.01" step="0.01" required :disabled="isEditing">
                        </div>

                        <div class="form-item md:col-span-2">
                            <label>Descripcion *</label>
                            <textarea class="form-control" v-model="item.description" rows="4" required :disabled="isEditing" />
                        </div>

                    </div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">

                        <div class="form-item">
                            <label>Condicion de pago</label>

                            <select v-model="item.payment_condition" :disabled="isEditing && !canEditPaymentCondition">
                                <option :value="null">Por confirmar</option>
                                <option>Contado</option>
                                <option>Credito</option>
                            </select>

                            <p v-if="errors.payment_condition" class="text-sm text-red-500">
                                {{ errors.payment_condition[0] }}
                            </p>
                        </div>

                        <div v-if="item.payment_condition === 'Credito'" class="form-item">
                            <label>Dias de credito *</label>
                            <input
                                v-model.number="item.credit_days"
                                type="number"
                                min="1"
                                required
                                :disabled="isEditing && !canEditPaymentCondition"
                            >

                            <p v-if="errors.credit_days" class="text-sm text-red-500">
                                {{ errors.credit_days[0] }}
                            </p>
                        </div>

                        <div class="form-item">
                            <label>PDF de cotizacion *</label>
                            <input
                                v-if="!isEditing"
                                type="file"
                                accept="application/pdf"
                                required
                                @change="setFile('quotation', $event)"
                            >
                            <a v-if="item.quotation_url" :href="item.quotation_url" target="_blank" class="text-sm text-blue-600">
                                Ver cotizacion
                            </a>

                            <p v-if="errors.quotation" class="text-sm text-red-500">
                                {{ errors.quotation[0] }}
                            </p>
                        </div>

                        <div class="form-item">
                            <label>Evidencia *</label>
                            <input
                                v-if="!isEditing"
                                type="file"
                                accept="application/pdf,image/*"
                                required
                                @change="setFile('evidence', $event)"
                            >
                            <a v-if="item.evidence_url" :href="item.evidence_url" target="_blank" class="text-sm text-blue-600">
                                Ver evidencia
                            </a>

                            <p v-if="errors.evidence" class="text-sm text-red-500">
                                {{ errors.evidence[0] }}
                            </p>
                        </div>
                    </div>
                </section>
            </fieldset>

            <div class="flex justify-end gap-2 border-t pt-4">
                <router-link :to="workOrderReturnPath" class="rounded border px-4 py-2">
                    Regresar
                </router-link>

                <button
                    v-if="canSave"
                    type="submit"
                    class="rounded bg-[#18364a] px-4 py-2 text-white"
                >
                    {{ isEditing ? 'Actualizar condicion de pago' : 'Crear orden de compra' }}
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
    createPurchaseOrderApi,
    getPurchaseOrderDetailApi,
    updatePurchaseOrderPaymentConditionApi,
} from '../../../apis/PurchaseOrderApi';
import breadcrumb from '../../../components/breadcrumb.vue';
import { usePermissions } from '../../../composables/usePermissions';

const route = useRoute();
const router = useRouter();
const dialogs = inject('swal');
const { hasPermission } = usePermissions();
const isEditing = computed(() => route.params.id && route.params.id !== 'new');
const canEditPaymentCondition = computed(() => isEditing.value && hasPermission('maintenances.edit'));
const canSave = computed(() => isEditing.value
    ? canEditPaymentCondition.value
    : hasPermission('maintenances.create'));
const workOrderReturnPath = computed(() => item.work_order_id
    ? `/panel/maintenance-new/work-orders/${item.work_order_id}`
    : '/panel/maintenance-new/work-orders');

const breadcrumbItems = computed(() => [
    { title: 'Ordenes de trabajo', path: '/panel/maintenance-new/work-orders' },
    { title: item.work_order?.folio || 'Orden de trabajo', path: workOrderReturnPath.value },
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
        if (isEditing.value) {
            await updatePurchaseOrderPaymentConditionApi(item.id, {
                payment_condition: item.payment_condition,
                credit_days: item.payment_condition === 'Credito' ? item.credit_days : null,
            });
        } else {
            await createPurchaseOrderApi(buildFormData());
        }

        dialogs.fire('Excelente', 'Orden guardada correctamente', 'success');
        router.push(workOrderReturnPath.value);
        
    } catch (error) {
        errors.value = error.response?.data?.errors || {};
        dialogs.fire('Error', error.response?.data?.message || 'Revise los campos', 'error');
    }
}

</script>
