# 💰 Módulo de Tesorería

[← Volver al índice](context.md)

---

## 📋 Descripción General

El módulo de tesorería gestiona todas las órdenes de pago del sistema, incluyendo pagos por servicios, mantenimientos y nóminas de operadores. Permite aplicar pagos, cargar evidencias y generar reportes.

---

## 🗂️ Modelos

### 1. TreasuryService

**Ubicación:** `app/Models/TreasuryService.php`

#### Campos de la Tabla `treasury_services`

| Campo | Tipo | Descripción | Requerido |
|-------|------|-------------|-----------|
| `id` | BIGINT | ID único | Auto |
| `service_id` | BIGINT | ID del servicio | ✅ |
| `user_id` | BIGINT | ID de quien crea la orden (solicitante) | ✅ |
| `reviewed_by` | BIGINT | ID del usuario que aprobó la solicitud | ❌ |
| `order_date` | DATE | Fecha de la orden | ✅ |
| `total` | DECIMAL(10,2) | Monto total | ✅ |
| `type_payment` | INTEGER | Tipo de pago (1=inicial, 2=extra) | ✅ |
| `paid` | BOOLEAN | 0=pendiente, 1=pagado | ❌ |
| `payment_date` | DATE | Fecha en que se aplicó el pago | ❌ |
| `zombie` | TINYINT | 0=activo, 1=eliminado | ❌ |
| `created_at` | TIMESTAMP | Fecha de creación | Auto |
| `updated_at` | TIMESTAMP | Fecha de actualización | Auto |

#### Relaciones
```php
public function service()  { return $this->belongsTo(Service::class); }
public function user()     { return $this->belongsTo(User::class); }
public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }
```

### 2. TreasuryMaintenance

**Ubicación:** `app/Models/TreasuryMaintenance.php`

#### Campos de la Tabla `treasury_maintenances`

| Campo | Tipo | Descripción | Requerido |
|-------|------|-------------|-----------|
| `id` | BIGINT | ID único | Auto |
| `maintenance_id` | BIGINT | ID del mantenimiento | ✅ |
| `user_id` | BIGINT | ID de quien crea la orden (solicitante) | ✅ |
| `reviewed_by` | BIGINT | ID del usuario que aprobó la solicitud | ❌ |
| `order_date` | DATE | Fecha de la orden | ✅ |
| `total` | DECIMAL(10,2) | Monto total | ✅ |
| `description` | TEXT | Formato: `"{PROVEEDOR} - N productos, N servicios"` | ❌ |
| `paid` | BOOLEAN | 0=pendiente, 1=pagado | ❌ |
| `payment_date` | DATE | Fecha en que se aplicó el pago | ❌ |
| `zombie` | TINYINT | 0=activo, 1=eliminado | ❌ |
| `created_at` | TIMESTAMP | Fecha de creación | Auto |
| `updated_at` | TIMESTAMP | Fecha de actualización | Auto |

#### Relaciones
```php
public function maintenance() { return $this->belongsTo(Maintenance::class); }
public function user()        { return $this->belongsTo(User::class); }
public function reviewer()    { return $this->belongsTo(User::class, 'reviewed_by'); }
```

### 3. TreasuryPayment

**Ubicación:** `app/Models/TreasuryPayment.php`

#### Campos de la Tabla `treasury_payments`

| Campo | Tipo | Descripción | Requerido |
|-------|------|-------------|-----------|
| `id` | BIGINT | ID único | Auto |
| `folio` | VARCHAR(50) | Formato `TAG{YmdHis}` | ✅ |
| `operator_id` | BIGINT | ID del operador | ✅ |
| `user_id` | BIGINT | ID de quien registra | ✅ |
| `order_date` | DATE | Fecha del pago | ✅ |
| `total` | DECIMAL(10,2) | Total del pago | ✅ |
| `paid` | BOOLEAN | 0=pendiente, 1=pagado | ❌ |
| `payment_date` | DATE | Fecha en que se aplicó el pago | ❌ |
| `zombie` | TINYINT | 0=activo, 1=eliminado | ❌ |
| `created_at` | TIMESTAMP | Fecha de creación | Auto |
| `updated_at` | TIMESTAMP | Fecha de actualización | Auto |

