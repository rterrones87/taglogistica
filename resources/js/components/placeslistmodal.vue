<template>
    <BaseModal
        :show="show"
        title="Administrar Destinos"
        size="4xl"
        @close="$emit('close')"
    >
                <div class="mb-4 flex justify-end">
                    <button 
                        @click="openPlaceModal" 
                        class="form-button"
                    >
                        Agregar nuevo destino
                    </button>
                </div>
                
                <div class="overflow-auto max-h-96">
                    <table class="w-full border-collapse border border-gray-300">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border border-gray-300 px-4 py-2 text-left">ID</th>
                                <th class="border border-gray-300 px-4 py-2 text-left">Nombre</th>
                                <th class="border border-gray-300 px-4 py-2 text-center"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="place in items" :key="place.id">
                                <td class="border border-gray-300 px-4 py-2">{{ place.id }}</td>
                                <td class="border border-gray-300 px-4 py-2">{{ place.name }}</td>
                                <td class="border border-gray-300 px-4 py-2 text-center">
                                    <!--<button 
                                        @click="() => {openEditPlaceModal(place.id)}" 
                                        class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm"
                                    >
                                        Editar
                                    </button>-->
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div v-if="items.length === 0" class="text-center py-8 text-gray-500">
                        No hay destinos registrados
                    </div>
                </div>
    </BaseModal>
    <placemodal
        :show="showPlaceModal" 
        :idPlace="idPlace"
        @close="closePlaceModal"
        @saved="placeSaved"
    />
</template>

<script setup>
    import { inject, ref, watch } from "vue";
    import { actionslist } from '../composables/actionslist';
    import BaseModal from './BaseModal.vue';
    import placemodal from "./placemodal.vue";

    const showPlaceModal = ref(false);
    const idPlace = ref(null);
    const dialogs = inject("swal");

    const props = defineProps({
        show: {
            type: Boolean,
            default: false
        }
    });

    const emit = defineEmits(['close']);

    const { items, deleteItem, loadItems } = actionslist({
        endpoint: 'places',
        dialogs
    });

    // Cargar datos cuando se abre el modal
    watch(() => props.show, (newValue) => {
        if (newValue) {
            loadItems();
        }
    });

    const openPlaceModal = () => {
        idPlace.value = null
        showPlaceModal.value = true;
    };

    const openEditPlaceModal = (id) => {
        idPlace.value = id
        showPlaceModal.value = true;
    };

    const closePlaceModal = () => {
        showPlaceModal.value = false;
    };

    const placeSaved = () => {
        loadItems();
    }

</script>
