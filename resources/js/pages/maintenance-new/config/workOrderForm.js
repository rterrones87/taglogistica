export const workOrderCategories = [
    'Tractor',
    'Remolque',
    'Dolly',
    'Plataforma',
    'Caja Refrigerada',
    'Gastos de accidentes',
    'Gastos de gruas',
    'Mala operacion del operador',
    'Rescate carretero',
];

export const vehicleCategories = workOrderCategories.slice(0, 5);

export const purchaseOrderColumns = [
    { key: 'folio', label: 'OC', sortable: true, filterable: true },
    { key: 'supplier.name', label: 'Proveedor', filterable: true },
    { key: 'description', label: 'Descripcion', filterable: true },
    {
        key: 'cost',
        label: 'Costo con IVA',
        sortable: true,
        formatter: formatCurrency,
    },
    { key: 'status', label: 'Estado', sortable: true, filterable: true },
];

export function itemWorkOrder() {
    return {
        unit_category: '',
        maintenance_type: '',
        unit_id: '',
        initial_mileage: 1,
        opened_at: new Date().toISOString().slice(0, 10),
        operator_id: '',
        mechanic_id: '',
        failure_description: '',
        work_type: '',
    };
}

export function workshopCatalogs() {
    return {
        units: [],
        operators: [],
        mechanics: [],
        suppliers: [],
    };
}

export function formatCurrency(value) {
    return Number(value || 0).toLocaleString('es-MX', {
        style: 'currency',
        currency: 'MXN',
    });
}