#### Relaciones
```php
public function operator() { return $this->belongsTo(User::class, 'operator_id'); }
public function user()     { return $this->belongsTo(User::class); }
public function payments() { return $this->hasMany(Payment::class); }
public function discounts(){ return $this->hasMany(Discount::class); }
```

**Ver:** [modulo-operadores.md](modulo-operadores.md) para detalles del sistema de pagos.

---

## 🔌 API Endpoints

### Servicios de Tesorería

**Endpoint:** `GET /api/treasury/services`  
**Permiso:** `treasury.view_services`  
**Controlador:** `TreasuryController@services`

**Query Parameters:**
- `type` *(default: 1)* — `1`=gastos iniciales pendientes, `2`=gastos extras pendientes, `3`=historial (pagados)
- `htype` — cuando `type=3`, filtra por `type_payment` específico
- `start_date`, `end_date` — rango de fechas sobre `order_date` (YYYY-MM-DD, zona México)

**Respuesta:**
```json
[
  {
    "id": 1,
    "service_id": 42,
    "folio": "TAG260115001",
    "user": "JUAN LOGISTICA",
    "approver": "ADMIN GARCIA",
    "order_date": "2026-01-20",
    "operator": "PEDRO CHOFER",
    "total": "$5,000.00",
    "payment_date": null
  }
]
```

### Mantenimientos de Tesorería

**Endpoint:** `GET /api/treasury/maintenances`  
**Permiso:** `treasury.view_maintenances`  
**Controlador:** `TreasuryController@maintenances`

**Query Parameters:**
- `type` *(default: 1)* — ID del `type_maintenance_id` del mantenimiento, o `6`=historial (pagados)
- `htype` — cuando `type=6`, filtra por `type_maintenance_id` específico
- `start_date`, `end_date` — rango de fechas sobre `order_date`

**Respuesta:**
```json
[
  {
    "id": 1,
    "order_date": "2026-01-20",
    "total": "$12,500.00",
    "user": "JUAN LOGISTICA",
    "approver": "ADMIN GARCIA",
    "folio": "MTT20260115120000",
    "unit": "T-001",
    "description": "Descripción del mantenimiento",
    "supplier": "PROVEEDOR XYZ",
    "payment_date": null
  }
]
```

### Nóminas de Operadores

**Endpoint:** `GET /api/treasury/payments`  
**Permiso:** `treasury.view_payments`  
**Controlador:** `TreasuryController@payments`

**Query Parameters:**
- `type` *(default: 1)* — `1`=pendientes de pago, `2`=pagadas

**Respuesta:**
```json
[
  {
    "id": 1,
    "folio": "TAG20260120120000",
    "order_date": "2026-01-20",
    "total": 34500.00,
    "operator": "JUAN PEREZ GARCIA",
    "payment_date": null
  }
]
```

### Detalle de Nómina por ID

**Endpoint:** `GET /api/treasury/payments/details/{id}`  
**Permiso:** `treasury.view_payments`  
**Controlador:** `TreasuryController@payments_details`

**Respuesta:**
```json
{
  "payments": [
    {
      "id": 1,
      "folio": "TAG260115001",
      "type_operation": 1,
      "client": "CLIENTE XYZ",
      "operator": "PEDRO CHOFER",
      "operator_role": "Flete",
      "destines": [{"name": "GUADALAJARA, JAL"}],
      "amount": 3000.00
    }
  ],
  "discounts": [
    {
      "title": "Seguro Social",
      "total": 500.00
    }
  ]
}
```
`operator_role` es el nombre del tipo de operador (`service_operator_types.name`). Es `"N/A"` para registros anteriores a la migración que añadió `service_operator_type_id` a `payments`.

