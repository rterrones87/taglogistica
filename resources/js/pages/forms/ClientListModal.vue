<template>
    <BaseModal
        :show="show"
        title="Administrar Clientes"
        size="4xl"
        @close="$emit('close')"
    >
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
    </BaseModal>
</template>

<script setup>
    import { ref, watch } from 'vue';
    import BaseModal from '../../components/BaseModal.vue';
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