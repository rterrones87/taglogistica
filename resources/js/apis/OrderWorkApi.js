import axios from 'axios';

const endpoint = 'maintenance-new/work-orders';

export async function getWorkOrdersApi(filters = {}) {
    const response = await axios.get(endpoint, { params: filters });

    return response.data;
}

export async function getWorkOrderDetailApi(id) {
    const response = await axios.get(`${endpoint}/${id}`);

    return response.data;
}

export async function createWorkOrderApi(data) {
    const response = await axios.post(endpoint, data);

    return response.data;
}

export async function updateWorkOrderApi(id, data) {
    const response = await axios.put(`${endpoint}/${id}`, data);

    return response.data;
}

export async function startWorkOrderApi(id) {
    const response = await axios.post(`${endpoint}/${id}/start`);

    return response.data;
}

export async function closeWorkOrderApi(id) {
    const response = await axios.post(`${endpoint}/${id}/close`);

    return response.data;
}