### Detalle de Orden de Mantenimiento

**Endpoint:** `GET /api/treasury/maintenances/details/{id}`  
**Permiso:** `treasury.view_maintenances`  
**Controlador:** `TreasuryController@maintenanceDetails`

**Descripción:** Detalle completo de una orden de mantenimiento: orden, datos del mantenimiento, solicitudes al proveedor y solicitudes de inventario.

**Respuesta:**
```json
{
  "treasury_order": {
    "id": 1,
    "total": 12500.00,
    "order_date": "2026-01-20",
    "supplier_name": "PROVEEDOR XYZ",
    "invoice_required": true
  },
  "maintenance": {
    "folio": "MTT20260115120000",
    "unit": "T-001",
    "type_maintenance": "Preventivo",
    "description": "...",
    "kms": 150000
  },
  "supplier_requests": [
    {"id": 1, "request_type": "product", "description": "Filtro", "quantity": 2, "cost": 500.00}
  ],
  "inventory_requests": [
    {"id": 1, "inventory": {"name": "Aceite"}, "quantity": 3}
  ]
}
```

### Aplicar Pago

**Endpoint:** `PUT /api/treasury/apply-payment`  
**Permiso:** `treasury.apply_payment`  
**Controlador:** `TreasuryController@applyPayment`

**Request Body:**
```json
{
  "type": "service",
  "id": 1
}
```

`type` puede ser: `"service"`, `"maintenance"` o `"payment"`.

**Comportamiento para los tres tipos:**
- Marca `paid = 1`
- Registra `payment_date = fecha actual`

**Response 200**

### PDF de Nómina

**Endpoint:** `GET /api/treasury/payments/pdf/{id}`  
**Permiso:** `treasury.view_payments`  
**Controlador:** `TreasuryController@payment_pdfhtml`

**Descripción:** Retorna el modelo `TreasuryPayment` serializado con sus relaciones para renderizar en `PdfNomina.vue`. Cada entrada en `payments` incluye la relación `operator_type` (nombre del tipo de rol del operador en ese viaje). El PDF muestra por viaje: fecha de entrega, destinos, carta porte y tipo de operador.

### Gastos Iniciales del Servicio

**Endpoint:** `GET /api/treasury/init/expenses/{id}`  
**Permiso:** `treasury.init_expenses`  
**Controlador:** `TreasuryController@initExpenses`

**Descripción:** Obtiene gastos iniciales del servicio (costos de casetas iniciales).

### Gastos Extras del Servicio

**Endpoint:** `GET /api/treasury/ext/expenses/{id}`  
**Permiso:** `treasury.ext_expenses`  
**Controlador:** `TreasuryController@extExpenses`

**Descripción:** Obtiene gastos extras del servicio (tipo 'EXTRAS').

### Upload de Evidencias

**Endpoint:** `POST /api/treasury/upload-photos/{id}`  
**Permiso:** `treasury.upload_evidence`  
**Controlador:** `TreasuryController@upload`

**Request:** Multipart/form-data, campo `photos[]` (jpg/jpeg/png, máx 5 MB por imagen)

**Descripción:** Sube evidencias fotográficas asociadas al servicio. Crea un registro en `evidences` por cada imagen con el campo `file_name` (nombre del archivo) y `service_id`.

### Descargas

**Endpoint:** `GET /api/download/treasury/services`  
**Permiso:** `treasury.view_services`  
**Controlador:** `TreasuryController@download`

**Descripción:** Exporta órdenes de servicios a Excel.

**Endpoint:** `GET /api/download/treasury/maintenances`  
**Permiso:** `treasury.view_maintenances`  
**Controlador:** `TreasuryController@downloadMaintenances`

**Descripción:** Exporta órdenes de mantenimientos a Excel.

---

## 🎨 Frontend - Vistas

