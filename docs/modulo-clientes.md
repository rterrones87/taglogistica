# 🏢 Módulo de Clientes

[← Volver al índice](context.md)

---

## 📋 Descripción General

El módulo de clientes gestiona la información de los clientes que solicitan servicios de transporte. Incluye datos de contacto, información fiscal y gestión de contenedores asociados.

---

## 🗂️ Modelo: Client

**Ubicación:** `app/Models/Client.php`

### Campos de la Tabla `clients`

| Campo | Tipo | Descripción | Requerido |
|-------|------|-------------|-----------|
| `id` | BIGINT | ID único del cliente | Auto |
| `name` | VARCHAR(255) | Nombre de la empresa | ✅ |
| `company_type` | VARCHAR(100) | Tipo de empresa | ❌ |
| `RFC` | VARCHAR(13) | Registro Federal de Contribuyentes | ❌ |
| `zip` | VARCHAR(10) | Código postal | ❌ |
| `contact_name` | VARCHAR(255) | Nombre del contacto | ❌ |
| `contact_email` | VARCHAR(255) | Email del contacto | ❌ |
| `active` | BOOLEAN | Cliente activo | ❌ |
| `zombie` | BOOLEAN | Soft delete (0=activo, 1=eliminado) | ❌ |
| `created_at` | TIMESTAMP | Fecha de creación | Auto |
| `updated_at` | TIMESTAMP | Fecha de actualización | Auto |

### Traits Aplicados

- **`HasFactory`** - Permite crear factories para testing
- **`UppercaseAttributes`** - Convierte automáticamente a mayúsculas el campo `name`
- **`HasMexicoTimezone`** - Maneja fechas en zona horaria México

### Relaciones

```php
// Relación uno a muchos con servicios
public function services() {
    return $this->hasMany(Service::class);
}
```

---

## 🔌 API Endpoints

### Listar Clientes

**Endpoint:** `GET /api/clients`  
**Permiso:** `clients.view` o `clients.consult`  
**Controlador:** `ClientController@index`

**Respuesta:**
```json
[
  {
    "id": 1,
    "name": "TRANSPORTES ABC SA DE CV",
    "company_type": "SA de CV",
    "RFC": "TABC850101ABC",
    "zip": "64000",
    "contact_name": "Juan Pérez",
    "contact_email": "juan@transportesabc.com",
    "active": 1,
    "zombie": 0
  }
]
```

### Crear Cliente

**Endpoint:** `POST /api/clients`  
**Permiso:** `clients.create`  
**Controlador:** `ClientController@store`

**Request Body:**
```json
{
  "name": "TRANSPORTES XYZ",
  "company_type": "SA de CV",
  "RFC": "TXYZ901010XYZ",
  "zip": "64000",
  "contact_name": "María López",
  "contact_email": "maria@xyz.com",
  "active": 1
}
```

**Validaciones:**
- `name`: requerido, string, max:255
- `company_type`: opcional, string
- `RFC`: opcional, string, max:13
- `zip`: opcional, string, max:10
- `contact_name`: opcional, string
- `contact_email`: opcional, email
- `active`: opcional, boolean

### Ver Cliente

**Endpoint:** `GET /api/clients/{id}`  
**Permiso:** `clients.view`  
**Controlador:** `ClientController@show`

### Actualizar Cliente

**Endpoint:** `PUT /api/clients/{id}`  
**Permiso:** `clients.edit`  
**Controlador:** `ClientController@update`

**Request Body:** Mismo formato que POST

### Eliminar Cliente (Soft Delete)

**Endpoint:** `DELETE /api/clients/{id}`  
**Permiso:** `clients.delete`  
**Controlador:** `ClientController@destroy`

**Comportamiento:**
- Marca el campo `zombie = 1` en lugar de eliminar físicamente
- Preserva integridad referencial con servicios existentes

---

## 🎨 Frontend - Vistas

### Listado: `clients.vue`

**Ruta:** `/panel/clients`  
**Ubicación:** `resources/js/pages/clients.vue`  
**Permisos:** `clients.view`

**Funcionalidades:**
- Listado de clientes con DataTable
- Filtros por estado (activo/inactivo)
- Búsqueda por nombre, RFC, contacto
- Botón "Nuevo Cliente" (redirecciona a `/panel/client`)
- Botón "Editar" por fila
- Botón "Eliminar" con confirmación

**Endpoints Consumidos:**
- `GET /api/clients` - Listar clientes
- `DELETE /api/clients/{id}` - Eliminar cliente

### Formulario: `client.vue`

**Rutas:** 
- `/panel/client` (crear nuevo)
- `/panel/client/{id}` (editar existente)

**Ubicación:** `resources/js/pages/forms/client.vue`  
**Permisos:** `clients.view`, `clients.create`, `clients.edit`

**Funcionalidades:**
- Formulario de creación/edición
- Campos: Nombre, Tipo de Empresa, RFC, CP, Contacto, Email
- Toggle "Cliente Activo"
- Validación en tiempo real
- Botón "Guardar"

**Endpoints Consumidos:**
- `GET /api/clients/{id}` - Obtener cliente (modo edición)
- `POST /api/clients` - Crear cliente
- `PUT /api/clients/{id}` - Actualizar cliente

