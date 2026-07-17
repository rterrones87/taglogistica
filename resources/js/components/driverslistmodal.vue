<template>
    <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl mx-4 max-h-[90vh] overflow-hidden">
            <div class="flex justify-between items-center p-6 border-b">
                <h3 class="text-xl font-bold">Administrar Operadores</h3>
                <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="p-6">
                <div class="mb-4 flex justify-end">
                    <button 
                        @click="openDriverModal" 
                        class="form-button"
                    >
                        Agregar nuevo operador
                    </button>
                </div>
                
                <div class="overflow-auto max-h-96">
                    <table class="w-full border-collapse border border-gray-300">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border border-gray-300 px-4 py-2 text-left">ID</th>
                                <th class="border border-gray-300 px-4 py-2 text-left">Nombre</th>
                                <th class="border border-gray-300 px-4 py-2 text-left">Correo</th>
                                <th class="border border-gray-300 px-4 py-2 text-left">Activo</th>
                                <th class="border border-gray-300 px-4 py-2 text-center"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="driver in filteredItems" :key="driver.id">
                                <td class="border border-gray-300 px-4 py-2">{{ driver.id }}</td>
                                <td class="border border-gray-300 px-4 py-2">{{ driver.name }}</td>
                                <td class="border border-gray-300 px-4 py-2">{{ driver.email }}</td>
                                <td class="border border-gray-300 px-4 py-2">{{ driver.active === 1 ? 'Activo' : 'Inactivo' }}</td>
                                <td class="border border-gray-300 px-4 py-2 text-center">
                                    <!--<button 
                                        @click="() => {openEditDriverModal(driver.id)}" 
                                        class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm"
                                    >
                                        Editar
                                    </button>-->
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div v-if="filteredItems.length === 0" class="text-center py-8 text-gray-500">
                        No hay operadores registrados
                    </div>
                </div>
            </div>
        </div>
    </div>
    <drivermodal 
        :show="showDriverModal" 
        :idUser="idUser"
        @close="closeDriverModal"
        @saved="driverSaved"
    />
</template>

<script setup>
    import { inject, ref, computed, watch } from "vue";
    import { actionslist } from '../composables/actionslist';
    import drivermodal from "./drivermodal.vue";

    const showDriverModal = ref(false);
    const idUser = ref(null);
    const dialogs = inject("swal");

    const props = defineProps({
        show: {
            type: Boolean,
            default: false
        }
    });

    const emit = defineEmits(['close']);

    const { items, deleteItem, loadItems } = actionslist({
        endpoint: 'users',
        dialogs
    });

    // Filtrar solo usuarios con role_id === 8 (Operadores)
    const filteredItems = computed(() => {
        return items.value.filter(user => user.role_id === 8);
    });

    // Cargar datos cuando se abre el modal
    watch(() => props.show, (newValue) => {
        if (newValue) {
            loadItems();
        }
    });

    const openDriverModal = () => {
        idUser.value = null
        showDriverModal.value = true;
    };

    const openEditDriverModal = (id) => {
        idUser.value = id
        showDriverModal.value = true;
    };

    const closeDriverModal = () => {
        showDriverModal.value = false;
    };

    const driverSaved = () => {
        loadItems();
    }

</script>