### Servicios Pendientes: `treasury/services.vue`

**Ruta:** `/panel/treasury/services`  
**Ubicación:** `resources/js/pages/treasury/services.vue`  
**Permisos:** `treasury.view_services`

**Funcionalidades:**
- Listado de órdenes de pago de servicios
- Filtros por estado (pagado/pendiente)
- Búsqueda por folio
- Botón "Aplicar Pago" por fila
- Confirmación con SweetAlert2
- Exportar a Excel

**Endpoints Consumidos:**
- `GET /api/treasury/services`
- `PUT /api/treasury/apply-payment`
- `GET /api/download/treasury/services`

### Mantenimientos Pendientes: `treasury/maintenances.vue`

**Ruta:** `/panel/treasury/maintenances`  
**Ubicación:** `resources/js/pages/treasury/maintenances.vue`  
**Permisos:** `treasury.view_maintenances`

**Funcionalidades:**
- Listado de órdenes de pago de mantenimientos
- Filtros por estado (pagado/pendiente)
- Búsqueda por folio
- Botón "Aplicar Pago" por fila
- Confirmación con SweetAlert2
- Exportar a Excel

**Endpoints Consumidos:**
- `GET /api/treasury/maintenances`
- `PUT /api/treasury/apply-payment`
- `GET /api/download/treasury/maintenances`

### Nóminas: `treasury/nominas.vue`

**Ruta:** `/panel/treasury/nominas`  
**Ubicación:** `resources/js/pages/treasury/nominas.vue`  
**Permisos:** `treasury.view_payments`

**Funcionalidades:**
- Listado de pagos semanales a operadores
- Búsqueda por folio, operador
- Botón "Ver Detalle" por fila
- Botón "Descargar PDF" por fila
- Modal con detalle de pago (servicios + descuentos)

**Endpoints Consumidos:**
- `GET /api/treasury/payments`
- `GET /api/treasury/payments/details/{id}`
- `GET /api/treasury/payments/pdf/{id}`

### Formato PDF: `formats/PdfNomina.vue`

**Ubicación:** `resources/js/pages/formats/PdfNomina.vue`

**Descripción:** Componente que genera el layout HTML para exportar nóminas a PDF.

**Librerías usadas:**
- `vue3-html2pdf` - Conversión HTML a PDF
- `jspdf` - Generación de PDFs
- `html2canvas` - Renderizado HTML

---

## 💡 Flujo de Trabajo

### Aplicar Pago de Servicio

1. Usuario (Tesorería) accede a `/panel/treasury/services`
2. Ve listado de órdenes de pago pendientes
3. Clic en "Aplicar Pago" en una orden
4. Modal de confirmación con SweetAlert2
5. Confirma aplicación
6. `PUT /api/treasury/apply-payment` con `{type: "service", id: 1}`
7. Backend marca `paid = 1` en `treasury_services`
8. Orden desaparece del listado de pendientes
9. Mensaje de éxito

### Generar PDF de Nómina

1. Usuario accede a `/panel/treasury/nominas`
2. Ve listado de pagos semanales
3. Clic en "Descargar PDF" en un pago
4. `GET /api/treasury/payments/pdf/{id}` obtiene HTML
5. Componente PdfNomina.vue renderiza el HTML
6. `vue3-html2pdf` convierte a PDF
7. Descarga automática del archivo

### Creación de Órdenes (Automática)

Las órdenes de tesorería se crean automáticamente al:

#### Servicios:
- **Gastos iniciales** (`type_payment = 1`): al aprobar `initial_expenses` desde `approvals.vue`
- **Gastos extras** (`type_payment = 2`): al aprobar `extra_expenses` o `extra_booth`

#### Mantenimientos:
- Al aprobar `maintenance_expenses`; se crea una orden por cada proveedor agrupado

#### Nóminas:
- Al ejecutar `PUT /api/services/weekly-payments/operator/{id}` desde la vista de pagos de operadores

