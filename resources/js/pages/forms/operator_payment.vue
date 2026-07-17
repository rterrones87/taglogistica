<template>
    <breadcrumb :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 shadow-md">

        <h2 class="text-3xl my-4 font-bold text-center md:text-left">Generar orden de pago</h2>
        <div v-if="item.services.length > 0" class="mb-2">
            <b>Operador:</b> {{ item.services[0].operator }}  <br/>
            <b>Fecha:</b> {{ item.services[0].payment_date }} <br/>
        </div>
        <form @submit.prevent="savePayment">
            <!-- Checkbox para seleccionar todos -->
            <div class="mb-4 flex items-center gap-2" v-if="item.services.length > 0">
                <input 
                    type="checkbox" 
                    v-model="selectAll"
                    @change="toggleSelectAll"
                    class="w-5 h-5"
                />
                <label class="font-semibold">Seleccionar todos los viajes</label>
            </div>

            <!-- Tabla para mostrar los datos - -->
            <table class="table mb-8" v-if="item.services.length > 0">
                <thead>
                    <tr>
                        <th>Seleccionar</th>
                        <th>Folio</th>
                        <th>Tipo de operación</th>
                        <th>Cliente</th>
                        <th>Destinos</th>
                        <th>Fecha</th>
                        <th>Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="payment in item.services" :key="payment.id">
                        <td data-label="Seleccionar">
                            <input 
                                type="checkbox" 
                                v-model="payment.selected"
                                class="w-5 h-5"
                            />
                        </td>
                        <td data-label="Folio">{{ payment.folio }}</td>
                        <td data-label="Nombre operador">{{ getOperationTypeTitle(payment.type_operation) }}</td>
                        <td data-label="No. de Viajes">{{ payment.client }}</td>
                        <td>{{ getAllDestine(payment) }}</td>
                        <td data-label="Fecha">{{ payment.delivery_date }}</td>
                        <td>
                            <div class="form-item">
                                <input 
                                    type="text" 
                                    v-model="payment.amount"
                                    :disabled="!payment.selected"
                                />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <discountrequest
                v-model:rows="item.discounts"
            />


            <div class="flex justify-end border-t mt-8 py-2">
                <button class="form-button" type="submit">Guardar</button>
            </div>
        </form>
    </div>
</template>
  
<script setup>
    import { inject, ref, computed, watch } from "vue";
    import { upsert } from '../../composables/upsert';
    import breadcrumb from '../../components/breadcrumb.vue';
    import discountrequest from '../../components/discountrequest.vue';

    const dialogs = inject("swal");

    const { item, isEditing, errors, saveItem } = upsert({
        endpoint: 'services/weekly-payments/operator',
        data: { services:[], discounts:[] },
        dialogs,
        redirectOnCreate: 'operators/payments'
    });

    // Agregar propiedad selected a cada servicio cuando se cargan
    watch(() => item.value.services, (newServices) => {
        if (newServices && newServices.length > 0) {
            newServices.forEach(service => {
                if (service.selected === undefined) {
                    service.selected = false;
                }
            });
        }
    }, { immediate: true, deep: true });

    // Checkbox "Seleccionar todos"
    const selectAll = ref(false);

    const toggleSelectAll = () => {
        item.value.services.forEach(service => {
            service.selected = selectAll.value;
        });
    };

    // Computeds para resumen
    const selectedCount = computed(() => {
        return item.value.services.filter(s => s.selected).length;
    });

    const totalToPay = computed(() => {
        return item.value.services
            .filter(s => s.selected)
            .reduce((sum, s) => sum + (parseFloat(s.amount) || 0), 0);
    });

    // Método personalizado para guardar solo servicios seleccionados
    const savePayment = async () => {
        // Filtrar solo los servicios seleccionados
        const selectedServices = item.value.services.filter(service => service.selected);
        
        // Validar que al menos uno esté seleccionado
        if (selectedServices.length === 0) {
            dialogs.fire("Atención", "Debes seleccionar al menos un viaje para pagar.", "warning");
            return;
        }
        
        // Validar que los seleccionados tengan monto
        const invalidServices = selectedServices.filter(s => !s.amount || parseFloat(s.amount) <= 0);
        if (invalidServices.length > 0) {
            dialogs.fire("Atención", "Los viajes seleccionados deben tener una cantidad mayor a 0.", "warning");
            return;
        }
        
        // Guardar referencia de todos los servicios
        const allServices = [...item.value.services];
        
        // Temporalmente reemplazar con solo los seleccionados
        item.value.services = selectedServices;
        
        try {
            // Llamar al saveItem del composable
            await saveItem();
            
            // Si llega aquí, el guardado fue exitoso
            // El composable ya maneja el redirect y los mensajes
            
        } catch (error) {
            // En caso de error, restaurar todos los servicios
            item.value.services = allServices;
            // El composable ya maneja el error
        }
    };

    // Definir los elementos del breadcrumb
    const breadcrumbItems = [
        { title: 'Pago Operadores', path: '/panel/operators/payments' },
        { title: 'Administrar Pago', path: ''} 
    ];

    const getOperationTypeTitle = (id) => {
        let list = {1 : 'Importación', 2 : 'Exportación', 3 : 'Carga Suelta'};
        return list[id];
    };

    const getAllDestine = (item) => {
        if (!item || !item.destines || !Array.isArray(item.destines)) {
            return '';
        }

        return item.destines
            .map(destine => destine.name)
            .join(', ');
    };
</script>
  