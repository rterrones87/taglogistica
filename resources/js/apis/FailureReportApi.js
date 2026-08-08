import axios from 'axios';

const endpoint = 'maintenance-new/failure-reports';

export async function getFailureReportsApi(filters = {}) {
    const response = await axios.get(endpoint, { params: filters });

    return response.data;
}

export async function getFailureReportDetailApi(id) {
    const response = await axios.get(`${endpoint}/${id}`);

    return response.data;
}

export async function createFailureReportApi(data) {
    const response = await axios.post(endpoint, data);

    return response.data;
}

export async function updateFailureReportApi(id, data) {
    const response = await axios.put(`${endpoint}/${id}`, data);

    return response.data;
}

export async function startFailureReportApi(id) {
    const response = await axios.post(`${endpoint}/${id}/start`);

    return response.data;
}

export async function finishFailureReportApi(id) {
    const response = await axios.post(`${endpoint}/${id}/finish`);

    return response.data;
}
