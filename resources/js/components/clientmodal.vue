<template>
    <BaseModal
        :show="show"
        title="Cliente"
        size="4xl"
        @close="closeModal"
    >
                <clientForm 
                :key="idClient || 'new-client'"
                :isModal="true"
                :idClient="idClient"
                @saved="onClientSaved"/>
    </BaseModal>
</template>

<script setup>
    import BaseModal from './BaseModal.vue';
    import clientForm from '../pages/forms/client.vue';

    const props = defineProps({
        show: {
            type: Boolean,
            default: false
        },
        idClient: {
            type: Number | null,
            default: null
        }
    });

    const emit = defineEmits(['close', 'saved']);

    const closeModal = () => {
        emit('close');
    };

    const onClientSaved = () => {
        emit('saved');
        closeModal();
    };
</script>