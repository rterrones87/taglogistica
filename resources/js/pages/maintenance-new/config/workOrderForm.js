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

/**
 * @typedef {Object} WorkOrderForm
 * @property {string} unit_category
 * @property {string} maintenance_type
 * @property {number|string} unit_id
 * @property {number} initial_mileage
 * @property {string} opened_at
 * @property {number|string} operator_id
 * @property {number|string} mechanic_id
 * @property {string} failure_description
 * @property {string} work_type
 */

/**
 * Crea un estado independiente para el formulario de orden de trabajo.
 *
 * @returns {WorkOrderForm}
 */
export function createWorkOrderForm() {
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

/**
 * Crea la estructura inicial de los catalogos del formulario.
 *
 * @returns {{units: Array, operators: Array, mechanics: Array, suppliers: Array}}
 */
export function createWorkshopCatalogs() {
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
