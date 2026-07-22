<template>
    <BaseModal
        :show="show"
        title="Operador"
        size="4xl"
        @close="closeModal"
    >
                <userForm 
                :key="idUser || 'new-driver'"
                :isModal="true"
                :idUser="idUser"
                :fixedRoleId="8"
                @saved="onDriverSaved"/>
    </BaseModal>
</template>

<script setup>
    import BaseModal from './BaseModal.vue';
    import userForm from '../pages/forms/user.vue';

    const props = defineProps({
        show: {
            type: Boolean,
            default: false
        },
        idUser: {
            type: Number | null,
            default: null
        }
    });

    const emit = defineEmits(['close', 'saved']);

    const closeModal = () => {
        emit('close');
    };

    const onDriverSaved = () => {
        emit('saved');
        closeModal();
    };
</script>
