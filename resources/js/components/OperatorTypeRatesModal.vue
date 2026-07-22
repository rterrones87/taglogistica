<template>
    <BaseModal
        :show="show"
        title="Tarifas por Tipo de Operador"
        size="4xl"
        @close="$emit('close')"
    >
        <div v-if="loading" class="text-center py-8 text-gray-500">Cargando...</div>

        <div v-else class="space-y-6">
            <!-- Agrupar por tipo de operación -->
            <div v-for="(types, opLabel) in groupedTypes" :key="opLabel">
                <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wide mb-3 border-b pb-1">
                    {{ opLabel }}
                </h3>

                <div
                    v-for="opType in types"
                    :key="opType.id"
                    class="border rounded mb-3 overflow-hidden"
                >
                    <!-- Cabecera del tipo -->
                    <div class="flex items-center justify-between px-4 py-2 bg-gray-50">
                        <span class="font-semibold text-sm">{{ opType.name }}</span>
                        <button
                            type="button"
                            class="text-sm text-[#18364a] font-semibold hover:underline"
                            @click="toggleAddForm(opType.id)"
                        >
                            + Agregar tarifa
                        </button>
                    </div>

                    <!-- Tabla de tarifas existentes -->
                    <table v-if="opType.rates && opType.rates.length > 0" class="w-full text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="text-left px-4 py-2 font-medium">Nombre</th>
                                <th class="text-right px-4 py-2 font-medium">Monto</th>
                                <th class="w-24 px-4 py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="rate in opType.rates"
                                :key="rate.id"
                                class="border-t"
                            >
                                <td class="px-4 py-2">{{ rate.name }}</td>
                                <td class="px-4 py-2 text-right">${{ Number(rate.amount).toFixed(2) }}</td>
                                <td class="px-4 py-2 text-center">
                                    <button
                                        type="button"
                                        class="text-red-500 hover:text-red-700 text-xs font-semibold"
                                        @click="deleteRate(opType.id, rate.id)"
                                    >
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else class="text-xs text-gray-400 italic px-4 py-2">Sin tarifas registradas</p>

                    <!-- Formulario inline para agregar -->
                    <div
                        v-if="addFormVisible[opType.id]"
                        class="flex items-end gap-2 px-4 py-3 bg-blue-50 border-t"
                    >
                        <div class="flex-1">
                            <label class="text-xs text-gray-600 block mb-1">Nombre</label>
                            <input
                                v-model="addForms[opType.id].name"
                                type="text"
                                class="border rounded px-2 py-1 w-full text-sm"
                                placeholder="Ej. Base, Especial..."
                            />
                        </div>
                        <div class="w-36">
                            <label class="text-xs text-gray-600 block mb-1">Monto ($)</label>
                            <CurrencyInput
                                v-model="addForms[opType.id].amount"
                                :min="0.01"
                                placeholder="0.00"
                            />
                        </div>
                        <div class="flex gap-2 pb-0.5">
                            <button
                                type="button"
                                class="form-button text-sm py-1 px-3"
                                @click="saveRate(opType.id)"
                            >
                                Guardar
                            </button>
                            <button
                                type="button"
                                class="form-button bg-[#6e7881] text-sm py-1 px-3"
                                @click="cancelAddForm(opType.id)"
                            >
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BaseModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import BaseModal from './BaseModal.vue';
import CurrencyInput from './CurrencyInput.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);

const loading    = ref(false);
const allTypes   = ref([]);
const addFormVisible = ref({});
const addForms       = ref({});

const TYPE_OPERATION_LABELS = {
    1: 'Importación',
    2: 'Exportación',
    3: 'Carga Suelta',
};

// Solo tipos no-principales (is_main = 0)
const nonMainTypes = computed(() =>
    allTypes.value.filter(t => t.is_main == 0)
);

// Agrupados por tipo de operación
const groupedTypes = computed(() => {
    const groups = {};
    for (const t of nonMainTypes.value) {
        const label = TYPE_OPERATION_LABELS[t.type_operation] ?? `Operación ${t.type_operation}`;
        if (!groups[label]) groups[label] = [];
        groups[label].push(t);
    }
    return groups;
});

// Cargar tipos cuando se abre el modal
watch(() => props.show, async (val) => {
    if (val) await loadTypes();
});

async function loadTypes() {
    loading.value = true;
    try {
        const { data } = await axios.get('service-operator-types');
        allTypes.value = data;
        // Inicializar formularios
        data.forEach(t => {
            addFormVisible.value[t.id] = false;
            addForms.value[t.id]       = { name: '', amount: '' };
        });
    } finally {
        loading.value = false;
    }
}

function toggleAddForm(typeId) {
    addFormVisible.value[typeId] = !addFormVisible.value[typeId];
    if (addFormVisible.value[typeId]) {
        addForms.value[typeId] = { name: '', amount: '' };
    }
}

function cancelAddForm(typeId) {
    addFormVisible.value[typeId] = false;
    addForms.value[typeId] = { name: '', amount: '' };
}

async function saveRate(typeId) {
    const form = addForms.value[typeId];
    if (!form.name || !form.amount) {
        alert('El nombre y el monto son obligatorios');
        return;
    }
    try {
        const { data } = await axios.post('service-operator-type-rates', {
            service_operator_type_id: typeId,
            name:   form.name,
            amount: form.amount,
        });
        // Agregar la nueva tarifa a la lista local
        const opType = allTypes.value.find(t => t.id === typeId);
        if (opType) {
            if (!opType.rates) opType.rates = [];
            opType.rates.push(data);
            opType.rates.sort((a, b) => a.name.localeCompare(b.name));
        }
        cancelAddForm(typeId);
    } catch (err) {
        const msg = err.response?.data?.message || 'Error al guardar la tarifa';
        alert(msg);
    }
}

async function deleteRate(typeId, rateId) {
    if (!confirm('¿Eliminar esta tarifa?')) return;
    try {
        await axios.delete(`service-operator-type-rates/${rateId}`);
        const opType = allTypes.value.find(t => t.id === typeId);
        if (opType) {
            opType.rates = opType.rates.filter(r => r.id !== rateId);
        }
    } catch {
        alert('Error al eliminar la tarifa');
    }
}
</script>
