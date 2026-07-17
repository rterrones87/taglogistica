<script setup>
import { inject, ref, computed, watch } from 'vue';
import axios from 'axios';

const dialogs = inject("swal");

// Props
const props = defineProps({
  stateId: {
    type: Number,
    required: true,
  },
  manttoId: {
    type: Number,
    required: true,
  },
});

// Emit
const emit = defineEmits(['updated']);

// Reactive currentStateId
const currentStateId = ref(props.stateId);

// Watch por si el prop cambia externamente
watch(() => props.stateId, (newVal) => {
  currentStateId.value = newVal;
});

// Estados por tipo de operación
const stateList = ref([
    { id:0, name: ''},
    { id: 1, name: 'Solicitado' },
    { id: 2, name: 'Pendiente' },
    { id: 3, name: 'En Proceso' },
    { id: 4, name: 'Finalizado' },
]);


// Calcula el índice actual y el siguiente estado si existe
const currentIndex = computed(() =>
  stateList.value.findIndex(s => s.id === currentStateId.value)
);

const nextState = computed(() => {
  if (currentIndex.value === -1) return null;
  const nextIndex = currentIndex.value + 1;
  return stateList.value[nextIndex] || null;
});

// Acción al dar click
const changeState = async () => {
  if (!nextState.value) return;

  try {
    
    dialogs.fire({
        title: "Procesando...",
        text: "Por favor, espere",
        allowOutsideClick: false,
        didOpen: () => dialogs.showLoading()
    });

    const response = await axios.post('maintenances/change_state', {
      state_id: nextState.value.id,
      mantto_id: props.manttoId
    });

    // Si la petición fue exitosa, actualiza el estado
    currentStateId.value = nextState.value.id;

    // Emite un evento para el padre si necesita saberlo
    emit('updated', currentStateId.value);

    dialogs.close();
    dialogs.fire("Excelente!", "El cambio de estado se ha aplicado.", "success");

    /*if( currentStateId.value == 7 ||  currentStateId.value == 9) {
      location.reload()
    }*/

  } catch (error) {
    console.error('Error al cambiar de estado', error);
    dialogs.close();
  }
};
</script>

<template>
  <button
    @click="changeState"
    :disabled="!nextState"
    class="bg-[#234053] hover:bg-[#18364a] text-white px-4 py-1 rounded disabled:opacity-50 disabled:cursor-not-allowed"
  >
    <span v-if="nextState" class="whitespace-nowrap">
      <small class="block text-[14px] opacity-70">Cambiar a</small>
      <small class="block text-[12px]">{{ nextState.name }}</small>
    </span>
    <span v-else class="whitespace-nowrap">
      <small class="block text-[14px]">Finalizado</small>
      <small class="block text-[12px]">Estado final alcanzado</small>
    </span>

    
  </button>
</template>