### Componentes Relacionados

#### `clientmodal.vue`
Modal para selección rápida de cliente en formularios de servicio.

#### `clientslistmodal.vue`
Modal con listado completo de clientes para selección.

#### `clientcontainers.vue`
Componente para gestión de contenedores asociados a cliente en servicios.

---

## 📊 Matriz Vista ↔ Endpoint

| Vista | Endpoints Usados | Método | Propósito |
|-------|------------------|--------|-----------|
| `clients.vue` | `/api/clients` | GET | Listar clientes |
| `clients.vue` | `/api/clients/{id}` | DELETE | Eliminar cliente |
| `client.vue` | `/api/clients/{id}` | GET | Obtener cliente |
| `client.vue` | `/api/clients` | POST | Crear cliente |
| `client.vue` | `/api/clients/{id}` | PUT | Actualizar cliente |

---

## 🔒 Seguridad

### Control de Acceso

**Roles con acceso:**
- Administrador (todos los permisos)
- Logística (crear, editar, eliminar clientes)

**Permisos:**
- `clients.view` - Ver listado y detalles
- `clients.consult` - Consultar para selección en servicios
- `clients.create` - Crear nuevos clientes
- `clients.edit` - Editar clientes existentes
- `clients.delete` - Eliminar clientes (soft delete)

### Validaciones Backend

```php
// ClientController@store y update
$validator = Validator::make($request->all(), [
    'name' => 'required|string|max:255',
    'company_type' => 'nullable|string',
    'RFC' => 'nullable|string|max:13',
    'zip' => 'nullable|string|max:10',
    'contact_name' => 'nullable|string',
    'contact_email' => 'nullable|email',
    'active' => 'nullable|boolean'
]);
```

---

## 💡 Características Especiales

### 1. Soft Deletes (Zombie Flag)

En lugar de eliminar físicamente registros, se marca el campo `zombie = 1`:

```php
// ClientController@destroy
public function destroy($id) {
    $client = Client::find($id);
    $client->update(['zombie' => 1]);
    return response()->json(['message' => 'Cliente eliminado'], 200);
}
```

**Ventajas:**
- Preserva integridad referencial con servicios existentes
- Permite auditoría de datos históricos
- Posibilidad de recuperación

### 2. Uppercase Automático

El trait `UppercaseAttributes` convierte automáticamente el campo `name` a mayúsculas:

```php
// Antes de guardar
$client->name = "transportes xyz";

// Se guarda como
// "TRANSPORTES XYZ"
```

### 3. Zona Horaria México

El trait `HasMexicoTimezone` maneja las fechas en zona horaria `America/Mexico_City`:
- Los timestamps se almacenan en UTC en la BD
- Se convierten automáticamente a México al leer
- Se formatean correctamente en español

---

## 📋 Flujo de Trabajo

### Crear Nuevo Cliente

1. Usuario (Logística) accede a `/panel/clients`
2. Clic en botón "Nuevo Cliente"
3. Redirecciona a `/panel/client`
4. Completa formulario con datos del cliente
5. Clic en "Guardar"
6. `POST /api/clients` con datos del formulario
7. Backend valida y crea cliente
8. Trait `UppercaseAttributes` convierte nombre a mayúsculas
9. Respuesta exitosa redirige a `/panel/clients`

### Editar Cliente Existente

1. Usuario accede a `/panel/clients`
2. Clic en botón "Editar" en fila de cliente
3. Redirecciona a `/panel/client/{id}`
4. `GET /api/clients/{id}` carga datos
5. Usuario modifica campos necesarios
6. Clic en "Guardar"
7. `PUT /api/clients/{id}` con datos actualizados
8. Backend valida y actualiza
9. Respuesta exitosa redirige a `/panel/clients`

### Eliminar Cliente

1. Usuario accede a `/panel/clients`
2. Clic en botón "Eliminar" en fila de cliente
3. Modal de confirmación con SweetAlert2
4. Usuario confirma eliminación
5. `DELETE /api/clients/{id}`
6. Backend marca `zombie = 1` (soft delete)
7. Cliente desaparece de listado
8. Servicios existentes mantienen referencia histórica

---

## 🔗 Relaciones con Otros Módulos

### Con Servicios
- Un cliente puede tener múltiples servicios (`hasMany`)
- Al crear un servicio, se selecciona el cliente
- Ver: [modulo-servicios.md](modulo-servicios.md)

---

## 📝 Notas de Implementación

### Consideraciones

1. **RFC** - No se valida formato específico, solo longitud máxima
2. **Email** - Se valida formato pero no unicidad
3. **Código Postal** - No se valida formato específico
4. **Nombre** - Se convierte a mayúsculas automáticamente por el trait

### Mejoras Sugeridas

1. Implementar validación de formato RFC mexicano
2. Agregar validación de código postal mexicano
3. Implementar historial de cambios en datos del cliente
4. Agregar campo de notas o comentarios
5. Implementar sistema de etiquetas/categorías de clientes
6. Dashboard con métricas por cliente (servicios, ingresos, etc.)

---

**Última actualización:** Enero 23, 2026  
**Ver también:** [modulo-servicios.md](modulo-servicios.md) | [context.md](context.md)
