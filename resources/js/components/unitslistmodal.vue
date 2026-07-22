<template>
    <BaseModal
        :show="show"
        title="Unidades sin TAG"
        size="4xl"
        @close="$emit('close')"
    >
                <div class="overflow-auto max-h-96">
                    <table class="w-full border-collapse border border-gray-300">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border border-gray-300 px-4 py-2 text-left">ID</th>
                                <th class="border border-gray-300 px-4 py-2 text-left">Nombre</th>
                                <th class="border border-gray-300 px-4 py-2 text-left">Marca</th>
                                <th class="border border-gray-300 px-4 py-2 text-left">Modelo</th>
                                <th class="border border-gray-300 px-4 py-2 text-center"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="unit in filteredItems" :key="unit.id">
                                <td class="border border-gray-300 px-4 py-2">{{ unit.id }}</td>
                                <td class="border border-gray-300 px-4 py-2">{{ unit.econame }}</td>
                                <td class="border border-gray-300 px-4 py-2">{{ unit.brand }}</td>
                                <td class="border border-gray-300 px-4 py-2">{{ unit.model }}</td>
                                <td class="border border-gray-300 px-4 py-2 text-center">
                                    <button 
                                        @click="() => {openEditUnitModal(unit.id)}" 
                                        class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm"
                                    >
                                        {{ unit.TAG ? 'Ver TAG' : 'Asignar TAG' }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div v-if="filteredItems.length === 0" class="text-center py-8 text-gray-500">
                        No hay unidades sin TAG
                    </div>
                </div>
    </BaseModal>
    <unitmodal
        :show="showUnitModal" 
        :idUnit="idUnit"
        @close="closeUnitModal"
        @saved="unitSaved"
    />
</template>

<script setup>
    import { inject, ref, computed, watch } from "vue";
    import { actionslist } from '../composables/actionslist';
    import BaseModal from './BaseModal.vue';
    import unitmodal from "./unitmodal.vue";

    const showUnitModal = ref(false);
    const idUnit = ref(null);
    const dialogs = inject("swal");

    const props = defineProps({
        show: {
            type: Boolean,
            default: false
        }
    });

    const emit = defineEmits(['close']);

    const { items, deleteItem, loadItems } = actionslist({
        endpoint: 'units',
        dialogs
    });

    // Filtrar solo unidades tipo Tractocamión (type=1) sin TAG asignado
    const filteredItems = computed(() => {
        return items.value.filter(unit => unit.type === 1);
    });

    // Cargar datos cuando se abre el modal
    watch(() => props.show, (newValue) => {
        if (newValue) {
            loadItems();
        }
    });

    const openEditUnitModal = (id) => {
        idUnit.value = id
        showUnitModal.value = true;
    };

    const closeUnitModal = () => {
        showUnitModal.value = false;
    };

    const unitSaved = () => {
        loadItems();
    }

</script>
