import { jsPDF } from 'jspdf';

export const treasuryMaintenanceBreadcrumbItems = [
    { title: 'Tesorería' },
    { title: 'Mantenimientos' },
];

export const treasuryMaintenanceTabs = [
    { id: 'pending', label: 'Pendientes' },
    { id: 'paid', label: 'Pagados' },
    { id: 'all', label: 'Todos' },
];

export const treasuryMaintenanceColumns = [
    { key: 'purchase_order.folio', label: 'OC', filterable: true },
    { key: 'purchase_order.work_order.folio', label: 'OT', filterable: true },
    { key: 'purchase_order.work_order.unit.econame', label: 'Unidad', filterable: true },
    { key: 'purchase_order.supplier.name', label: 'Proveedor', filterable: true },
    { key: 'purchase_order.cost', label: 'Costo con IVA', formatter: formatCurrency },
    { key: 'status', label: 'Estado', filterable: true },
    { key: 'created_at', label: 'Fecha de aprobación', formatter: formatDate },
];

export function downloadTreasuryMaintenancesPdf(items, currentTab) {
    const document = new jsPDF({ orientation: 'landscape' });
    let y = 18;

    document.setFontSize(16);
    document.text('Tesorería - Mantenimientos', 14, y);
    y += 8;
    document.setFontSize(9);
    document.text(`Pestaña: ${treasuryMaintenanceTabs.find((tab) => tab.id === currentTab)?.label}`, 14, y);
    y += 8;
    document.setFont(undefined, 'bold');
    document.text('OC', 14, y);
    document.text('OT', 45, y);
    document.text('Unidad', 76, y);
    document.text('Proveedor', 118, y);
    document.text('Costo con IVA', 190, y);
    document.text('Estado', 232, y);
    document.text('Fecha', 260, y);
    document.setFont(undefined, 'normal');
    y += 6;

    items.forEach((item) => {
        if (y > 190) {
            document.addPage();
            y = 16;
        }

        const order = item.purchase_order || {};
        document.text(String(order.folio || ''), 14, y);
        document.text(String(order.work_order?.folio || ''), 45, y);
        document.text(truncate(order.work_order?.unit?.econame, 20), 76, y);
        document.text(truncate(order.supplier?.name, 32), 118, y);
        document.text(formatCurrency(order.cost), 190, y);
        document.text(item.status, 232, y);
        document.text(formatDate(item.created_at), 260, y);
        y += 6;
    });

    document.save('tesoreria-mantenimientos.pdf');
}

export function paymentCondition(order) {
    if (!order?.payment_condition) return 'Por confirmar';

    return order.payment_condition === 'Credito' && order.credit_days
        ? `Crédito (${order.credit_days} días)`
        : order.payment_condition;
}

export function formatCurrency(value) {
    return Number(value || 0).toLocaleString('es-MX', {
        style: 'currency', currency: 'MXN',
    });
}

export function formatDate(value) {
    if (!value) return 'N/A';

    return new Intl.DateTimeFormat('es-MX', {
        year: 'numeric', month: '2-digit', day: '2-digit',
    }).format(new Date(value));
}

function truncate(value, length) {
    const text = String(value || 'N/A');

    return text.length > length ? `${text.slice(0, length - 3)}...` : text;
}
