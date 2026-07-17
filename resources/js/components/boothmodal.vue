<template>
    <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl mx-4 max-h-[90vh] overflow-hidden">
            <div class="flex justify-between items-center p-6 border-b">
                <h3 class="text-xl font-bold">Caseta</h3>
                <button @click="closeModal" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="overflow-auto max-h-[80vh]">
                <boothForm 
                :key="idBooth || 'new-booth'"
                :isModal="true"
                :idBooth="idBooth"
                @saved="onBoothSaved"/>
            </div>
        </div>
    </div>
</template>

<script setup>
    import boothForm from '../pages/forms/booth.vue';

    const props = defineProps({
        show: {
            type: Boolean,
            default: false
        },
        idBooth: {
            type: Number | null,
            default: null
        }
    });

    const emit = defineEmits(['close', 'saved']);

    const closeModal = () => {
        emit('close');
    };

    const onBoothSaved = () => {
        emit('saved');
        closeModal();
    };
</script>
