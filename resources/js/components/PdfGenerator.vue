<script setup>
import { ref, nextTick, defineExpose, watchEffect } from 'vue'
import axios from 'axios'
import VueHtml2pdf from 'vue3-html2pdf'

// Props
const props = defineProps({
  endpoint: {
    type: String,
    required: true,
  },
  title: {
    type: String,
    required: true,
  }
})

// Refs y datos
const html2PdfRef = ref(null)
const pdfContent = ref(null)
const data = ref([])
const pdfUrl = ref(null)

// Método público para generar PDF (expuesto)
const generate = async (url) => {
  try {
    const res = await axios.get(url)
    data.value = res.data
    console.log(props.title)
    await nextTick()

    await html2PdfRef.value.generatePdf()

  } catch (err) {
    console.error('Error al generar PDF:', err)
    throw err
  }
}

const generateWithData = async (inputData) => {
  try {
    data.value = inputData
    
    await nextTick()

    await html2PdfRef.value.generatePdf()

  } catch (err) {
    console.error('Error al generar PDF:', err)
    throw err
  }
}



const onProgress = (event) => {
    console.log(event)
}

const hasStartedGeneration = (event) => {
    console.log(event)
}

const hasGenerated = (event) => {
    console.log(event)
}

// Exponer método
defineExpose({ generate, generateWithData })
</script>

<template>
  <div>
    <!-- Contenedor PDF invisible -->
    <vue-html2pdf
        ref="html2PdfRef"
        :show-layout="false"
        :float-layout="true"
        :enable-download="true"
        :preview-modal="false"
        :paginate-elements-by-height="1400"
        :filename="title"
        :pdf-quality="2"
        :manual-pagination="false"
        pdf-format="a4"
        pdf-orientation="portrait"
        pdf-content-width="800px"
        @progress="onProgress($event)"
        @hasStartedGeneration="hasStartedGeneration()"
        @hasGenerated="hasGenerated($event)"
    >
      <template #pdf-content>
        <slot name="pdf" :data="data"/>
      </template>
    </vue-html2pdf>
  </div>
</template>

