<script setup>
import { inject, ref, computed, watch, onMounted } from 'vue';
import CameraCapture from './CameraCapture.vue';
import BaseModal from './BaseModal.vue';
import axios from 'axios';

const dialogs = inject("swal");

// Props
const props = defineProps({
  stateId:      { type: Number,  required: true },
  serviceId:    { type: Number,  required: true },
  approvalsMap: { type: Object,  default: () => ({}) },
  waybill:      { type: String,  default: null },
  // typeOperation eliminado — los substates se cargan dinámicamente desde la API
});

// IDs de "Inicia Flete" (impo/suelta=2, expo=11)
const INICIA_FLETE_IDS = [2, 11];

// Bloqueado cuando el siguiente substate es "Inicia Flete" y no se cumplen condiciones
const isBlockedForFlete = computed(() => {
  if (!nextState.value) return false;
  if (!INICIA_FLETE_IDS.includes(nextState.value.id)) return false;

  const expensesApproved = props.approvalsMap?.initial_expenses === 'approved';
  const hasWaybill       = !!props.waybill;

  return !expensesApproved || !hasWaybill;
});

// Mensaje explicativo del bloqueo
const blockedMessage = computed(() => {
  if (!isBlockedForFlete.value) return null;
  const expensesApproved = props.approvalsMap?.initial_expenses === 'approved';
  const hasWaybill       = !!props.waybill;

  if (!expensesApproved && !hasWaybill) return 'Requiere gastos aprobados y carta porte';
  if (!expensesApproved) return 'Requiere gastos iniciales aprobados';
  return 'Requiere carta porte asignada';
});

const showCameraModal = ref(false);

// Emit
const emit = defineEmits(['updated']);

// Reactive currentStateId
const currentStateId = ref(props.stateId);

// Watch por si el prop cambia externamente
watch(() => props.stateId, (newVal) => {
  currentStateId.value = newVal;
});

// Lista de subestados cargada desde la API
const stateList = ref([]);

const loadSubstates = async () => {
  if (!props.serviceId) return;
  const { data } = await axios.get(`substates/for-service/${props.serviceId}`);
  // Insertar substate 0 al inicio si no viene en la lista
  const hasCero = data.some(s => s.id === 0);
  stateList.value = hasCero ? data : [{ id: 0, name: '' }, ...data];
};

onMounted(loadSubstates);

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

  currentStateId.value = nextState.value.id;
  emit('updated', currentStateId.value);

  dialogs.close();
  dialogs.fire("Excelente!", "El cambio de estado se ha aplicado.", "success");

  // Recargar si hubo pase de estafeta (el operador ya no verá el servicio)
  if (response.data?.operator_changed) {
    location.reload();
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
    :disabled="!nextState || isBlockedForFlete"
    :title="blockedMessage || ''"
    class="bg-[#234053] hover:bg-[#18364a] text-white px-4 py-1 rounded disabled:opacity-50 disabled:cursor-not-allowed"
  >
    <span v-if="nextState" class="whitespace-nowrap">
      <small class="block text-[14px] opacity-70">Cambiar a</small>
      <small class="block text-[12px]">{{ nextState.name }}</small>
      <small v-if="isBlockedForFlete" class="block text-[10px] text-red-300">{{ blockedMessage }}</small>
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
