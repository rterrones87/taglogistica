<template>
  <Transition name="modal-backdrop">
    <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 z-50 p-4 flex items-start justify-center">
      <Transition name="modal-slide">
        <div 
          v-if="show"
          :class="['bg-white rounded-lg shadow-xl w-full overflow-y-auto mt-8', sizeClass]"
          :style="{ maxHeight: `calc(${height} - 2rem)` }"
        >
      <!-- Header -->
      <div v-if="showHeader" class="flex justify-between items-center p-6 border-b">
        <slot name="header">
          <h3 class="text-xl font-bold">{{ title }}</h3>
        </slot>
        <button @click="handleClose" class="text-gray-500 hover:text-gray-700">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <!-- Content -->
      <div class="p-6">
        <slot></slot>
      </div>

      <!-- Footer (opcional) -->
      <div v-if="$slots.footer" class="border-t p-6">
        <slot name="footer"></slot>
      </div>
        </div>
      </Transition>
    </div>
  </Transition>
</template>

<script setup>
import { computed, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
  show: {
    type: Boolean,
    required: true
  },
  title: {
    type: String,
    required: true
  },
  size: {
    type: String,
    default: 'lg',
    validator: (value) => ['sm', 'md', 'lg', 'xl', '2xl', '4xl'].includes(value)
  },
  height: {
    type: String,
    default: '90%'
  },
  showHeader: {
    type: Boolean,
    default: true
  },
  closeOnEscape: {
    type: Boolean,
    default: true
  }
});

const emit = defineEmits(['close']);

// Mapeo de tamaños a clases de Tailwind
const sizeClass = computed(() => {
  const sizeMap = {
    'sm': 'max-w-sm',
    'md': 'max-w-md',
    'lg': 'max-w-lg',
    'xl': 'max-w-xl',
    '2xl': 'max-w-2xl',
    '4xl': 'max-w-4xl'
  };
  return sizeMap[props.size] || 'max-w-lg';
});

// Función para cerrar el modal
const handleClose = () => {
  emit('close');
};

// Listener para tecla ESC
const handleEscape = (event) => {
  if (props.closeOnEscape && props.show && event.key === 'Escape') {
    handleClose();
  }
};

// Agregar/remover listener de teclado
onMounted(() => {
  window.addEventListener('keydown', handleEscape);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleEscape);
});

// También escuchar cambios en el prop show para manejar el scroll del body
watch(() => props.show, (newValue) => {
  if (newValue) {
    // Prevenir scroll del body cuando el modal está abierto
    document.body.style.overflow = 'hidden';
  } else {
    // Restaurar scroll del body cuando el modal se cierra
    document.body.style.overflow = '';
  }
});

// Limpiar el overflow al desmontar el componente
onUnmounted(() => {
  document.body.style.overflow = '';
});
</script>

<style scoped>
/* Animación del backdrop (fondo) */
.modal-backdrop-enter-active,
.modal-backdrop-leave-active {
  transition: opacity 0.3s ease;
}

.modal-backdrop-enter-from,
.modal-backdrop-leave-to {
  opacity: 0;
}

/* Animación del modal (slide down/up con fade) */
.modal-slide-enter-active {
  transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.3s ease;
}

.modal-slide-leave-active {
  transition: transform 0.35s cubic-bezier(0.7, 0, 0.84, 0), opacity 0.25s ease;
}

.modal-slide-enter-from {
  transform: translateY(-200px) scale(0.95);
  opacity: 0;
}

.modal-slide-leave-to {
  transform: translateY(-150px) scale(0.98);
  opacity: 0;
}
</style>
