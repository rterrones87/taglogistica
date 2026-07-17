<template>
    <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl mx-4 max-h-[90vh] overflow-hidden">
            <div class="flex justify-between items-center p-6 border-b">
                <h3 class="text-xl font-bold">Unidades sin TAG</h3>
                <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="p-6">
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
            </div>
        </div>
    </div>
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
