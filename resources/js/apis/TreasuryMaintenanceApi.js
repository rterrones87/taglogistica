import axios from 'axios';

//Listado de tesoreria mantenimiento (ordenes de compra)
export async function getTreasuryMaintenancesApi(filters = {}) {
    const response = await axios.get('treasury/maintenances', { params: filters });

    return response.data;
}

//Detalle de tesoreria mantenimiento (ordenes de compra)
export async function getTreasuryMaintenanceDetailApi(id) {
    const response = await axios.get(`treasury/maintenances/${id}`);

    return response.data;
}

//Pagar tesoreria mantenimiento (ordenes de compra)
export async function payTreasuryMaintenanceApi(id) {
    const response = await axios.post(`treasury/maintenances/${id}/pay`);

    return response.data;
}
