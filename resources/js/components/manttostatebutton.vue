<script setup>
import { inject, ref, computed, watch } from 'vue';
import CameraCapture from './CameraCapture.vue';
import BaseModal from './BaseModal.vue';
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
  hasEvidencePermission: {
    type: Boolean,
    default: false,
  },
  hasEvidences: {
    type: Boolean,
    default: false,
  },
});

const showCameraModal = ref(false);

const handlerOnUpload = () => {
  showCameraModal.value = false;
  dialogs.fire("Excelente!", "Las evidencias se han cargado correctamente.", "success");
};

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

  } catch (error) {
    console.error('Error al cambiar de estado', error);
    dialogs.close();
    if (error.response?.status === 422) {
      dialogs.fire("Atención", error.response.data.message, "warning");
    } else {
      dialogs.fire("Error", "No se pudo cambiar el estado.", "error");
    }
  }
};
</script>

<template>
  <button
    v-if="currentStateId === 3 && hasEvidencePermission"
    @click="showCameraModal = true"
    class="bg-green-600 hover:bg-green-700 text-white px-4 py-1 rounded"
  >
    <span class="whitespace-nowrap">
      <small class="block text-[14px]">Cargar</small>
      <small class="block text-[12px]">evidencias</small>
    </span>
  </button>

  <button
    @click="changeState"
    :disabled="!nextState || (currentStateId === 3 && !hasEvidences)"
    class="text-white px-4 py-1 rounded disabled:opacity-50 disabled:cursor-not-allowed"
    :class="currentStateId === 3 && !hasEvidences
      ? 'bg-gray-400 cursor-not-allowed'
      : 'bg-[#234053] hover:bg-[#18364a]'"
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

  <BaseModal
    :show="showCameraModal"
    title="Evidencias de Mantenimiento"
    @close="showCameraModal = false"
  >
    <CameraCapture
      :id="manttoId"
      endpoint="maintenances/upload-photos"
      @OnUpload="handlerOnUpload"
    />
  </BaseModal>
</template>
