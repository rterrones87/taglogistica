import axios from 'axios';

const endpoint = 'maintenance-new/work-orders';

//Listado de ordenes de trabajo
export async function getWorkOrdersApi(filters = {}) {
    const response = await axios.get(endpoint, { params: filters });

    return response.data;
}

//Detalle de orden de trabajo
export async function getWorkOrderDetailApi(id) {
    const response = await axios.get(`${endpoint}/${id}`);

    return response.data;
}

//Alta de ordenes de trabajo
export async function createWorkOrderApi(data) {
    const response = await axios.post(endpoint, data);

    return response.data;
}

//Actualizar ordenes de trabajo
export async function updateWorkOrderApi(id, data) {
    const response = await axios.put(`${endpoint}/${id}`, data);

    return response.data;
}

//Cambiar estado de la orden: 1 iniciar, 2 finalizar
export async function changeWorkOrderStatusApi(id, status) {
    const response = await axios.post(`${endpoint}/${id}/status`, { status });

    return response.data;
}
