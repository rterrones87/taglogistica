<template>
    <breadcrumb :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 rounded shadow-md">

        <div class="flex items-center">
            <h2 class="text-3xl my-4 font-bold grow text-center md:text-left">Control de rutas</h2>

            <div v-if="hasPermission('client_places.create')" class="my-2 flex justify-end hidden md:flex gap-2">
                <GenericAction
                    title="Nuevo registro"
                    icon="add.png"
                    @click="openCreateModal"
                />
                <GenericAction
                    v-if="hasPermission('clients.consult')"
                    title="Clientes"
                    icon="edit.png"
                    @click="openClientsListModal"
                />
                <GenericAction
                    v-if="hasPermission('places.consult')"
                    title="Destinos"
                    icon="edit.png"
                    @click="openPlacesListModal"
                />
                <GenericAction
                    v-if="hasPermission('booths.consult')"
                    title="Casetas"
                    icon="edit.png"
                    @click="openBoothsListModal"
                />
            </div>
        </div>

        <button
            v-if="hasPermission('client_places.create')"
            class="float-button block md:hidden"
            @click="openCreateModal"
        ></button>

        <!-- DataTable Component -->
        <DataTable
            :data="items"
            :columns="columns"
            :onReload="loadItems"
            emptyMessage="No hay rutas disponibles."
        >
            <template #actions="{ row }">
                <div class="flex justify-center flex-col md:flex-row">
                    <TableAction
                        v-if="hasPermission('client_places.edit')"
                        title="Casetas"
                        icon="booth.png"
                        @click.prevent="openBoothsModal(row)"
                    />
                    <TableAction
                        v-if="hasPermission('client_places.edit')"
                        title="Editar"
                        icon="edit.png"
                        @click.prevent="openEditModal(row)"
                    />
                    <TableAction
                        v-if="hasPermission('client_places.delete')"
                        title="Eliminar"
                        icon="delete.png"
                        @click.prevent="deleteItem(row.id)"
                    />
                </div>
            </template>
        </DataTable>
    </div>

    <!-- Modal de Creación -->
    <BaseModal
        :show="showCreateModal"
        title="Nueva Tarifa"
        size="md"
        @close="closeCreateModal"
    >
        <div class="space-y-4">
            <div class="form-item">
                <label>Cliente <span class="text-red-500">*</span></label>
                <suggestioninput
                    v-model="createForm.client_id"
                    url="clients"
                    valueKey="id"
                    textKey="name"
                    :textValue="''"
                />
                <span v-if="createErrors.client_id" class="text-red-500 text-sm">{{ createErrors.client_id[0] }}</span>
            </div>

            <div class="form-item">
                <label>Destino <span class="text-red-500">*</span></label>
                <suggestioninput
                    v-model="createForm.place_id"
                    url="places"
                    valueKey="id"
                    textKey="name"
                    :textValue="''"
                />
                <span v-if="createErrors.place_id" class="text-red-500 text-sm">{{ createErrors.place_id[0] }}</span>
            </div>

            <div class="form-item">
                <label>Tipos de Unidad <span class="text-red-500">*</span></label>
                <div class="border rounded overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium">Tipo de Unidad <span class="text-red-500">*</span></th>
                                <th class="px-3 py-2 text-left font-medium">Pago Operador <span class="text-red-500">*</span></th>
                                <th class="px-3 py-2 w-8"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, index) in createForm.items" :key="index" class="border-t">
                                <td class="px-3 py-2">
                                    <UnitTypeSelect v-model="row.type_unit_id" :required="true" />
                                </td>
                                <td class="px-3 py-2">
                                    <CurrencyInput v-model="row.amount" />
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <button
                                        v-if="createForm.items.length > 1"
                                        @click="removeItemRow(index)"
                                        type="button"
                                        class="text-red-500 hover:text-red-700 font-bold text-lg leading-none"
                                        title="Quitar fila"
                                    >&times;</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <button
                        @click="addItemRow"
                        type="button"
                        class="w-full py-2 text-sm text-blue-600 hover:bg-gray-50 border-t"
                    >
                        + Agregar tipo de unidad
                    </button>
                </div>
                <span v-if="createErrors['items']" class="text-red-500 text-sm">{{ createErrors['items'][0] }}</span>
            </div>

            <div v-if="createErrors.error" class="text-red-500 text-sm font-medium">
                {{ createErrors.error }}
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <button @click="closeCreateModal" class="form-button bg-[#6e7881]">Cancelar</button>
                <button @click="saveCreate" class="form-button">Guardar</button>
            </div>
        </template>
    </BaseModal>

    <!-- Modal de Plantilla de Casetas -->
    <BaseModal
        :show="showBoothsModal"
        :title="'Plantilla de Casetas — ' + (boothsTarget?.client?.name || '') + ' → ' + (boothsTarget?.place?.name || '')"
        size="4xl"
        @close="closeBoothsModal"
    >
        <div class="space-y-4">
            <!-- Checkbox Auto replicación -->
            <div class="flex items-center gap-2">
                <input
                    type="checkbox"
                    id="autoReplicate"
                    v-model="autoReplicate"
                    class="w-4 h-4 cursor-pointer"
                />
                <label for="autoReplicate" class="text-sm cursor-pointer select-none">
                    Auto replicación
                    <span class="text-gray-400 text-xs">(aplica cambios a todas las combinaciones de este destino)</span>
                </label>
            </div>

            <!-- Formulario para agregar -->
            <div class="flex flex-col md:flex-row items-end gap-2 border-b pb-4">
                <div class="form-item grow w-full md:w-auto">
                    <label>Caseta <span class="text-red-500">*</span></label>
                    <suggestioninput
                        :key="boothInputKey"
                        v-model="boothForm.booth_id"
                        url="booths"
                        valueKey="id"
                        textKey="name"
                        :textValue="''"
                        :disabled="addingBooth"
                    />
                </div>
                <div class="form-item w-full md:w-48">
                    <label>Dirección <span class="text-red-500">*</span></label>
                    <select v-model="boothForm.direction" :disabled="addingBooth">
                        <option value="outbound">Ida</option>
                        <option value="return">Regreso</option>
                    </select>
                </div>
                <button @click="addBoothToTemplate" :disabled="addingBooth" class="form-button whitespace-nowrap h-[38px]">
                    {{ addingBooth ? 'Agregando...' : 'Agregar' }}
                </button>
            </div>

            <div v-if="boothFormError" class="text-red-500 text-sm font-medium">
                {{ boothFormError }}
            </div>

            <!-- Tablas lado a lado -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Casetas de Ida -->
                <div>
                    <h4 class="font-bold text-lg mb-2">Casetas de Ida</h4>
                    <table v-if="boothsOutbound.length" class="w-full border-collapse border border-gray-300">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border border-gray-300 px-4 py-2 text-left">Nombre</th>
                                <th class="border border-gray-300 px-4 py-2 text-center w-16"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in boothsOutbound" :key="item.id">
                                <td class="border border-gray-300 px-4 py-2">
                                    <span
                                        v-if="boothsHasSiblings"
                                        class="inline-block w-2 h-2 rounded-full mr-2 align-middle flex-shrink-0"
                                        :class="item.is_unique ? 'bg-orange-400' : 'bg-green-500'"
                                        :title="item.is_unique ? 'Solo en este tipo de unidad' : 'Compartida con otras combinaciones'"
                                    ></span>
                                    {{ item.booth?.name }}
                                </td>
                                <td class="border border-gray-300 px-4 py-2 text-center">
                                    <TableAction
                                        title="Eliminar"
                                        icon="delete.png"
                                        @click.prevent="removeBoothFromTemplate(item.id)"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else class="text-gray-500 text-sm">No hay casetas de ida configuradas.</p>
                </div>

                <!-- Casetas de Regreso -->
                <div>
                    <h4 class="font-bold text-lg mb-2">Casetas de Regreso</h4>
                    <table v-if="boothsReturn.length" class="w-full border-collapse border border-gray-300">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border border-gray-300 px-4 py-2 text-left">Nombre</th>
                                <th class="border border-gray-300 px-4 py-2 text-center w-16"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in boothsReturn" :key="item.id">
                                <td class="border border-gray-300 px-4 py-2">
                                    <span
                                        v-if="boothsHasSiblings"
                                        class="inline-block w-2 h-2 rounded-full mr-2 align-middle flex-shrink-0"
                                        :class="item.is_unique ? 'bg-orange-400' : 'bg-green-500'"
                                        :title="item.is_unique ? 'Solo en este tipo de unidad' : 'Compartida con otras combinaciones'"
                                    ></span>
                                    {{ item.booth?.name }}
                                </td>
                                <td class="border border-gray-300 px-4 py-2 text-center">
                                    <TableAction
                                        title="Eliminar"
                                        icon="delete.png"
                                        @click.prevent="removeBoothFromTemplate(item.id)"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else class="text-gray-500 text-sm">No hay casetas de regreso configuradas.</p>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <button @click="closeBoothsModal" class="form-button bg-[#6e7881]">Cerrar</button>
            </div>
        </template>
    </BaseModal>

    <!-- Modal de Edición -->
    <BaseModal
        :show="showEditModal"
        title="Editar Tarifa"
        size="sm"
        @close="closeEditModal"
    >
        <div class="space-y-4">
        <div class="form-item">
            <label>Cliente</label>
            <input type="text" :value="editingItem?.client?.name" disabled class="bg-gray-100" />
        </div>

        <div class="form-item">
            <label>Tipo de Unidad</label>
            <input type="text" :value="editingItem?.type_unit?.name ?? '---'" disabled class="bg-gray-100" />
        </div>

            <div class="form-item">
                <label>Destino</label>
                <input type="text" :value="editingItem?.place?.name" disabled class="bg-gray-100" />
            </div>

            <div class="form-item">
                <label>Pago de operadores <span class="text-red-500">*</span></label>
                <CurrencyInput v-model="editForm.amount" />
                <span v-if="editErrors.amount" class="text-red-500 text-sm">{{ editErrors.amount[0] }}</span>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <button @click="closeEditModal" class="form-button bg-[#6e7881]">Cancelar</button>
                <button @click="saveEdit" class="form-button">Guardar</button>
            </div>
        </template>
    </BaseModal>
    <clientslistmodal
        :show="showClientsListModal"
        @close="showClientsListModal = false"
    />
    <placeslistmodal
        :show="showPlacesListModal"
        @close="showPlacesListModal = false"
    />
    <boothslistmodal
        :show="showBoothsListModal"
        @close="showBoothsListModal = false"
    />
