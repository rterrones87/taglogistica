<template>
    <BaseModal
        :show="show"
        title="Administrar Casetas"
        size="4xl"
        @close="$emit('close')"
    >
                <div class="mb-4 flex justify-end">
                    <button 
                        @click="openBoothModal" 
                        class="form-button"
                    >
                        Agregar nueva caseta
                    </button>
                </div>
                
                <div class="overflow-auto max-h-96">
                    <table class="w-full border-collapse border border-gray-300">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border border-gray-300 px-4 py-2 text-left">ID</th>
                                <th class="border border-gray-300 px-4 py-2 text-left">Nombre</th>
                                <th class="border border-gray-300 px-4 py-2 text-left">Costo</th>
                                <th class="border border-gray-300 px-4 py-2 text-center"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="booth in items" :key="booth.id">
                                <td class="border border-gray-300 px-4 py-2">{{ booth.id }}</td>
                                <td class="border border-gray-300 px-4 py-2">{{ booth.name }}</td>
                                <td class="border border-gray-300 px-4 py-2">{{ booth.cost }}</td>
                                <td class="border border-gray-300 px-4 py-2 text-center">
                                    <!--<button 
                                        @click="() => {openEditBoothModal(booth.id)}" 
                                        class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm"
                                    >
                                        Editar
                                    </button>-->
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div v-if="items.length === 0" class="text-center py-8 text-gray-500">
                        No hay casetas registradas
                    </div>
                </div>
    </BaseModal>
    <boothmodal
        :show="showBoothModal" 
        :idBooth="idBooth"
        @close="closeBoothModal"
        @saved="boothSaved"
    />
</template>

<script setup>
    import { inject, ref, watch } from "vue";
    import { actionslist } from '../composables/actionslist';
    import BaseModal from './BaseModal.vue';
    import boothmodal from "./boothmodal.vue";

    const showBoothModal = ref(false);
    const idBooth = ref(null);
    const dialogs = inject("swal");

    const props = defineProps({
        show: {
            type: Boolean,
            default: false
        }
    });

    const emit = defineEmits(['close']);

    const { items, deleteItem, loadItems } = actionslist({
        endpoint: 'booths',
        dialogs
    });

    // Cargar datos cuando se abre el modal
    watch(() => props.show, (newValue) => {
        if (newValue) {
            loadItems();
        }
    });

    const openBoothModal = () => {
        idBooth.value = null
        showBoothModal.value = true;
    };

    const openEditBoothModal = (id) => {
        idBooth.value = id
        showBoothModal.value = true;
    };

    const closeBoothModal = () => {
        showBoothModal.value = false;
    };

    const boothSaved = () => {
        loadItems();
    }

</script>
