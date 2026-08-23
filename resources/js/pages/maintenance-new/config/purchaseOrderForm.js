export const MAX_EVIDENCE_FILES = 5;

export function itemPurchaseOrder(workOrderId = '') {
    return {
        work_order_id: workOrderId ? Number(workOrderId) : '',
        supplier_id: '',
        description: '',
        cost: '',
        payment_condition: null,
        credit_days: null,
    };
}

export function purchaseOrderCatalogs() {
    return {
        work_orders: [],
        suppliers: [],
    };
}
