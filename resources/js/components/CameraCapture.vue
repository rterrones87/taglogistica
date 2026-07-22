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
  },
  endpoint: {
    type: String,
    default: 'treasury/upload-photos'
  }
});

// refs
const camera = ref(null);
const showCamera = ref(true);
const photos = ref([]);

// Comprimir imagen usando Canvas API
const compressImage = async (blob, maxWidth = 1920, maxHeight = 1080, quality = 0.8) => {
  return new Promise((resolve, reject) => {
    const img = new Image();
    const url = URL.createObjectURL(blob);
    
    img.onload = () => {
      // Calcular nuevas dimensiones manteniendo aspect ratio
      let width = img.width;
      let height = img.height;
      
      if (width > maxWidth || height > maxHeight) {
        const ratio = Math.min(maxWidth / width, maxHeight / height);
        width = width * ratio;
        height = height * ratio;
      }
      
      // Crear canvas y comprimir
      const canvas = document.createElement('canvas');
      canvas.width = width;
      canvas.height = height;
      
      const ctx = canvas.getContext('2d');
      ctx.drawImage(img, 0, 0, width, height);
      
      // Convertir a blob comprimido
      canvas.toBlob(
        (compressedBlob) => {
          URL.revokeObjectURL(url);
          if (compressedBlob) {
            resolve(compressedBlob);
          } else {
            reject(new Error('Error al comprimir imagen'));
          }
        },
        'image/jpeg',
        quality
      );
    };
    
    img.onerror = () => {
      URL.revokeObjectURL(url);
      reject(new Error('Error al cargar imagen'));
    };
    
    img.src = url;
  });
};

// Tomar una foto
const takePhoto = async () => {
  try {
    // Usar las dimensiones reales del stream para evitar distorsión
    const videoEl = camera.value?.video;
    const blob = await camera.value?.snapshot({
      width: videoEl?.videoWidth || 1920,
      height: videoEl?.videoHeight || 1080,
    });
    
    // Comprimir la imagen antes de agregarla
    const compressedBlob = await compressImage(blob);
    
    photos.value.push({
      url: URL.createObjectURL(compressedBlob),
      blob: compressedBlob,
    });
  } catch (error) {
    console.error('Error al procesar la foto:', error);
    dialogs.fire("Error", "No se pudo procesar la foto. Intente de nuevo.", "error");
  }
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

    const res = await axios.post(`${props.endpoint}/${props.id}`, formData, {
      headers: { "Content-Type": "multipart/form-data" },
    });
    
    dialogs.close();
    
    dialogs.fire("Excelente!", "Las imagenes se han cargado correctamente.", "success");

    // Vaciar fotos tras subida
    photos.value = [];

    emit('OnUpload');

  } catch (err) {
    console.error(err);
    dialogs.close();
    
    let errorMsg = "Ocurrió un error inesperado, intente de nuevo.";
    if (err.response?.data?.message) {
      errorMsg = err.response.data.message;
    } else if (err.response?.status === 422) {
      errorMsg = "Las imágenes no cumplen con los requisitos del servidor.";
    }
    
    dialogs.fire("Lo sentimos!", errorMsg, "error");
  }
};
</script>
