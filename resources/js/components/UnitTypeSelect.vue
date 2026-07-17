<template>
    <select
        :value="modelValue"
        :disabled="disabled"
        :required="required"
        @change="onChange"
    >
        <option value="" disabled>{{ placeholder }}</option>
        <option v-for="type in items" :key="type.id" :value="type.id">
            {{ type.name }}
        </option>
    </select>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    modelValue: {
        type: [Number, String],
        default: '',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    required: {
        type: Boolean,
        default: false,
    },
    placeholder: {
        type: String,
        default: '-- Seleccione --',
    },
});

const emit = defineEmits(['update:modelValue']);
const items = ref([]);

const onChange = (event) => {
    const value = event.target.value;
    emit('update:modelValue', value === '' ? '' : Number(value));
};

onMounted(async () => {
    const { data } = await axios.get('unit-types');
    items.value = data;
});
</script>