</template>

<script setup>
import { ref, inject, onMounted } from 'vue';
import axios from 'axios';
import { actionslist } from '../composables/actionslist';
import { usePermissions } from '../composables/usePermissions';
import breadcrumb from '../components/breadcrumb.vue';
import DataTable from '@/components/DataTable.vue';
import TableAction from '@/components/TableAction.vue';
import GenericAction from '@/components/GenericAction.vue';
import BaseModal from '@/components/BaseModal.vue';
import CurrencyInput from '@/components/CurrencyInput.vue';
import suggestioninput from '../components/suggestioninput.vue';
import UnitTypeSelect from '../components/UnitTypeSelect.vue';
import clientslistmodal from '../components/clientslistmodal.vue';
import placeslistmodal from '../components/placeslistmodal.vue';
import boothslistmodal from '../components/boothslistmodal.vue';

const dialogs = inject('swal');
const { hasPermission } = usePermissions();

// Lista principal
const { items, deleteItem, loadItems } = actionslist({
    endpoint: 'client-places',
    dialogs,
});

onMounted(() => {
    loadItems();
});

// ─── Modales de catálogos ─────────────────────────────────────────────────────
const showClientsListModal = ref(false);
const showPlacesListModal = ref(false);

const showBoothsListModal = ref(false);

