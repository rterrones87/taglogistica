<script setup>
	import logo from '@/assets/logo.vue';
	import { numeroATexto } from '@/plugins/numeroATexto.js';

    const props = defineProps({
        data: {
            type: Object,
            required: true
        }
    })

    const getAllDestine = (item) => {
	    if (!item || !item.containers || !Array.isArray(item.containers)) {
	        return '';
	    }

	    return item.containers
	        .map(container => container.place.name)
	        .join(' // ');
	    
	};

	

</script>
<template>
	<div class="pdf-content">
		<table class="pdf-table-data">
			<tr>
				<td colspan="2"><h2 class="text-2xl font-bold">RECIBO DE PAGO</h2></td>
				<td colspan="2">
					<div class="flex items-center">
						<div class="grow"><b>Fecha</b> {{ data.payment_date }}</div>
						<logo/>
					</div>
				</td>
			</tr>
			<tr>
				<td width="120px"><b>Recibi de:</b></td>
				<td class="bottom-line">Gilberto Raul Arroyo Diaz</td>
				<td width="120px"><b>Cantidad</b></td>
				<td class="bottom-line"><b>${{ data.total }}</b></td>
			</tr>
			<tr>
				<td width="120px"><b>Cantidad</b></td>
				<td colspan="3" class="bottom-line uppercase">{{ numeroATexto(data.total) }}</td>
			</tr>
			<tr>
				<td width="120px"><b>Concepto</b></td>
				<td colspan="3" class="bottom-line text-sm"><b>PAGO DE VIAJES</b></td>
			</tr>
			<tr v-for="payment in data.payments" :key="payment.id">
				<td width="120px"></td>
				<td colspan="2" class="bottom-line">
					{{ payment.service.delivery_date.substring(0, 10) }} // {{ getAllDestine(payment.service) }} // {{ payment.service.cost.waybill }} // {{ payment.operator_type?.name ?? 'N/A' }}
				</td>
				<td class="bottom-line">${{payment.total}}</td>
			</tr>

			<tr>
				<td width="120px"><b>Concepto</b></td>
				<td colspan="3" class="bottom-line text-sm"><b>DESCUENTOS</b></td>
			</tr>
			<tr v-for="discount in data.discounts" :key="discount.id">
				<td width="120px"></td>
				<td colspan="2" class="bottom-line">{{discount.title}}</td>
				<td class="bottom-line">${{discount.total}}</td>
			</tr>

			<tr>
				<td width="120px"><b>Recibido por:</b></td>
				<td class="bottom-line" colspan="2"></td>
				<td>&nbsp;&nbsp; [ &nbsp; ] &nbsp;&nbsp; Cheque No.</td>
			</tr>
			<tr>
				<td width="120px"></td>
				<td colspan="2"><center><b>{{data.operator? data.operator.name : ''}}</b></center></td>
				<td>&nbsp;&nbsp; [ &nbsp; ] &nbsp;&nbsp; Transferencia</td>
			</tr>
			<tr>
				<td colspan="4"></td>
			</tr>
		</table>
	</div>
</template>
<style scoped>
	.pdf-content {
        font-family: Arial, sans-serif;
		padding: 24mm 12mm 4mm 12mm;
		font-size: 0.625rem;
        box-sizing: border-box;
        margin: 0;
        word-wrap: break-word;
        overflow-wrap: break-word;
	}	

	.pdf-table-data {
        table-layout: fixed;
       	width: 100%;
        border: 2px solid #000;
    }     
    
    .pdf-table-data td, 
    .pdf-table-data th {
        word-break: break-word;
        white-space: normal !important;
    }     
    
    .pdf-table-data td {
        padding: 0.25rem 0.5rem;
        vertical-align: middle;
        font-weight: 400;
    }

    .bottom-line {
    	border-bottom: 1px solid #000;
    }
    /*tengo que olvidar - rola vieja tipo roberto carlod*/
</style>