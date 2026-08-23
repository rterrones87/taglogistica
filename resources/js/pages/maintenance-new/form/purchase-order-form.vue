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
                            <label>Costo con IVA *</label>
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

                    </div>
                </section>

                <section class="border-t pt-5">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h3 class="text-xl font-bold">Archivos de la orden</h3>
                            <p class="text-sm text-gray-500">
                                Una cotizacion en PDF y hasta cinco evidencias.
                            </p>
                        </div>

                        <span
                            v-if="!canManageFiles"
                            class="rounded bg-gray-100 px-3 py-1 text-sm text-gray-600"
                        >
                            No cuenta con permiso para administrar archivos
                        </span>
                    </div>

                    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                        <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4">
                            <div class="mb-3 flex items-center justify-between gap-2">
                                <div>
                                    <h4 class="font-semibold">Cotizacion</h4>
                                    <p class="text-xs text-gray-500">PDF, maximo 10 MB</p>
                                </div>

                                <span class="rounded bg-blue-100 px-2 py-1 text-xs text-blue-700">
                                    1 archivo
                                </span>
                            </div>

                            <label
                                v-if="canManageFiles && !currentQuotation"
                                class="block cursor-pointer rounded border-2 border-dashed border-blue-300 bg-white p-4 text-center text-sm text-blue-700 hover:bg-blue-50"
                            >
                                Seleccionar cotizacion
                                <input
                                    class="hidden"
                                    type="file"
                                    accept="application/pdf"
                                    @change="selectQuotation"
                                >
                            </label>

                            <div v-if="currentQuotation" class="mt-3 flex items-center gap-3 rounded border bg-white p-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded bg-red-100 font-bold text-red-700">
                                    PDF
                                </div>

                                <div class="min-w-0 grow">
                                    <p class="truncate text-sm font-medium">{{ currentQuotation.name }}</p>

                                    <a
                                        v-if="currentQuotation.url"
                                        :href="currentQuotation.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-xs text-blue-600 hover:underline"
                                    >
                                        Ver archivo
                                    </a>
                                </div>

                                <button
                                    v-if="canManageFiles"
                                    type="button"
                                    class="rounded px-2 py-1 text-sm text-red-600 hover:bg-red-50"
                                    @click="removeFile(currentQuotation, 'quotation')"
                                >
                                    Eliminar
                                </button>
                            </div>

                            <p v-if="errors.quotation" class="mt-2 text-sm text-red-500">
                                {{ errors.quotation[0] }}
                            </p>
                        </div>

                        <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4">
                            <div class="mb-3 flex items-center justify-between gap-2">
                                <div>
                                    <h4 class="font-semibold">Evidencias</h4>
                                    <p class="text-xs text-gray-500">PDF o imagen, maximo 10 MB por archivo</p>
                                </div>

                                <span class="rounded bg-blue-100 px-2 py-1 text-xs text-blue-700">
                                    {{ currentEvidences.length }} / 5
                                </span>
                            </div>

                            <label
                                v-if="canManageFiles && currentEvidences.length < 5"
                                class="block cursor-pointer rounded border-2 border-dashed border-blue-300 bg-white p-4 text-center text-sm text-blue-700 hover:bg-blue-50"
                            >
                                Seleccionar evidencias
                                <input
                                    class="hidden"
                                    type="file"
                                    accept="application/pdf,image/jpeg,image/png,image/webp"
                                    multiple
                                    @change="selectEvidences"
                                >
                            </label>

                            <div v-if="currentEvidences.length" class="mt-3 space-y-2">
                                <div
                                    v-for="(file, index) in currentEvidences"
                                    :key="file.id || `${file.name}-${index}`"
                                    class="flex items-center gap-3 rounded border bg-white p-3"
                                >
                                    <img
                                        v-if="file.preview || (file.is_image && file.url)"
                                        :src="file.preview || file.url"
                                        alt="Vista previa de evidencia"
                                        class="h-10 w-10 rounded object-cover"
                                    >

                                    <div
                                        v-else
                                        class="flex h-10 w-10 items-center justify-center rounded bg-gray-100 text-xs font-bold text-gray-600"
                                    >
                                        {{ file.is_image ? 'IMG' : 'PDF' }}
                                    </div>

                                    <div class="min-w-0 grow">
                                        <p class="truncate text-sm font-medium">{{ file.name }}</p>

                                        <a
                                            v-if="file.url"
                                            :href="file.url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="text-xs text-blue-600 hover:underline"
                                        >
                                            Ver archivo
                                        </a>
                                    </div>

                                    <button
                                        v-if="canManageFiles"
                                        type="button"
                                        class="rounded px-2 py-1 text-sm text-red-600 hover:bg-red-50"
                                        @click="removeFile(file, 'evidence')"
                                    >
                                        Eliminar
                                    </button>
                                </div>
                            </div>

                            <p v-if="errors.evidences" class="mt-2 text-sm text-red-500">
                                {{ errors.evidences[0] }}
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
                    :disabled="isSaving"
                    class="flex items-center gap-2 rounded bg-[#18364a] px-4 py-2 text-white disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <span
                        v-if="isSaving"
                        class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                    ></span>

                    {{ isSaving ? 'Guardando...' : (isEditing ? 'Actualizar' : 'Crear orden de compra') }}
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
    updatePurchaseOrderApi,
} from '../../../apis/PurchaseOrderApi';
import breadcrumb from '../../../components/breadcrumb.vue';
import { usePermissions } from '../../../composables/usePermissions';
import {
    purchaseOrderCatalogs,
    itemPurchaseOrder,
    MAX_EVIDENCE_FILES,
} from '../config/purchaseOrderForm';