const openClientsListModal = () => { showClientsListModal.value = true; };
const openPlacesListModal = () => { showPlacesListModal.value = true; };
const openBoothsListModal = () => { showBoothsListModal.value = true; };

// ─── Modal de Creación ────────────────────────────────────────────────────────
const showCreateModal = ref(false);
const createForm = ref({ client_id: '', place_id: '', items: [{ type_unit_id: '', amount: '0.00' }] });
const createErrors = ref({});

const openCreateModal = () => {
    createForm.value = { client_id: '', place_id: '', items: [{ type_unit_id: '', amount: '0.00' }] };
    createErrors.value = {};
    showCreateModal.value = true;
};

const addItemRow = () => {
    createForm.value.items.push({ type_unit_id: '', amount: '0.00' });
};

const removeItemRow = (index) => {
    createForm.value.items.splice(index, 1);
};

const closeCreateModal = () => {
    showCreateModal.value = false;
};

const saveCreate = async () => {
    createErrors.value = {};

    if (!createForm.value.client_id || !createForm.value.place_id) {
        if (!createForm.value.client_id) createErrors.value.client_id = ['El cliente es requerido.'];
        if (!createForm.value.place_id) createErrors.value.place_id = ['El destino es requerido.'];
        return;
    }

    const emptyRow = createForm.value.items.some(r => !r.type_unit_id);
    if (emptyRow) {
        createErrors.value.items = ['Todos los tipos de unidad son requeridos.'];
        return;
    }

    try {
        dialogs.fire({
            title: 'Procesando...',
            text: 'Por favor, espere',
            allowOutsideClick: false,
            didOpen: () => dialogs.showLoading(),
        });

        const response = await axios.post('client-places', {
            client_id: createForm.value.client_id,
            place_id:  createForm.value.place_id,
            items:     createForm.value.items,
        });

        // El servidor devuelve un array; insertar todos al inicio de la lista
        const newRecords = response.data;
        newRecords.slice().reverse().forEach(r => items.value.unshift(r));
        dialogs.close();
        dialogs.fire('¡Excelente!', 'Los registros han sido creados correctamente.', 'success');
        closeCreateModal();
    } catch (error) {
        dialogs.close();
        if (error.response?.status === 400) {
            createErrors.value = error.response.data;
        } else {
            console.error('Error al crear:', error);
        }
    }
};

