import axios from 'axios';

const endpoint = 'maintenance-new/purchase-orders';

export async function getPurchaseOrdersApi(filters = {}) {
    const response = await axios.get(endpoint, { params: filters });

    return response.data;
}

export async function getPurchaseOrderDetailApi(id) {
    const response = await axios.get(`${endpoint}/${id}`);

    return response.data;
}

export async function createPurchaseOrderApi(data) {
    const response = await axios.post(endpoint, data);

    return response.data;
}

export async function updatePurchaseOrderApi(id, data) {
    const response = await axios.post(`${endpoint}/${id}`, data);

    return response.data;
}

export async function closePurchaseOrderApi(id) {
    const response = await axios.post(`${endpoint}/${id}/close`);

    return response.data;
}
