import axios from 'axios';

const endpoint = 'maintenance-new/purchase-orders';

//Listado de ordenes de compra
export async function getPurchaseOrdersApi(filters = {}) {
    const response = await axios.get(endpoint, { params: filters });

    return response.data;
}

//Detalle de orden de compra 
export async function getPurchaseOrderDetailApi(id) {
    const response = await axios.get(`${endpoint}/${id}`);

    return response.data;
}

//Alta de orden de compra
export async function createPurchaseOrderApi(data) {
    const response = await axios.post(endpoint, data);

    return response.data;
}

//Actualizar orden de compra
export async function updatePurchaseOrderApi(id, data) {
    data.append('_method', 'PUT');
    const response = await axios.post(`${endpoint}/${id}`, data);

    return response.data;
}
