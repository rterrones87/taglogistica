<script setup>
import GenericAction from '@/components/GenericAction.vue';
import * as XLSX from "xlsx";
import { saveAs } from "file-saver";
import axios from "axios";

const props = defineProps({
  endpoint: { type: String, required: true },
  fields: { type: Array, required: true }, // Ej: ["id", "nombre", "email"]
  headers: { type: Array, required: true }, // Ej: ["ID", "Nombre", "Correo"]
  fileName: { type: String, default: "reporte.xlsx" },
  buttonLabel: { type: String, default: "Descargar Excel" },
  filters: { type: Object, default: () => ({}) } // Filtros opcionales a enviar al backend
});

const descargarExcel = async () => {
  try {
    // 1. Llamada a la API con filtros como query params
    const { data } = await axios.get(props.endpoint, { params: props.filters });

    // 2. Filtrar solo los campos requeridos
    const rows = data.map(item =>
      props.fields.map(field => item[field] ?? "")
    );

    // 3. Crear hoja con headers + rows
    const worksheet = XLSX.utils.aoa_to_sheet([
      props.headers,
      ...rows
    ]);

    // 4. Crear libro
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "Datos");

    // 5. Generar archivo
    const excelBuffer = XLSX.write(workbook, { bookType: "xlsx", type: "array" });

    // 6. Descargar archivo
    saveAs(
      new Blob([excelBuffer], { type: "application/octet-stream" }),
      props.fileName
    );
  } catch (error) {
    console.error("Error al generar Excel:", error);
  }
};
</script>

<template>
  <GenericAction
    :title="buttonLabel"
    icon="export.png"
    @click="descargarExcel"
  />
</template>
