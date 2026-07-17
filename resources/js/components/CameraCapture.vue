<template>
  <div>
    
    <!-- Cámara -->
    <div v-if="showCamera">
      <simple-vue-camera ref="camera" :facingMode="'environment'" />
      <button @click="takePhoto" class="button ms-auto">Tomar Foto</button>
    </div>

    <!-- Lista de fotos -->
    <div v-if="photos.length > 0" class="my-4">
      <h3>Fotos tomadas:</h3>
      <div class="grid grid-cols-3 gap-3">
        <div v-for="(p, i) in photos" :key="i" class="relative">
          <img
            :src="p.url"
            alt="captura"
          />
          <!-- Botón eliminar foto -->
          <button
            @click="removePhoto(i)"
            class="absolute top-0 right-0 bg-white/20 text-white w-8 h-8 border-0 outline-0"
          >
            x
          </button>
        </div>
      </div>
      <button @click="uploadPhotos" class="button ms-auto">
        Subir todas
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, inject } from "vue";
import SimpleVueCamera from "simple-vue-camera";
import axios from "axios";

const dialogs = inject("swal");

const emit = defineEmits(['OnUpload'])

// Props
const props = defineProps({
  id: {
    type: Number,
    required: true,
  }
});

// refs
const camera = ref(null);
const showCamera = ref(true);
const photos = ref([]);

// Tomar una foto
const takePhoto = async () => {
  const blob = await camera.value?.snapshot(); 
  photos.value.push({
    url: URL.createObjectURL(blob),
    blob: blob,
  });
};


// Eliminar foto de la lista
const removePhoto = (index) => {
  photos.value.splice(index, 1);
};

// Subir todas las fotos
const uploadPhotos = async () => {
  if (photos.value.length === 0) return;

  const formData = new FormData();
  photos.value.forEach((p, i) => {
    formData.append("photos[]", p.blob, `evidencia-${i + 1}.jpg`);
  });

  try {
    
    dialogs.fire({
        title: "Procesando...",
        text: "Por favor, espere",
        allowOutsideClick: false,
        didOpen: () => dialogs.showLoading()
    });

    const res = await axios.post(`treasury/upload-photos/${props.id}`, formData, {
      headers: { "Content-Type": "multipart/form-data" },
    });
    
    dialogs.close();
    
    dialogs.fire("Excelente!", "Las imagenes se han cargado correctamente.", "success");

    // Vaciar fotos tras subida
    photos.value = [];

    emit('OnUpload');

  } catch (err) {
    console.error(err);
    dialogs.fire("Lo sentimos!", "Ocurrio un errors inesperado, intente de nuevo.", "error");
  }
};
</script>