Ver: [modulo-aprobaciones.md](modulo-aprobaciones.md) para detalles de aprobaciones.

---

## 🔒 Seguridad

### Control de Acceso

**Roles con acceso:**
- Administrador (todos los permisos)
- Tesorería (aplicar pagos, ver órdenes)

**Permisos:**
- `treasury.view_services` - Ver órdenes de servicios
- `treasury.view_maintenances` - Ver órdenes de mantenimientos
- `treasury.view_payments` - Ver nóminas
- `treasury.apply_payment` - Aplicar pagos
- `treasury.init_expenses` - Ver gastos iniciales
- `treasury.ext_expenses` - Ver gastos extras
- `treasury.upload_evidence` - Subir evidencias

---

## 📊 Estadísticas y Reportes

### Exportaciones Disponibles

1. **Excel de Servicios**
   - Endpoint: `GET /api/download/treasury/services`
   - Incluye: Folio, Cliente, Fecha, Monto, Estado

2. **Excel de Mantenimientos**
   - Endpoint: `GET /api/download/treasury/maintenances`
   - Incluye: Folio, Unidad, Fecha, Monto, Descripción, Estado

3. **PDF de Nómina**
   - Endpoint: `GET /api/treasury/payments/pdf/{id}`
   - Incluye: Operador, Servicios (con tipo de operador por viaje), Descuentos, Total

### Métricas Sugeridas (No Implementadas)

1. Total pendiente de pago por tipo
2. Total pagado en el mes/año
3. Promedio de tiempo entre orden y pago
4. Top operadores por ingresos
5. Servicios vs Mantenimientos (proporción de pagos)
6. Dashboard financiero

---

## 🔗 Relaciones con Otros Módulos

### Con Servicios
- Órdenes de pago por gastos extras y casetas
- Ver: [modulo-servicios.md](modulo-servicios.md)

### Con Mantenimientos
- Órdenes de pago por solicitudes a proveedores
- Ver: [modulo-mantenimientos.md](modulo-mantenimientos.md)

### Con Operadores
- Pagos semanales (nóminas)
- Ver: [modulo-operadores.md](modulo-operadores.md)

### Con Aprobaciones
- Órdenes se crean al aprobar solicitudes
- Ver: [modulo-aprobaciones.md](modulo-aprobaciones.md)

---

## 📝 Notas de Implementación

### Consideraciones

1. **Campo `paid`** - Presente en los tres modelos (0=pendiente, 1=pagado)
2. **Campo `payment_date`** - Presente en los tres modelos; se asigna al aplicar el pago
3. **Campo `reviewed_by`** - Presente en `TreasuryService` y `TreasuryMaintenance`; es el ID del usuario que aprobó la solicitud
4. **Type Payment** - En `treasury_services`: 1=gastos iniciales, 2=gastos extras
5. **Folios Nómina** - Formato `TAG{YmdHis}` (ej. `TAG20260120120000`)
6. **Evidencias** - Se suben por `service_id`; se almacena `file_name` en la tabla `evidences`
7. **Soft delete** - Los tres modelos usan campo `zombie` (0=activo, 1=eliminado)

### Mejoras Sugeridas

1. Agregar campo `payment_method` (efectivo, transferencia, cheque)
2. Agregar campo `reference` (número de transferencia/cheque)
3. Implementar sistema de conciliación bancaria
4. Dashboard de tesorería con métricas
5. Alertas de órdenes vencidas
6. Proyección de flujo de efectivo
7. Integración con sistema contable
8. Historial de cambios en órdenes
9. Reportes personalizables por fecha/tipo/monto

---

**Última actualización:** Enero 23, 2026  
**Ver también:** [modulo-servicios.md](modulo-servicios.md) | [modulo-mantenimientos.md](modulo-mantenimientos.md) | [modulo-operadores.md](modulo-operadores.md) | [context.md](context.md)
