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

            <fieldset class="space-y-6">
                
                <!-- Clasificacion -->
                <section>
                    
                    <h3 class="mb-3 text-xl font-bold">Clasificacion</h3>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">

                        <div class="form-item">
                            <label>Tipo de servicio *</label>

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

                <!-- Datos de operacion -->
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

                            <select v-model="item.work_type" required :disabled="isEditing">
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

                <!-- Detalle del trabajo -->
                <section>
                    <h3 class="mb-3 text-xl font-bold">Detalle del trabajo</h3>

                    <div class="form-item">
                        <label>Descripcion de la falla *</label>
                        <textarea class="form-control" v-model="item.failure_description" rows="4" required />
                        <ErrorText :errors="errors.failure_description" />
                    </div>
                </section>

                <!-- Ordenes de compra -->
                <section v-if="isEditing && item.work_type !== 'Interno' && item.status === 'En Proceso'" class="border-t pt-4">
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

                <!-- Evidencias: Solo cuando es de tipo interno -->
                <section v-if="isEditing && item.work_type === 'Interno' && item.status === 'En Proceso'" class="border-t pt-4">

                    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    
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
                                v-if="currentEvidences.length < 5"
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

            <!-- Seguimiento: Iniciada / Cerrada -->
            <div v-if="item.started_at || item.closed_at" class="border-t pt-3 text-sm text-gray-600">
                <p v-if="item.started_at">
                    Iniciada por {{ item.started_by_user || 'Usuario' }} el {{ formatDateTime(item.started_at) }}
                </p>
                <p v-if="item.closed_at">
                    Cerrada por {{ item.closed_by_user || 'Usuario' }} el {{ formatDateTime(item.closed_at) }}
                </p>
            </div>

            <!-- Acciones -->
            <div class="flex flex-wrap justify-end gap-2 border-t pt-4">
                <router-link to="/panel/maintenance-new/work-orders" class="rounded border px-4 py-2">
                    Cancelar
                </router-link>

                <div v-if="item.work_type !== 'Interno'" >

                    <router-link
                        v-if="isEditing && item.status === 'En Proceso' && hasPermission('maintenances.create')"
                        :to="`/panel/maintenance-new/purchase-orders/new?work_order_id=${item.id}`"
                        class="rounded border border-[#18364a] px-4 py-2 text-[#18364a]"
                    >
                        Agregar OC
                    </router-link>

                </div>

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
import { computed, inject, onMounted, reactive, ref, watch } from 'vue';
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
    createWorkOrderForm,
    createWorkshopCatalogs,
    purchaseOrderColumns,
    vehicleCategories,
    workOrderCategories,
    MAX_EVIDENCE_FILES
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

const item = reactive(createWorkOrderForm());
const catalogs = reactive(createWorkshopCatalogs());
const errors = ref({});
const isLoadingPurchaseOrders = ref(isEditing.value);
const isSaving = ref(false);
const vehicleCategory = computed(() => vehicleCategories.includes(item.unit_category));
const canFinishWorkOrder = computed(() => isEditing.value
    && item.status === 'En Proceso'
    && !(item.purchase_orders || []).some((purchaseOrder) => purchaseOrder.status === 'Pendiente')
    && hasPermission('maintenances.change_state'));

const evidences = ref([]);
const deletedFileIds = ref([]);

const currentEvidences = computed(() => [
    ...(item.evidence_files || []).filter((file) => !deletedFileIds.value.includes(file.id)),
    ...evidences.value,
]);

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

watch(
    () => route.params.id,
    async (newId, oldId) => {
        if (newId === oldId) return;

        if (newId && newId !== 'new') {
            isLoadingPurchaseOrders.value = true;

            try {
                const response = await getWorkOrderDetailApi(newId);
                Object.assign(item, response.data);
            } catch (error) {
                dialogs.fire('Error', error.response?.data?.message || 'No fue posible cargar la orden', 'error');
                router.push('/panel/maintenance-new/work-orders');
            } finally {
                isLoadingPurchaseOrders.value = false;
            }
        }
    }
);

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

async function removeFile(file) {
    if (!file.id) {
        
        const stagedIndex = evidences.value.indexOf(file);
        const stagedFile = evidences.value[stagedIndex];

        if (stagedFile?.preview) URL.revokeObjectURL(stagedFile.preview);
        if (stagedIndex >= 0) evidences.value.splice(stagedIndex, 1);
        
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

function buildUpdateFormData() {
    const formData = new FormData();

    // 1. Añadir TODOS los campos de texto obligatorios y opcionales
    formData.append('unit_category', item.unit_category ?? '');
    formData.append('unit_id', item.unit_id ?? '');
    formData.append('initial_mileage', item.initial_mileage ?? '');
    formData.append('opened_at', item.opened_at ?? '');
    formData.append('operator_id', item.operator_id ?? '');
    formData.append('failure_description', item.failure_description ?? '');
    formData.append('work_type', item.work_type ?? '');
    
    if (item.mechanic_id) formData.append('mechanic_id', item.mechanic_id);
    if (item.maintenance_type) formData.append('maintenance_type', item.maintenance_type);

    // 2. Añadir evidencias y archivos eliminados
    evidences.value.forEach((file) => {
        if (file.file) formData.append('evidences[]', file.file);
    });
    deletedFileIds.value.forEach((id) => formData.append('deleted_file_ids[]', id));

    return formData;
}

async function save() {
    if (isSaving.value) return;

    isSaving.value = true;

    try {
        errors.value = {};
        if (item.work_type === 'Externo') item.mechanic_id = null;
        if (!vehicleCategory.value) item.maintenance_type = null;

        let response;
        if (isEditing.value) {
            response = await updateWorkOrderApi(item.id, buildUpdateFormData());
        } else {
            response = await createWorkOrderApi(item);
        }

        const savedOrder = response.data || response;

        // 1. Actualizar el item local con la respuesta fresca del backend (trae los archivos ya guardados)
        Object.assign(item, savedOrder);

        // 2. Limpiar los estados temporales de evidencias y archivos eliminados
        evidences.value.forEach(file => {
            if (file.preview) URL.revokeObjectURL(file.preview);
        });
        evidences.value = [];
        deletedFileIds.value = [];

        dialogs.fire('Excelente', 'Orden guardada correctamente', 'success');

        // 3. Redirigir asegurando que la ruta recargue si es necesario
        const newId = savedOrder.id;
        if (route.params.id !== String(newId)) {
            router.push(`/panel/maintenance-new/work-orders/${newId}`);
        }

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
