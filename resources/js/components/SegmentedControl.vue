<script setup>
import { defineProps, ref, watch, onMounted  } from 'vue'

const emit = defineEmits(['OnClicked'])

const props = defineProps({
  titles: {
    type: Array,
    required: true
  },
  modelValue: {          // 👈 valor actual desde fuera (opcional)
    type: [String, Number],
    default: '1'         // 👈 valor por defecto si no se pasa
  }
})

// Estado interno (reactivo)
const current = ref(props.modelValue)

// Si el padre cambia el valor, sincronizamos
watch(() => props.modelValue, (newVal) => {
  current.value = newVal
})

// Evento al hacer clic
const handlerClick = (id) => {
  current.value = id
  emit('OnClicked', id)
  emit('update:modelValue', id) // 👈 para soportar v-model desde el padre
}

onMounted(() => {
  current.value = props.modelValue
})

</script>

<template>
  <nav class="w-full flex justify-between my-4 rounded-[3px] overflow-hidden">
    <button
      v-for="title in titles"
      :key="title.id"
      type="button"
      @click="handlerClick(title.id)"
      class="flex-1 flex justify-center items-center px-2 md:px-8 py-2 text-center border border-slate-300"
      :class="{ 'text-white bg-dynamic-color first:rounded-l last:rounded-r': current === title.id }"
    >
      <img 
        v-if="title.icon" 
        :src="`/img/${title.icon}`"
        class="w-4 h-4 md:hidden" 
        :class="{ '!hidden': current === title.id }"
      />
      <span 
        class="hidden md:block whitespace-nowrap"
        :class="{ '!block': current === title.id }"
      >
        {{ title.label }}
      </span>
    </button>
  </nav>
</template>
