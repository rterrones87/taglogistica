<template>
    <BaseModal
        :show="show"
        title="Destino"
        size="4xl"
        @close="closeModal"
    >
                <placeForm 
                :key="idPlace || 'new-place'"
                :isModal="true"
                :idPlace="idPlace"
                @saved="onPlaceSaved"/>
    </BaseModal>
</template>

<script setup>
    import BaseModal from './BaseModal.vue';
    import placeForm from '../pages/forms/place.vue';

    const props = defineProps({
        show: {
            type: Boolean,
            default: false
        },
        idPlace: {
            type: Number | null,
            default: null
        }
    });

    const emit = defineEmits(['close', 'saved']);

    const closeModal = () => {
        emit('close');
    };

    const onPlaceSaved = () => {
        emit('saved');
        closeModal();
    };
</script>
