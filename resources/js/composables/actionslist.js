import { ref } from "vue";
import axios from "axios";

export function actionslist(config) {

    const items = ref([]);
    const dialogs = config.dialogs;
    let abortController = null;
    let debounceTimer = null;

    const loadItems = async (queryParams = {}, { signal } = {}) => {
        try {
            dialogs.fire({
                title: "Cargando...",
                text: "Por favor, espere",
                allowOutsideClick: false,
                didOpen: () => dialogs.showLoading()
            });

            const response = await axios.get(`${config.endpoint}`, { 
                params: queryParams,
                signal 
            }); 
            
            // Manejar respuesta en formato objeto con {data, week_range} o array directo
            if (response.data && typeof response.data === 'object' && response.data.data !== undefined) {
                items.value = response.data.data;
            } else {
                items.value = response.data;
            }

            dialogs.close();
            
            // Retornar la respuesta completa para que el componente pueda extraer week_range si existe
            return response.data;
            
        } catch (error) {
            if (axios.isCancel(error)) return;
            console.error('Error al obtener los recursos:', error);
        } finally {
            dialogs.close();
        }
    };

    const loadFilteredItems = (queryParams = {}) => {
      // Cancelar request anterior si existe
      if (abortController) abortController.abort();
      clearTimeout(debounceTimer);

      return new Promise((resolve) => {
        debounceTimer = setTimeout(async () => {
          abortController = new AbortController();
          const result = await loadItems(queryParams, { signal: abortController.signal });
          resolve(result);
        }, 300);
      });
    };
    
    const deleteItem = async (id) => {
        const result = await dialogs.fire({
            title: "Eliminar registro",
            text: "¿Estás seguro(a) que deseas ejecutar esta acción?",
            showCancelButton: true,
            confirmButtonText: "Aceptar",
            cancelButtonText: "Cancelar"
        });

        if (result.isConfirmed) {
            try {
                dialogs.fire({
                    title: "Procesando...",
                    text: "Por favor, espere",
                    allowOutsideClick: false,
                    didOpen: () => dialogs.showLoading()
                });

                // Ejecutar la solicitud de eliminación
                await axios.delete(`${config.endpoint}/${id}`);
                
                items.value = items.value.filter(item => item.id !== id);

                dialogs.close();
                dialogs.fire("Excelente!", "El registro ha sido eliminado correctamente.", "success");

            } catch (error) {
                dialogs.close();
                if (error.response && error.response.status === 400) {
                    errors.value = error.response.data;
                } else {
                    console.error("Error eliminando el recurso:", error);
                }
            }
        }
    };

    return {
        items,
        deleteItem,
        loadItems,
        loadFilteredItems
    };
}