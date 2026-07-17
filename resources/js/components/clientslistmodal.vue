<template>
    <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl mx-4 max-h-[90vh] overflow-hidden">
            <div class="flex justify-between items-center p-6 border-b">
                <h3 class="text-xl font-bold">Administrar Clientes</h3>
                <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="p-6">
                <div class="mb-4 flex justify-end">
                    <button 
                        @click="openClientModal" 
                        class="form-button"
                    >
                        Agregar nuevo cliente
                    </button>
                </div>
                
                <div class="overflow-auto max-h-96">
                    <table class="w-full border-collapse border border-gray-300">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border border-gray-300 px-4 py-2 text-left">Nombre</th>
                                <th class="border border-gray-300 px-4 py-2 text-left">RFC</th>
                                <th class="border border-gray-300 px-4 py-2 text-center"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="client in items" :key="client.id">
                                <td class="border border-gray-300 px-4 py-2">{{ client.name }}</td>
                                <td class="border border-gray-300 px-4 py-2">{{ client.RFC }}</td>
                                <td class="border border-gray-300 px-4 py-2 text-center">
                                    <!--<button 
                                        @click="() => {openEditClientModal(client.id)}" 
                                        class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm"
                                    >
                                        Editar / Destinos
                                    </button>-->
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div v-if="items.length === 0" class="text-center py-8 text-gray-500">
                        No hay clientes registrados
                    </div>
                </div>
            </div>
        </div>
    </div>
    <clientmodal 
        :show="showClientModal" 
        :idClient="idClient"
        @close="closeClientModal"
        @saved="clientSaved"
    />
</template>

<script setup>
    import { inject, ref, watch } from "vue";
    import { actionslist } from '../composables/actionslist';
    import clientmodal from "./clientmodal.vue";

    const showClientModal = ref(false);
    const idClient = ref(null);
    const dialogs = inject("swal");

    const props = defineProps({
        show: {
            type: Boolean,
            default: false
        }
    });

    const emit = defineEmits(['close']);

    const { items, deleteItem, loadItems } = actionslist({
        endpoint: 'clients',
        dialogs
    });

    // Cargar datos cuando se abre el modal
    watch(() => props.show, (newValue) => {
        if (newValue) {
            loadItems();
        }
    });

    const openClientModal = () => {
        idClient.value = null
        showClientModal.value = true;
    };

    const openEditClientModal = (id) => {
        idClient.value = id
        showClientModal.value = true;
    };

    const closeClientModal = () => {
        showClientModal.value = false;
    };

    const clientSaved = () => {
        loadItems();
    }

</script>