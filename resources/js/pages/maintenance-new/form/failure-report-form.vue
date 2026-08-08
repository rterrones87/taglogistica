<template>
    <breadcrumb :items="breadcrumbItems" />

    <div class="m-4 rounded bg-white p-4 shadow-md">
        <div class="flex items-center gap-3">
            <h2 class="my-4 grow text-3xl font-bold">
                {{ isEditing ? item.folio : 'Nuevo reporte de falla' }}
            </h2>

            <span
                v-if="isEditing"
                class="rounded bg-gray-100 px-3 py-1 font-semibold"
            >
                {{ item.status }}
            </span>
        </div>

        <form @submit.prevent="save">
            <fieldset :disabled="item.status === 'Finalizado'" class="grid grid-cols-1 gap-3 disabled:opacity-70 md:grid-cols-2">
                <div class="form-item">
                    <label>Unidad economica *</label>

                    <select v-model="item.unit_id" required>
                        <option value="">Seleccione</option>
                        <option v-for="unit in catalogs.units" :key="unit.id" :value="unit.id">
                            {{ unit.econame }}
                        </option>
                    </select>
                </div>

                <div class="form-item">
                    <label>Operador *</label>

                    <select v-model="item.operator_id" required>
                        <option value="">Seleccione</option>
                        <option v-for="operator in catalogs.operators" :key="operator.id" :value="operator.id">
                            {{ operator.name }}
                        </option>
                    </select>
                </div>

                <div class="form-item">
                    <label>Kilometraje *</label>
                    <input v-model.number="item.mileage" type="number" min="1" required>
                </div>

                <div class="form-item">
                    <label>Fecha del reporte *</label>
                    <input v-model="item.reported_at" type="date" required>
                </div>

                <div class="form-item md:col-span-2">
                    <label>Descripcion de la falla *</label>
                    <textarea class="form-control" v-model="item.description" rows="5" required />
                </div>
            </fieldset>

            <div v-if="item.started_at || item.finished_at" class="mt-6 border-t pt-3 text-sm text-gray-600">
                <p v-if="item.started_at">
                    Iniciado por {{ item.started_by_user?.name || 'Usuario' }} el {{ formatDateTime(item.started_at) }}
                </p>

                <p v-if="item.finished_at">
                    Finalizado por {{ item.finished_by_user?.name || 'Usuario' }} el {{ formatDateTime(item.finished_at) }}
                </p>
            </div>

            <div class="mt-6 flex justify-end gap-2 border-t pt-4">
                <router-link
                    to="/panel/maintenance-new/failure-reports"
                    class="rounded border px-4 py-2"
                >
                    Cancelar
                </router-link>

                <router-link
                    v-if="isEditing && item.status === 'Abierto'"
                    :to="`/panel/maintenance-new/work-orders/new?failure_report_id=${item.id}`"
                    class="rounded border border-[#18364a] px-4 py-2 text-[#18364a]"
                >
                    Abrir orden
                </router-link>

                <button
                    v-if="isEditing && item.status === 'Abierto' && hasPermission('maintenance_new.start_failure_report')"
                    type="button"
                    class="rounded bg-amber-600 px-4 py-2 text-white"
                    @click="changeStatus('start')"
                >
                    Cambiar a En Proceso
                </button>

                <button
                    v-if="isEditing && item.status === 'En Proceso' && hasPermission('maintenance_new.finish_failure_report')"
                    type="button"
                    class="rounded bg-red-700 px-4 py-2 text-white"
                    @click="changeStatus('finish')"
                >
                    Finalizar reporte
                </button>

                <button
                    v-if="!isEditing || item.status === 'Abierto'"
                    type="submit"
                    class="rounded bg-[#18364a] px-4 py-2 text-white"
                >
                    {{ isEditing ? 'Actualizar' : 'Crear reporte' }}
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
    createFailureReportApi,
    finishFailureReportApi,
    getFailureReportDetailApi,
    startFailureReportApi,
    updateFailureReportApi,
} from '../../../apis/FailureReportApi';
import breadcrumb from '../../../components/breadcrumb.vue';
import { usePermissions } from '../../../composables/usePermissions';

const route = useRoute();
const router = useRouter();
const dialogs = inject('swal');
const { hasPermission } = usePermissions();
const isEditing = computed(() => route.params.id && route.params.id !== 'new');

const breadcrumbItems = computed(() => [
    { title: 'Reporte de fallas', path: '/panel/maintenance-new/failure-reports' },
    { title: isEditing.value ? 'Detalle del reporte' : 'Nuevo reporte' },
]);

const item = reactive({
    unit_id: '',
    operator_id: '',
    mileage: 1,
    reported_at: new Date().toISOString().slice(0, 10),
    description: '',
});

const catalogs = reactive({ units: [], operators: [] });
const errors = ref({});

onMounted(async () => {
    Object.assign(catalogs, await getWorkshopCatalogsApi());

    if (isEditing.value) {
        const response = await getFailureReportDetailApi(route.params.id);
        Object.assign(item, response.data);
    }
});

async function save() {
    try {
        errors.value = {};

        if (isEditing.value) {
            await updateFailureReportApi(item.id, item);
        } else {
            await createFailureReportApi(item);
        }

        dialogs.fire('Excelente', 'Reporte guardado correctamente', 'success');
        router.push('/panel/maintenance-new/failure-reports');
    } catch (error) {
        errors.value = error.response?.data?.errors || {};
        dialogs.fire('Error', error.response?.data?.message || 'Revise los campos', 'error');
    }
}

async function changeStatus(action) {
    const label = action === 'start' ? 'cambiar el reporte a En Proceso' : 'finalizar el reporte';
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
            ? await startFailureReportApi(item.id)
            : await finishFailureReportApi(item.id);

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
