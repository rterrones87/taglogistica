import { ref, onMounted } from "vue";
import axios from "axios";
import { useRoute, useRouter } from "vue-router";

export function upsert(config) {
    const route = useRoute();
    const router = useRouter();

    // Limpiar el objeto de datos iniciales: si id es null/undefined, no incluirlo
    let initialData;
    try {
        initialData = structuredClone(config.data);
        // Asegurar que initialData es un objeto
        if (typeof initialData !== 'object' || initialData === null) {
            console.error('config.data no es un objeto válido:', config.data);
            initialData = {};
        }
        if (initialData.id == null || initialData.id === 0) {
            delete initialData.id;
        }
    } catch (error) {
        console.error('Error al clonar config.data:', error);
        initialData = {};
    }
    
    const item = ref(initialData);
    const isEditing = ref(false);
    const errors = ref({});
    const dialogs = config.dialogs;

    const loadItem = async (id) => {
        try {
            dialogs.fire({
                title: "Cargando...",
                text: "Por favor, espere",
                allowOutsideClick: false,
                didOpen: () => dialogs.showLoading()
            });

            const response = await axios.get(`${config.endpoint}/${id}`);
            //console.log(response.data)
            item.value = response.data;

        } catch (error) {
            console.error("Error cargando el recurso:", error);
            // En caso de error, restaurar el objeto inicial
            item.value = structuredClone(config.data);
        } finally {
            dialogs.close();
        }
    };

    const saveItem = async () => {
        try {
            dialogs.fire({
                title: "Procesando...",
                text: "Por favor, espere",
                allowOutsideClick: false,
                didOpen: () => dialogs.showLoading()
            });

            if (isEditing.value) {
                let id = item.value.id ? item.value.id : route.params.id 
                await axios.put(`${config.custom_put || config.endpoint}/${id}`, item.value);
                dialogs.close();
                dialogs.fire("Excelente!", "El registro ha sido actualizado correctamente.", "success").then(() => {
                    if (config.redirectOnCreate) {
                        location.href = "/panel/" + config.redirectOnCreate
                    }
                })

                if (config.onCreatedListener) {
                    config.onCreatedListener();
                }
            } else {
                await axios.post(`${config.endpoint}`, item.value);
                dialogs.close();
                dialogs.fire("Excelente!", "El registro ha sido creado correctamente.", "success").then(() => {

                    // Si quieres redirigir después de crear:
                    if (config.redirectOnCreate) {
                        location.href = "/panel/" + config.redirectOnCreate
                    }

                });

                

                if (config.onCreatedListener) {
                    config.onCreatedListener()
                }
            }
        } catch (error) {
            dialogs.close();
            if (error.response && error.response.status === 400) {
                errors.value = error.response.data;
            } else {
                console.error("Error guardando el recurso:", error);
            }
        }
    };

    onMounted(async () => {
        // Priorizar el ID explícitamente pasado en config.data
        const dataId = item.value.hasOwnProperty('id') ? item.value.id : null;
        
        // Solo usar route.params.id si no se debe ignorar y no hay dataId
        const paramId = config.ignoreRouteParams ? null : route.params.id;
        
        // El ID final es dataId, o paramId si dataId no existe
        const id = (dataId != null && dataId !== 0) ? dataId : paramId;
        
        if (id != null && id !== 0 && id !== '') {
            isEditing.value = true;
            await loadItem(id);
        }
    });

    return {
        item,
        isEditing,
        errors,
        saveItem,
        loadItem
    };
}
