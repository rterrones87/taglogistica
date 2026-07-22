<template>
    <BaseModal
        :show="show"
        title="Caseta"
        size="4xl"
        @close="closeModal"
    >
                <boothForm 
                :key="idBooth || 'new-booth'"
                :isModal="true"
                :idBooth="idBooth"
                @saved="onBoothSaved"/>
    </BaseModal>
</template>

<script setup>
    import BaseModal from './BaseModal.vue';
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