// ─── Modal de Plantilla de Casetas ────────────────────────────────────────────
const showBoothsModal = ref(false);
const boothsTarget = ref(null);
const boothsOutbound = ref([]);
const boothsReturn = ref([]);
const boothsHasSiblings = ref(false);
const autoReplicate = ref(true);
const boothForm = ref({ booth_id: '', direction: 'outbound' });
const boothFormError = ref('');
const addingBooth = ref(false);
const boothInputKey = ref(0);

const openBoothsModal = async (row) => {
    boothsTarget.value = row;
    boothForm.value = { booth_id: '', direction: 'outbound' };
    boothFormError.value = '';
    boothInputKey.value++;
    autoReplicate.value = true;
    showBoothsModal.value = true;
    await loadBoothsTemplate(row.id);
};

const closeBoothsModal = () => {
    showBoothsModal.value = false;
    boothsTarget.value = null;
};

const loadBoothsTemplate = async (clientPlaceId) => {
    try {
        const res = await axios.get(`client-places/${clientPlaceId}/booths`);
        boothsHasSiblings.value = res.data.has_siblings ?? false;
        boothsOutbound.value = res.data.outbound || [];
        boothsReturn.value   = res.data.return   || [];
    } catch (error) {
        console.error('Error cargando plantilla de casetas:', error);
    }
};

