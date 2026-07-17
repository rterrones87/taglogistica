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
  serviceId: {
    type: Number,
    required: true,
  },
  typeOperation: {
    type: String,
    required: true,
  },
});

const showCameraModal =ref(false);

// Emit
const emit = defineEmits(['updated']);

// Reactive currentStateId
const currentStateId = ref(props.stateId);

// Watch por si el prop cambia externamente
watch(() => props.stateId, (newVal) => {
  currentStateId.value = newVal;
});

// Estados por tipo de operación
const states = {
  impo: [
    { id: 0, name: '' },
    { id: 1, name: 'Cargar en Puerto' },
    { id: 2, name: 'Inicia flete' },
    { id: 3, name: 'Llegada a Cliente' },
    { id: 4, name: 'Inicio descarga' },
    { id: 5, name: 'Termino descarga' },
    { id: 6, name: 'Salida de Cliente' },
    { id: 7, name: 'Llegada a patio TAG' },
    { id: 8, name: 'Entrega de vacio' },
  ],
  expo: [
    { id:  0, name: '' },
    { id:  9, name: 'Recolección de Vacío' },
    { id: 10, name: 'Vacío Cargado' },
    { id: 11, name: 'Inicia Flete' },
    { id: 12, name: 'Llegada a Cliente' },
    { id: 13, name: 'Inicia Carga' },
    { id: 14, name: 'Finaliza Carga' },
    { id: 15, name: 'Salida de Cliente' },
    { id: 16, name: 'Llegada a Patio TAG' },
    { id: 17, name: 'Inicia Ingreso de Carga' },
    { id: 18, name: 'Ingreso de Carga Concluido' },
  ],
};

// Lista de estados según typeOperation
const stateList = computed(() => {
  return states[props.typeOperation] || [];
});

// Calcula el índice actual y el siguiente estado si existe
const currentIndex = computed(() =>
  stateList.value.findIndex(s => s.id === currentStateId.value)
);

const nextState = computed(() => {
  if (currentIndex.value === -1) return null;
  const nextIndex = currentIndex.value + 1;
  return stateList.value[nextIndex] || null;
});

async function applyState() {
  dialogs.fire({
        title: "Procesando...",
        text: "Por favor, espere",
        allowOutsideClick: false,
        didOpen: () => dialogs.showLoading()
    });

    const response = await axios.post('services/change_substate', {
      substate_id: nextState.value.id,
      service_id: props.serviceId
    });

    // Si la petición fue exitosa, actualiza el estado
    currentStateId.value = nextState.value.id;

    // Emite un evento para el padre si necesita saberlo
    emit('updated', currentStateId.value);

    dialogs.close();
    dialogs.fire("Excelente!", "El cambio de estado se ha aplicado.", "success");

    if( currentStateId.value == 7 ||  currentStateId.value == 9) {
      location.reload()
    }
}

// Acción al dar click
const changeState = async () => {
  if (!nextState.value) return;

  try {
    if(nextState.value.id == 5 || nextState.value.id == 14) {

      showCameraModal.value = true;

    } else {
      applyState();
      /*
        dialogs.fire({
            title: "Procesando...",
            text: "Por favor, espere",
            allowOutsideClick: false,
            didOpen: () => dialogs.showLoading()
        });

        const response = await axios.post('services/change_substate', {
          substate_id: nextState.value.id,
          service_id: props.serviceId
        });

        // Si la petición fue exitosa, actualiza el estado
        currentStateId.value = nextState.value.id;

        // Emite un evento para el padre si necesita saberlo
        emit('updated', currentStateId.value);

        dialogs.close();
        dialogs.fire("Excelente!", "El cambio de estado se ha aplicado.", "success");

        if( currentStateId.value == 7 ||  currentStateId.value == 9) {
          location.reload()
        } 
      */
    }

  } catch (error) {
    console.error('Error al cambiar de estado', error);
    dialogs.close();
  }
};

const handlerOnUpload = async () => {
  showCameraModal.value = false;
  applyState();
}

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
      <small class="block text-[14px]">Terminado</small>
      <small class="block text-[12px]">Estado final alcanzado</small>
    </span>

    
  </button>

  <BaseModal
    :show="showCameraModal"
    title="Evidencias"
    @close="showCameraModal = false"
  >
    <CameraCapture 
      :id="serviceId"
      @OnUpload="handlerOnUpload"
    />
  </BaseModal>

  
</template>
