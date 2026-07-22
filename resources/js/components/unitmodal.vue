<template>
    <BaseModal
        :show="show"
        title="Asignar TAG"
        size="4xl"
        @close="closeModal"
    >
                <unitForm 
                :key="idUnit || 'new-unit'"
                :isModal="true"
                :idUnit="idUnit"
                :blockAllExceptTag="true"
                @saved="onUnitSaved"/>
    </BaseModal>
</template>

<script setup>
    import BaseModal from './BaseModal.vue';
    import unitForm from '../pages/forms/unit.vue';

    const props = defineProps({
        show: {
            type: Boolean,
            default: false
        },
        idUnit: {
            type: Number | null,
            default: null
        }
    });

    const emit = defineEmits(['close', 'saved']);

    const closeModal = () => {
        emit('close');
    };

    const onUnitSaved = () => {
        emit('saved');
        closeModal();
    };
</script>