const addBoothToTemplate = async () => {
    boothFormError.value = '';

    if (!boothForm.value.booth_id) {
        boothFormError.value = 'Selecciona una caseta.';
        return;
    }

    addingBooth.value = true;
    try {
        await axios.post(`client-places/${boothsTarget.value.id}/booths`, {
            booth_id:  boothForm.value.booth_id,
            direction: boothForm.value.direction,
            replicate: autoReplicate.value,
        });
        boothForm.value.booth_id = '';
        boothInputKey.value++;
        await loadBoothsTemplate(boothsTarget.value.id);
    } catch (error) {
        if (error.response?.status === 400 && error.response.data?.error) {
            boothFormError.value = error.response.data.error;
        } else {
            console.error('Error al agregar caseta:', error);
        }
    } finally {
        addingBooth.value = false;
    }
};

const removeBoothFromTemplate = async (recordId) => {
    const result = await dialogs.fire({
        title: 'Eliminar caseta',
        text: '¿Estás seguro(a) que deseas eliminar esta caseta de la plantilla?',
        showCancelButton: true,
        confirmButtonText: 'Aceptar',
        cancelButtonText: 'Cancelar',
    });

    if (result.isConfirmed) {
        try {
            dialogs.fire({
                title: 'Procesando...',
                text: 'Por favor, espere',
                allowOutsideClick: false,
                didOpen: () => dialogs.showLoading(),
            });
            await axios.delete(
                `client-places/${boothsTarget.value.id}/booths/${recordId}`,
                { params: { replicate: autoReplicate.value ? 1 : 0 } }
            );
            await loadBoothsTemplate(boothsTarget.value.id);
            dialogs.close();
        } catch (error) {
            dialogs.close();
            console.error('Error al eliminar caseta:', error);
        }
    }
};

// ─── Modal de Edición ─────────────────────────────────────────────────────────
const showEditModal = ref(false);
const editingItem = ref(null);
const editForm = ref({ amount: '0.00' });
const editErrors = ref({});

const openEditModal = (row) => {
    editingItem.value = row;
    editForm.value = { amount: row.amount ?? '0.00' };
    editErrors.value = {};
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editingItem.value = null;
};

const saveEdit = async () => {
    editErrors.value = {};

    try {
        dialogs.fire({
            title: 'Procesando...',
            text: 'Por favor, espere',
            allowOutsideClick: false,
            didOpen: () => dialogs.showLoading(),
        });

        await axios.put(`client-places/${editingItem.value.id}`, {
            amount: editForm.value.amount,
        });

        // Actualizar el ítem en la lista local
        const index = items.value.findIndex(i => i.id === editingItem.value.id);
        if (index !== -1) {
            items.value[index] = { ...items.value[index], amount: editForm.value.amount };
        }

        dialogs.close();
        dialogs.fire('¡Excelente!', 'El registro ha sido actualizado correctamente.', 'success');
        closeEditModal();
    } catch (error) {
        dialogs.close();
        if (error.response?.status === 400) {
            editErrors.value = error.response.data;
        } else {
            console.error('Error al actualizar:', error);
        }
    }
};

// ─── Configuración ────────────────────────────────────────────────────────────
const breadcrumbItems = [
    { title: 'Control de rutas' },
];

const formatCurrency = (value) => {
    if (value == null || value === '') return '$0.00';
    return `$${Number(value).toFixed(2)}`;
};

const columns = [
    { key: 'id',                label: 'ID',               sortable: true, filterable: true },
    { key: 'client.name',       label: 'Cliente',           sortable: true, filterable: true },
    { key: 'place.name',        label: 'Destino',           sortable: true, filterable: true },
    { key: 'type_unit.name',    label: 'Tipo de Unidad',    sortable: true, filterable: true, formatter: (value) => value || '---' },
    { key: 'amount',            label: 'Pago de operadores', sortable: true, filterable: true, formatter: (value) => formatCurrency(value) },
];
</script>