const route = useRoute();
const router = useRouter();
const dialogs = inject('swal');
const { hasPermission } = usePermissions();
const isEditing = computed(() => route.params.id && route.params.id !== 'new');
const canEditPaymentCondition = computed(() => isEditing.value && hasPermission('maintenances.edit'));
const canManageFiles = computed(() => isEditing.value
    ? hasPermission('maintenances.edit')
    : hasPermission('maintenances.create'));
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

const item = reactive(itemPurchaseOrder(route.query.work_order_id));
const catalogs = reactive(purchaseOrderCatalogs());
const errors = ref({});
const isSaving = ref(false);
const quotation = ref(null);
const evidences = ref([]);
const deletedFileIds = ref([]);

const selectedWorkOrder = computed(() => catalogs.work_orders.find((order) => order.id === item.work_order_id));
const selectedUnitName = computed(() => selectedWorkOrder.value?.unit?.econame || item.work_order?.unit?.econame || '');
const currentQuotation = computed(() => {
    if (quotation.value) return { name: quotation.value.name, file: quotation.value };
    if (item.quotation_file && !deletedFileIds.value.includes(item.quotation_file.id)) {
        return item.quotation_file;
    }

    return null;
});
const currentEvidences = computed(() => [
    ...(item.evidence_files || []).filter((file) => !deletedFileIds.value.includes(file.id)),
    ...evidences.value,
]);

onMounted(async () => {
    try {
        if (isEditing.value) {
            const [catalogData, response] = await Promise.all([
                getWorkshopCatalogsApi(),
                getPurchaseOrderDetailApi(route.params.id),
            ]);

            Object.assign(catalogs, catalogData);
            Object.assign(item, response.data);
        } else {
            Object.assign(catalogs, await getWorkshopCatalogsApi());
        }
    } catch (error) {
        dialogs.fire('Error', error.response?.data?.message || 'No fue posible cargar la orden', 'error');
        router.push(workOrderReturnPath.value);
    } 
});

async function selectQuotation(event) {
    const file = event.target.files[0] || null;
    event.target.value = '';

    if (!file) return;

    quotation.value = file;
}

async function selectEvidences(event) {
    const selectedFiles = Array.from(event.target.files || []);
    event.target.value = '';

    if (!selectedFiles.length) return;

    const available = MAX_EVIDENCE_FILES - currentEvidences.value.length;

    if (selectedFiles.length > available) {
        dialogs.fire('Limite de archivos', `Solo puede agregar ${available} evidencia(s) mas.`, 'warning');
        return;
    }

    evidences.value.push(...selectedFiles.map((file) => ({
        name: file.name,
        file,
        preview: file.type.startsWith('image/') ? URL.createObjectURL(file) : null,
    })));
}

async function removeFile(file, type) {
    if (!file.id) {
        if (type === 'quotation') {
            quotation.value = null;
        } else {
            const stagedIndex = evidences.value.indexOf(file);
            const stagedFile = evidences.value[stagedIndex];

            if (stagedFile?.preview) URL.revokeObjectURL(stagedFile.preview);
            if (stagedIndex >= 0) evidences.value.splice(stagedIndex, 1);
        }

        return;
    }

    const result = await dialogs.fire({
        title: 'Eliminar archivo',
        text: 'El archivo se eliminara cuando presione Actualizar.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Si, eliminar',
        cancelButtonText: 'Cancelar',
    });

    if (!result.isConfirmed) return;

    deletedFileIds.value.push(file.id);
}

function buildFormData() {
    const formData = new FormData();

    Object.entries(item).forEach(([key, value]) => {
        if (value !== null && typeof value !== 'object') {
            formData.append(key, value);
        }
    });

    if (quotation.value) formData.append('quotation', quotation.value);
    evidences.value.forEach((file) => formData.append('evidences[]', file.file));

    return formData;
}

function buildUpdateFormData() {
    const formData = new FormData();

    if (item.payment_condition !== null) {
        formData.append('payment_condition', item.payment_condition);
    }

    if (item.payment_condition === 'Credito' && item.credit_days !== null) {
        formData.append('credit_days', item.credit_days);
    }

    if (quotation.value) formData.append('quotation', quotation.value);
    evidences.value.forEach((file) => formData.append('evidences[]', file.file));
    deletedFileIds.value.forEach((id) => formData.append('deleted_file_ids[]', id));

    return formData;
}

async function save() {
    if (isSaving.value) return;

    isSaving.value = true;

    try {
        errors.value = {};
        if (isEditing.value) {
            await updatePurchaseOrderApi(item.id, buildUpdateFormData());
        } else {
            await createPurchaseOrderApi(buildFormData());
        }

        dialogs.fire('Excelente', 'Orden guardada correctamente', 'success');
        router.push(workOrderReturnPath.value);
        
    } catch (error) {
        errors.value = error.response?.data?.errors || {};
        dialogs.fire('Error', error.response?.data?.message || 'Revise los campos', 'error');
    } finally {
        isSaving.value = false;
    }
}

</script>
