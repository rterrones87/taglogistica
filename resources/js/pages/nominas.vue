<template>
    <breadcrumb :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 shadow-md">

        <div class="flex items-center">
            <h2 class="text-3xl my-4 font-bold grow text-center md:text-left">Mis nominas</h2>

        </div>

        <router-link class="float-button block md:hidden" :to="{ path: 'travel' }"></router-link>

        <!-- Tabla para mostrar los datos -->
        <table class="table" v-if="items.length > 0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Folio</th>
                    <th>Operador</th>
                    <th>Semana a pagar</th>
                    <th>Total</th>
                    <th>Fecha de pago</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="order in items" :key="order.id">
                    <td data-label="ID">{{ order.id }}</td>
                    <td data-label="Folio">{{ order.folio  }}</td>
                    <td data-label="Operador">{{ order.operator.name  }}</td>
                    <td data-label="Semana a pagar">{{ order.order_date  }}</td>
                    <td data-label="Total">${{ order.total }}</td>
                    <td data-label="Fecha de pago">{{ order.payment_date  }}</td>
                    <td>
                        <div class="flex justify-center flex-col md:flex-row">
                            <TableAction
                                title="Comprobante"
                                icon="request.png"
                                @click="downloadDocument(`nomina-${order.order_date}`,`treasury/payments/pdf/${order.id}`)"
                            />
                        </div>
                    </td>
                 </tr>
            </tbody>
         </table>

        <!-- Mensaje si no hay nominas -->
        <p class="text-center block py-8" v-else>No hay nominas.</p>

        <PdfGenerator 
            ref="pdfGen" 
            :title="pdfDoc"
         >
            <template #pdf="{ data }">
                <PdfNomina 
                    :data="data"
                    :item="item"
                />
            </template>
        </PdfGenerator>
    </div>
</template>


<script setup>
import { inject, ref, onMounted } from 'vue';
import { actionslist } from '../composables/actionslist';
import breadcrumb from '../components/breadcrumb.vue';
import TableAction from '@/components/TableAction.vue';
import PdfGenerator from '@/components/PdfGenerator.vue';
import PdfNomina from '@/pages/formats/PdfNomina.vue';

const pdfGen = ref(null)
const pdfDoc = ref('')

const downloadDocument = (doc, url) => {
    pdfDoc.value = doc
    pdfGen.value.generate(url)
}

const dialogs = inject("swal");

const { items, deleteItem, loadItems } = actionslist({
    endpoint: `operator/${localStorage.getItem('user_id')}/payments`,
    dialogs
});

onMounted(() => {
    loadItems();
});

// Definir los elementos del breadcrumb
const breadcrumbItems = [
    { title: 'Mis nominas' } // Último elemento sin path porque es la página actual
];

const getDocument = async (id) => {
	
}

</script>
