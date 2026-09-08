<template>
    <p v-if="errors?.length" class="text-sm text-red-500">
        {{ errors[0] }}
    </p>
</template>

<script setup>
defineProps({
    errors: {
        type: Array,
        default: () => [],
    },
});
</script>
