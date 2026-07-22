<template>
    <breadcrumb :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 shadow-md">

        <h2 class="text-3xl my-4 font-bold text-center md:text-left">Generar orden de pago</h2>
        <div v-if="item.services.length > 0 || item.bonuses?.length > 0" class="mb-2">
            <b>Operador:</b> {{ item.services[0]?.operator || item.bonuses?.[0]?.operator_role }}  <br/>
            <b>Fecha:</b> {{ item.services[0]?.payment_date }} <br/>
        </div>
        <form @submit.prevent="savePayment">

            <!-- ─── TABLA PRINCIPAL: Viajes de flete ─────────────────────── -->
            <div class="mb-4 flex items-center gap-2" v-if="item.services.length > 0">
                <input 
                    type="checkbox" 
                    v-model="selectAll"
                    @change="toggleSelectAll"
                    class="w-5 h-5"
                />
                <label class="font-semibold">Seleccionar todos los viajes de flete</label>
            </div>

            <h3 v-if="item.services.length > 0" class="text-lg font-bold mb-2">Viajes de Flete</h3>
            <table class="table mb-8" v-if="item.services.length > 0">
                <thead>
                    <tr>
                        <th>Seleccionar</th>
                        <th>Folio</th>
                        <th>Carta porte</th>
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
                        <td data-label="Carta porte">{{ payment.waybill }}</td>
                        <td data-label="Tipo">{{ getOperationTypeTitle(payment.type_operation) }}</td>
                        <td data-label="Cliente">{{ payment.client }}</td>
                        <td>{{ getAllDestine(payment) }}</td>
                        <td data-label="Fecha">{{ payment.delivery_date }}</td>
                        <td>
                            <div class="form-item">
                                <CurrencyInput
                                    v-model="payment.amount"
                                    :min="0"
                                    :disabled="true"
                                    placeholder="0.00"
                                />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- ─── TABLA DE BONOS: Participaciones como operador auxiliar ── -->
            <template v-if="item.bonuses && item.bonuses.length > 0">
                <div class="mb-4 flex items-center gap-2">
                    <input
                        type="checkbox"
                        v-model="selectAllBonuses"
                        @change="toggleSelectAllBonuses"
                        class="w-5 h-5"
                    />
                    <label class="font-semibold">Seleccionar todos los bonos</label>
                </div>

                <h3 class="text-lg font-bold mb-2">Bonos por Participación en Viajes</h3>
                <table class="table mb-8">
                    <thead>
                        <tr>
                            <th>Seleccionar</th>
                            <th>Folio</th>
                            <th>Carta porte</th>
                            <th>Tipo de operación</th>
                            <th>Rol</th>
                            <th>Cliente</th>
                            <th>Destinos</th>
                            <th>Fecha</th>
                            <th>Bono</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="bonus in item.bonuses" :key="bonus.service_operator_id">
                            <td data-label="Seleccionar">
                                <input
                                    type="checkbox"
                                    v-model="bonus.selected"
                                    class="w-5 h-5"
                                />
                            </td>
                            <td data-label="Folio">{{ bonus.folio }}</td>
                            <td data-label="Carta porte">{{ bonus.waybill }}</td>
                            <td data-label="Tipo">{{ getOperationTypeTitle(bonus.type_operation) }}</td>
                            <td data-label="Rol" class="font-semibold text-[#18364a]">{{ bonus.operator_role }}</td>
                            <td data-label="Cliente">{{ bonus.client }}</td>
                            <td>{{ getAllDestine(bonus) }}</td>
                            <td data-label="Fecha">{{ bonus.delivery_date }}</td>
                            <td>
                                <div class="form-item">
                                    <CurrencyInput
                                        v-model="bonus.amount"
                                        :min="0"
                                        :disabled="true"
                                        placeholder="0.00"
                                    />
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </template>

            <!-- ─── DESCUENTOS ─────────────────────────────────────────────── -->
            <discountrequest v-model:rows="item.discounts" />

            <!-- ─── RESUMEN DE TOTALES ─────────────────────────────────────── -->
            <div class="mt-6 mb-4 p-4 bg-gray-50 rounded border text-sm">
                <div class="flex justify-between mb-1">
                    <span>Viajes de flete seleccionados:</span>
                    <span class="font-semibold">${{ totalFlete.toFixed(2) }}</span>
                </div>
                <div class="flex justify-between mb-1" v-if="item.bonuses?.length > 0">
                    <span>Bonos seleccionados:</span>
                    <span class="font-semibold">${{ totalBonos.toFixed(2) }}</span>
                </div>
                <div class="flex justify-between mb-1 text-red-600" v-if="totalDescuentos > 0">
                    <span>Descuentos:</span>
                    <span class="font-semibold">-${{ totalDescuentos.toFixed(2) }}</span>
                </div>
                <div class="flex justify-between border-t pt-2 text-base font-bold">
                    <span>Total a pagar:</span>
                    <span>${{ totalFinal.toFixed(2) }}</span>
                </div>
            </div>

            <div class="flex justify-end border-t mt-4 py-2">
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
    import CurrencyInput from '@/components/CurrencyInput.vue';

    const dialogs = inject("swal");

    const { item, isEditing, errors, saveItem } = upsert({
        endpoint: 'services/weekly-payments/operator',
        data: { services: [], bonuses: [], discounts: [] },
        dialogs,
        redirectOnCreate: 'operators/payments'
    });

    // ─── Inicializar propiedad selected en servicios y bonos al cargar
    watch(() => item.value.services, (newServices) => {
        if (newServices && newServices.length > 0) {
            newServices.forEach(s => { if (s.selected === undefined) s.selected = false; });
        }
    }, { immediate: true, deep: true });

    watch(() => item.value.bonuses, (newBonuses) => {
        if (newBonuses && newBonuses.length > 0) {
            newBonuses.forEach(b => { if (b.selected === undefined) b.selected = false; });
        }
    }, { immediate: true, deep: true });

    // ─── Checkbox "Seleccionar todos" (servicios de flete)
    const selectAll = ref(false);
    const toggleSelectAll = () => {
        item.value.services.forEach(s => { s.selected = selectAll.value; });
    };

    // ─── Checkbox "Seleccionar todos" (bonos)
    const selectAllBonuses = ref(false);
    const toggleSelectAllBonuses = () => {
        item.value.bonuses?.forEach(b => { b.selected = selectAllBonuses.value; });
    };

    // ─── Totales computados
    const totalFlete = computed(() =>
        (item.value.services || [])
            .filter(s => s.selected)
            .reduce((sum, s) => sum + (parseFloat(s.amount) || 0), 0)
    );

    const totalBonos = computed(() =>
        (item.value.bonuses || [])
            .filter(b => b.selected)
            .reduce((sum, b) => sum + (parseFloat(b.amount) || 0), 0)
    );

    const totalDescuentos = computed(() =>
        (item.value.discounts || [])
            .reduce((sum, d) => sum + (parseFloat(d.amount) || 0), 0)
    );

    const totalFinal = computed(() =>
        totalFlete.value + totalBonos.value - totalDescuentos.value
    );

    // ─── Guardar: solo servicios/bonos seleccionados
    const savePayment = async () => {
        const selectedServices = (item.value.services || []).filter(s => s.selected);
        const selectedBonuses  = (item.value.bonuses  || []).filter(b => b.selected);

        if (selectedServices.length === 0 && selectedBonuses.length === 0) {
            dialogs.fire('Atención', 'Debes seleccionar al menos un viaje o bono para pagar.', 'warning');
            return;
        }

        const invalidServices = selectedServices.filter(s => !s.amount || parseFloat(s.amount) <= 0);
        if (invalidServices.length > 0) {
            dialogs.fire('Atención', 'Los viajes seleccionados deben tener una cantidad mayor a 0.', 'warning');
            return;
        }

        const allServices = [...(item.value.services || [])];
        const allBonuses  = [...(item.value.bonuses  || [])];

        item.value.services = selectedServices;
        item.value.bonuses  = selectedBonuses;

        try {
            await saveItem();
        } catch (error) {
            item.value.services = allServices;
            item.value.bonuses  = allBonuses;
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
  