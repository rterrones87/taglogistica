export const workOrderBreadcrumbItems = [
    { title: 'Mantenimientos' },
    { title: 'Ordenes de trabajo' },
];

export const workOrderColumns = [
    { key: 'folio', label: 'Folio', sortable: true, filterable: true },
    { key: 'unit.econame', label: 'Unidad', sortable: true, filterable: true },
    {
        key: 'unit_category', label: 'Tipo', sortable: true, filterable: true,
        formatter: (value, row) => row.maintenance_type ? `${value} / ${row.maintenance_type}` : value,
    },
    { key: 'status', label: 'Estado', sortable: true, filterable: true },
    {
        key: 'responsible', label: 'Responsable', sortable: true, filterable: true,
        cellClass: 'whitespace-pre-line',
    },
    { key: 'purchase_orders_count', label: 'OC aprobadas', sortable: true },
    {
        key: 'total_cost', label: 'Costo aprobado', sortable: true,
        formatter: (value) => Number(value || 0).toLocaleString('es-MX', {
            style: 'currency', currency: 'MXN',
        }),
    },
];
