export const MAX_EVIDENCE_FILES = 5;

/**
 * @typedef {Object} PurchaseOrderForm
 * @property {number|string} work_order_id
 * @property {number|string} supplier_id
 * @property {string} description
 * @property {number|string} cost
 * @property {string|null} payment_condition
 * @property {number|null} credit_days
 */

/**
 * Crea un estado independiente para el formulario de orden de compra.
 *
 * @param {number|string} workOrderId
 * @returns {PurchaseOrderForm}
 */
export function createPurchaseOrderForm(workOrderId = '') {
    return {
        work_order_id: workOrderId ? Number(workOrderId) : '',
        supplier_id: '',
        description: '',
        cost: '',
        payment_condition: null,
        credit_days: null,
    };
}

/**
 * Crea la estructura inicial de los catalogos del formulario.
 *
 * @returns {{work_orders: Array, suppliers: Array}}
 */
export function createPurchaseOrderCatalogs() {
    return {
        work_orders: [],
        suppliers: [],
    };
}
