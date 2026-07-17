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
                        @click="$emit('new-client')" 
                        class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600"
                    >
                        Agregar Nuevo Cliente
                    </button>
                </div>
                
                <div class="overflow-auto max-h-96">
                    <table class="w-full border-collapse border border-gray-300">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border border-gray-300 px-4 py-2 text-left">Nombre</th>
                                <th class="border border-gray-300 px-4 py-2 text-left">RFC</th>
                                <th class="border border-gray-300 px-4 py-2 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="client in clients" :key="client.id">
                                <td class="border border-gray-300 px-4 py-2">{{ client.name }}</td>
                                <td class="border border-gray-300 px-4 py-2">{{ client.RFC }}</td>
                                <td class="border border-gray-300 px-4 py-2 text-center">
                                    <button 
                                        @click="$emit('edit-client', client)" 
                                        class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm"
                                    >
                                        Editar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div v-if="clients.length === 0" class="text-center py-8 text-gray-500">
                        No hay clientes registrados
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
    import { ref, watch } from 'vue';
    import axios from 'axios';

    const props = defineProps({
        show: {
            type: Boolean,
            default: false
        }
    });

    const emit = defineEmits(['close', 'new-client', 'edit-client']);

    const clients = ref([]);

    const loadClients = async () => {
        try {
            const response = await axios.get('/api/clients');
            clients.value = response.data.data || response.data;
        } catch (error) {
            console.error('Error al cargar clientes:', error);
        }
    };

    // Cargar clientes cuando se abre el modal
    watch(() => props.show, (newVal) => {
        if (newVal) {
            loadClients();
        }
    });

    // Exponer función para recargar desde el componente padre
    defineExpose({
        loadClients
    });
</script>