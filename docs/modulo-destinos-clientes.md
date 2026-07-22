# 📍 Módulo de Destinos de Clientes

[← Volver al índice](context.md)

---

## 📋 Descripción General

El módulo de destinos de clientes gestiona las rutas (combinación cliente-destino) y su configuración de tarifas y casetas. Cada registro de `client_places` representa el monto que un cliente paga por un servicio hacia un destino específico, y además puede tener asociada una plantilla de casetas de peaje (separadas por dirección: ida y regreso). Las tarifas se utilizan para calcular automáticamente los montos en los pagos semanales de operadores.

---

## 🗂️ Modelos

### 1. ClientPlace

**Ubicación:** `app/Models/ClientPlace.php`

#### Campos de la Tabla `client_places`

| Campo | Tipo | Descripción | Requerido |
|-------|------|-------------|-----------|
| `id` | BIGINT | ID único del registro | Auto |
| `client_id` | INTEGER | ID del cliente asociado | ✅ |
| `place_id` | INTEGER | ID del destino asociado | ✅ |
| `type_unit_id` | INTEGER | ID del tipo de unidad (`unit_types`). Nullable para registros previos a esta funcionalidad | ❌ |
| `amount` | DECIMAL(10,2) | Pago de operadores para esta ruta | ✅ |
| `zombie` | INTEGER | Soft delete (0=activo, 1=eliminado) | ❌ |
| `created_at` | TIMESTAMP | Fecha de creación | Auto |
| `updated_at` | TIMESTAMP | Fecha de actualización | Auto |

**Restricción:** La combinación `client_id` + `place_id` + `type_unit_id` es única (`UNIQUE`), permitiendo que un mismo cliente-destino tenga distintas tarifas por tipo de unidad.

#### Traits Aplicados

- **`HasFactory`** - Permite crear factories para testing
- **`HasMexicoTimezone`** - Maneja fechas en zona horaria México

#### Relaciones

```php
// Relación con cliente
public function client() {
    return $this->belongsTo(Client::class);
}

// Relación con destino/lugar
public function place() {
    return $this->belongsTo(Place::class);
}

// Relación con tipo de unidad
public function typeUnit() {
    return $this->belongsTo(UnitType::class, 'type_unit_id');
}

// Plantilla de casetas asociadas a esta ruta
public function booths() {
    return $this->hasMany(ClientPlaceBooth::class);
}
```

---

### 2. ClientPlaceBooth

**Ubicación:** `app/Models/ClientPlaceBooth.php`

Representa una caseta de peaje dentro de la plantilla de una ruta. Cada registro asocia una caseta a una ruta con una dirección específica (ida o regreso).

#### Campos de la Tabla `client_place_booths`

| Campo | Tipo | Descripción | Requerido |
|-------|------|-------------|-----------|
| `id` | BIGINT | ID único del registro | Auto |
| `client_place_id` | INTEGER | ID de la ruta (`client_places`) | ✅ |
| `booth_id` | INTEGER | ID de la caseta | ✅ |
| `direction` | ENUM(`outbound`,`return`) | Dirección: `outbound`=Ida, `return`=Regreso | ✅ |
| `created_at` | TIMESTAMP | Fecha de creación | Auto |
| `updated_at` | TIMESTAMP | Fecha de actualización | Auto |

**Restricción:** La combinación `client_place_id` + `booth_id` + `direction` es única. No utiliza `zombie`; el borrado es físico.

#### Traits Aplicados

- **`HasFactory`**
- **`HasMexicoTimezone`**

#### Relaciones

```php
public function clientPlace() {
    return $this->belongsTo(ClientPlace::class);
}

public function booth() {
    return $this->belongsTo(Booth::class);
}
```

---

## 🔌 API Endpoints

### Listar Rutas

**Endpoint:** `GET /api/client-places`
**Permiso:** `client_places.view`
**Controlador:** `ClientPlaceController@index`

**Comportamiento:**
- Retorna registros con `zombie = 0` (activos)
- Incluye relaciones `client`, `place` y `typeUnit` (eager loading)
- Ordenados por `id` descendente

**Respuesta:**
```json
[
  {
    "id": 1,
    "client_id": 3,
    "place_id": 5,
    "amount": "1500.00",
    "zombie": 0,
    "client": {
      "id": 3,
      "name": "TRANSPORTES ABC SA DE CV"
    },
    "place": {
      "id": 5,
      "name": "MONTERREY"
    }
  }
]
```

### Crear Ruta

**Endpoint:** `POST /api/client-places`
**Permiso:** `client_places.create`
**Controlador:** `ClientPlaceController@store`

El endpoint recibe un lote de filas para la misma combinación cliente-destino, creando un registro por cada tipo de unidad. Tras crear cada registro, copia automáticamente la plantilla de casetas de los hermanos existentes (`firstOrCreate` por cada booth).

**Request Body:**
```json
{
  "client_id": 3,
  "place_id": 5,
  "items": [
    { "type_unit_id": 1, "amount": 1500.00 },
    { "type_unit_id": 2, "amount": 1200.00 }
  ]
}
```

**Validaciones:**
- `client_id`: requerido, entero
- `place_id`: requerido, entero
- `items`: requerido, array con al menos un elemento
- `items.*.type_unit_id`: requerido, entero, debe existir en `unit_types` con `zombie = 0`
- `items.*.amount`: requerido, numérico, min:0
- Unicidad por item: no puede existir un registro activo con la misma combinación `client_id` + `place_id` + `type_unit_id`

**Respuesta exitosa:** Retorna un array con todos los registros creados, incluyendo las relaciones `client`, `place` y `typeUnit`.

**Error de duplicado (400):**
```json
{
  "error": "Ya existen registros para los siguientes tipos de unidad en esta ruta.",
  "duplicates": [1, 2]
}
```

### Ver Ruta

**Endpoint:** `GET /api/client-places/{id}`
**Permiso:** `client_places.view`
**Controlador:** `ClientPlaceController@show`

### Actualizar Ruta

**Endpoint:** `PUT /api/client-places/{id}`
**Permiso:** `client_places.edit`
**Controlador:** `ClientPlaceController@update`

**Request Body:**
```json
{
  "amount": 1800.00
}
```

**Nota:** Solo permite actualizar el campo `amount`. El cliente y destino no son editables una vez creado el registro.

**Validaciones:**
- `amount`: requerido, numérico, min:0

**Respuesta exitosa:** HTTP 200 sin cuerpo.

### Eliminar Ruta (Soft Delete)

**Endpoint:** `DELETE /api/client-places/{id}`
**Permiso:** `client_places.delete`
**Controlador:** `ClientPlaceController@destroy`

**Comportamiento:**
- Marca el campo `zombie = 1` en lugar de eliminar físicamente
- Retorna HTTP 204 (No Content)

---

### Plantilla de Casetas

#### Listar casetas de una ruta

**Endpoint:** `GET /api/client-places/{id}/booths`
**Permiso:** `client_places.edit`
**Controlador:** `ClientPlaceController@listBooths`

**Respuesta:**
```json
{
  "has_siblings": true,
  "outbound": [
    {
      "id": 1,
      "client_place_id": 3,
      "booth_id": 2,
      "direction": "outbound",
      "is_unique": false,
      "booth": { "id": 2, "name": "CASETA SALTILLO" }
    }
  ],
  "return": [
    {
      "id": 2,
      "client_place_id": 3,
      "booth_id": 4,
      "direction": "return",
      "is_unique": true,
      "booth": { "id": 4, "name": "CASETA MATEHUALA" }
    }
  ]
}
```

- `has_siblings`: `true` si existen otros `client_places` activos con el mismo `client_id + place_id`.
- `is_unique`: `true` si la caseta (mismo `booth_id + direction`) no existe en ningún hermano.

#### Agregar caseta a la plantilla

**Endpoint:** `POST /api/client-places/{id}/booths`
**Permiso:** `client_places.edit`
**Controlador:** `ClientPlaceController@addBooth`

**Request Body:**
```json
{
  "booth_id": 2,
  "direction": "outbound",
  "replicate": true
}
```

**Validaciones:**
- `booth_id`: requerido, entero
- `direction`: requerido, enum (`outbound`, `return`)
- `replicate`: opcional, booleano (default `false`)
- Unicidad: no puede existir la misma combinación `client_place_id` + `booth_id` + `direction`

**Comportamiento con `replicate: true`:** tras crear el registro en el `client_place` actual, replica la caseta a todos los hermanos activos (mismo `client_id + place_id`) usando `firstOrCreate`, sin violar constraints.

**Error de duplicado (400):**
```json
{
  "error": "Esta caseta ya está en la plantilla para esa dirección."
}
```

#### Eliminar caseta de la plantilla

**Endpoint:** `DELETE /api/client-places/{clientPlaceId}/booths/{boothRecordId}`
**Permiso:** `client_places.edit`
**Controlador:** `ClientPlaceController@removeBooth`

**Query Params:**
- `replicate` (opcional, `0` o `1`, default `0`): si es `1`, elimina también la caseta equivalente (`booth_id + direction`) de todos los hermanos activos.

**Comportamiento:** Eliminación física del registro en el `client_place` actual. Con `replicate=1`, también elimina en los hermanos. Retorna HTTP 204.

---

## 🎨 Frontend - Vista

### Listado: `client_places.vue`

**Ruta:** `/panel/client-places`
**Ubicación:** `resources/js/pages/client_places.vue`
**Permisos:** `client_places.view`
**Breadcrumb:** Control de rutas

**Funcionalidades:**
- Listado de rutas con DataTable (filtrable y ordenable)
- Columnas: ID, Cliente, Destino, Tipo de Unidad, Pago de operadores (formato moneda)
- Botón "Nuevo registro" (abre modal de creación, requiere `client_places.create`)
- Botones de catálogos en toolbar: Clientes, Destinos, Casetas (abren modales de consulta)
- Botón "Casetas" por fila (abre modal de plantilla de casetas, requiere `client_places.edit`)
- Botón "Editar" por fila (abre modal de edición, requiere `client_places.edit`)
- Botón "Eliminar" por fila con confirmación SweetAlert2 (requiere `client_places.delete`)
- Diseño responsive (botón flotante en móvil)

**Endpoints Consumidos:**
- `GET /api/client-places` - Listar rutas
- `DELETE /api/client-places/{id}` - Eliminar ruta

### Modal de Creación

**Campos:**
- **Cliente** (select) - Carga catálogo de clientes activos (`active = 1`, `zombie = 0`)
- **Destino** (select) - Carga catálogo de destinos activos (`zombie = 0`)
- **Tabla de tipos de unidad** - Filas dinámicas; cada fila contiene:
  - Tipo de Unidad (`UnitTypeSelect` cargado desde `GET /api/unit-types`)
  - Monto (`CurrencyInput`, valor por defecto `0.00`)
  - Botón × para quitar la fila (oculto si solo hay una fila)
- Botón "+ Agregar tipo de unidad" para añadir nuevas filas

**Validación frontend:**
- Cliente y destino son requeridos
- Todos los selects de Tipo de Unidad deben tener valor seleccionado

**Endpoints Consumidos:**
- `GET /api/clients` - Cargar catálogo de clientes
- `GET /api/places` - Cargar catálogo de destinos
- `GET /api/unit-types` - Cargar tipos de unidad (dentro de cada `UnitTypeSelect`)
- `POST /api/client-places` - Crear registros (batch)

**Comportamiento:**
- Los catálogos de clientes y destinos se cargan al abrir el modal, en paralelo mediante `Promise.all`
- El servidor devuelve un array; todos los registros creados se insertan al inicio de la lista local

### Modal de Plantilla de Casetas

Se accede desde el botón "Casetas" de cada fila. El título del modal muestra `{Cliente} → {Destino}`.

**Secciones:**
- **Checkbox "Auto replicación"** (checked por defecto) — al activarse, cada agregar o eliminar se propaga a todos los `client_places` con el mismo `client_id + place_id`. Se resetea a `true` cada vez que se abre el modal.
- Formulario superior para agregar casetas: selector de caseta (`suggestioninput` sobre `/api/booths`) + selector de dirección (Ida/Regreso)
- Tabla "Casetas de Ida" con las casetas `direction = outbound`
- Tabla "Casetas de Regreso" con las casetas `direction = return`
- Botón "Eliminar" por caseta con confirmación SweetAlert2

**Indicador de unicidad por fila:**
Visible únicamente cuando `has_siblings = true` (la ruta tiene otros tipos de unidad configurados). Cada fila muestra un punto de color junto al nombre de la caseta:
- 🟠 Naranja — caseta presente solo en este registro (`is_unique = true`)
- 🟢 Verde — caseta compartida con al menos un hermano (`is_unique = false`)

**Endpoints Consumidos:**
- `GET /api/client-places/{id}/booths` - Cargar plantilla al abrir el modal (incluye `has_siblings` e `is_unique`)
- `POST /api/client-places/{id}/booths` - Agregar caseta (con `replicate` según checkbox)
- `DELETE /api/client-places/{clientPlaceId}/booths/{boothRecordId}?replicate=0|1` - Eliminar caseta

**Comportamiento:**
- La plantilla se recarga después de cada operación de agregar o eliminar
- El campo de caseta se reinicia tras agregar exitosamente
- Los errores de duplicado se muestran inline debajo del formulario

### Modal de Edición

**Campos:**
- **Cliente** (input deshabilitado) - Solo lectura
- **Destino** (input deshabilitado) - Solo lectura
- **Tipo de Unidad** (input deshabilitado) - Solo lectura
- **Pago de operadores** (CurrencyInput) - Editable

**Endpoints Consumidos:**
- `PUT /api/client-places/{id}` - Actualizar monto

**Comportamiento:**
- Solo permite editar el monto
- Actualiza el ítem directamente en la lista local sin recargar

### Composable Utilizado

**`actionslist`** (`resources/js/composables/actionslist.js`) - Proporciona:
- `items`: lista reactiva de registros
- `loadItems()`: carga datos del endpoint
- `deleteItem(id)`: eliminación con confirmación

---

## 📊 Matriz Vista ↔ Endpoint

| Vista | Endpoint | Método | Propósito |
|-------|----------|--------|-----------|
| `client_places.vue` | `/api/client-places` | GET | Listar rutas |
| `client_places.vue` | `/api/client-places/{id}` | DELETE | Eliminar ruta |
| `client_places.vue` (modal crear) | `/api/clients` | GET | Cargar catálogo clientes |
| `client_places.vue` (modal crear) | `/api/places` | GET | Cargar catálogo destinos |
| `client_places.vue` (modal crear) | `/api/client-places` | POST | Crear ruta |
| `client_places.vue` (modal editar) | `/api/client-places/{id}` | PUT | Actualizar monto |
| `client_places.vue` (modal casetas) | `/api/client-places/{id}/booths` | GET | Cargar plantilla de casetas |
| `client_places.vue` (modal casetas) | `/api/client-places/{id}/booths` | POST | Agregar caseta a plantilla |
| `client_places.vue` (modal casetas) | `/api/client-places/{clientPlaceId}/booths/{boothRecordId}` | DELETE | Eliminar caseta de plantilla |

---

## 🔒 Seguridad

### Control de Acceso

**Roles con acceso:**
- Administrador (todos los permisos asignados por migración)

**Permisos propios del módulo:**
- `client_places.view` - Ver listado de rutas
- `client_places.create` - Crear nuevas rutas (también controla visibilidad de los botones de catálogo en toolbar)
- `client_places.edit` - Editar monto y gestionar plantilla de casetas
- `client_places.delete` - Eliminar rutas (soft delete)

**Permisos de otros módulos consultados en la vista:**
- `clients.consult` - Controla visibilidad del botón "Clientes" en toolbar
- `places.consult` - Controla visibilidad del botón "Destinos" en toolbar
- `booths.consult` - Controla visibilidad del botón "Casetas" en toolbar

### Validaciones Backend

```php
// ClientPlaceController@store
$validator = Validator::make($request->all(), [
    'client_id' => 'required|integer',
    'place_id'  => 'required|integer',
    'amount'    => 'required|numeric|min:0',
]);

// Verificación de unicidad por item antes de crear
foreach ($request->items as $item) {
    ClientPlace::where('client_id', $request->client_id)
        ->where('place_id', $request->place_id)
        ->where('type_unit_id', $item['type_unit_id'])
        ->where('zombie', 0)
        ->exists();
}

// ClientPlaceController@update
$validator = Validator::make($request->all(), [
    'amount' => 'required|numeric|min:0',
]);

// ClientPlaceController@addBooth
$validator = Validator::make($request->all(), [
    'booth_id'  => 'required|integer',
    'direction' => 'required|in:outbound,return',
]);
```

---

## 💡 Características Especiales

### 1. Soft Deletes Selectivo

Las rutas (`client_places`) utilizan soft delete con `zombie = 1`. Las casetas de plantilla (`client_place_booths`) no utilizan zombie; se eliminan físicamente.

```php
// ClientPlaceController@destroy
ClientPlace::find($id)->update(['zombie' => 1]);
```

**Ventajas:**
- Preserva integridad referencial con servicios y pagos existentes
- Permite auditoría de datos históricos
- Posibilidad de recuperación

### 2. Validación de Duplicados

El sistema previene rutas duplicadas para la misma combinación cliente-destino-tipo:
- A nivel de base de datos: constraint `UNIQUE` en `[client_id, place_id, type_unit_id]`
- A nivel de aplicación: validación en `store()` por cada item, excluyendo registros con `zombie = 1`

Para las casetas de plantilla, el constraint único es sobre `[client_place_id, booth_id, direction]`, validado también a nivel de aplicación.

### 3. Edición Restringida

Una vez creada la ruta, solo se puede modificar el monto. El cliente, destino y tipo de unidad quedan fijos. Para cambiar el tipo de unidad se debe eliminar el registro (soft delete) y crear uno nuevo con el tipo correcto.

### 4. Carga Lazy de Catálogos

Los catálogos de clientes y destinos se cargan solo al abrir el modal de creación (no al cargar la página), en paralelo mediante `Promise.all`, optimizando el rendimiento inicial.

### 5. Plantilla de Casetas por Ruta

Cada ruta puede tener una plantilla de casetas configurada, separada por dirección (ida/regreso). Esta plantilla sirve de referencia para la asignación de casetas a contenedores dentro de los servicios.

### 6. Auto-replicación de Casetas

Cuando se trabaja con múltiples tipos de unidad para una misma ruta (mismo `client_id + place_id`), el sistema ofrece dos mecanismos de sincronización:

**Al crear un nuevo registro:** el backend recopila todas las casetas (union de `booth_id + direction`) de los hermanos existentes y las copia al nuevo registro con `firstOrCreate`. Esto es automático y silencioso.

**Al agregar o quitar una caseta desde el modal:** el checkbox "Auto replicación" (default ON) controla si la operación se propaga a los hermanos. La propagación usa `firstOrCreate` al agregar (idempotente) y `DELETE` directo al quitar (busca por `booth_id + direction`, no por `id`).

Si un registro no tiene hermanos, los parámetros de replicación no tienen efecto.

---

## 📋 Flujos de Trabajo

### Crear Nueva Ruta

1. Usuario accede a `/panel/client-places`
2. Clic en botón "Nuevo registro"
3. Se abre modal y se cargan catálogos de clientes y destinos en paralelo
4. Selecciona cliente, destino e ingresa monto
5. Clic en "Guardar"
6. `POST /api/client-places` con datos del formulario
7. Backend valida campos y verifica que no exista duplicado
8. Si es exitoso, el registro aparece al inicio de la lista
9. Modal se cierra automáticamente

### Editar Monto de Ruta

1. Usuario accede a `/panel/client-places`
2. Clic en botón "Editar" en la fila deseada
3. Se abre modal con cliente y destino en solo lectura
4. Modifica el monto
5. Clic en "Guardar"
6. `PUT /api/client-places/{id}` con nuevo monto
7. El monto se actualiza en la lista local
8. Modal se cierra automáticamente

### Configurar Plantilla de Casetas

1. Usuario accede a `/panel/client-places`
2. Clic en botón "Casetas" en la fila deseada
3. Se abre modal con el título `{Cliente} → {Destino}` y se carga la plantilla actual
4. El checkbox "Auto replicación" aparece activado por defecto
5. Si hay hermanos (otros tipos de unidad para la misma ruta), cada caseta muestra un punto de color (🟢 compartida / 🟠 única)
6. Para agregar: selecciona caseta y dirección (Ida/Regreso), clic en "Agregar"
   - Con Auto replicación ON: `POST /api/client-places/{id}/booths` con `replicate: true` → se agrega en el registro actual y en todos los hermanos
   - Con Auto replicación OFF: solo se agrega en el registro actual
7. La plantilla se recarga automáticamente y los indicadores de color se actualizan
8. Para eliminar: clic en "Eliminar" junto a la caseta, confirma en SweetAlert2
   - Con Auto replicación ON: `DELETE .../booths/{id}?replicate=1` → elimina en el actual y en los hermanos que tengan esa caseta
   - Con Auto replicación OFF: solo elimina del registro actual

### Eliminar Ruta

1. Usuario accede a `/panel/client-places`
2. Clic en botón "Eliminar" en la fila deseada
3. Modal de confirmación con SweetAlert2
4. Usuario confirma eliminación
5. `DELETE /api/client-places/{id}`
6. Backend marca `zombie = 1` (soft delete)
7. Registro desaparece del listado

---

## 🔗 Relaciones con Otros Módulos

### Con Clientes
- Cada ruta pertenece a un cliente (`belongsTo`)
- Se cargan clientes activos para el selector de creación
- Ver: [modulo-clientes.md](modulo-clientes.md)

### Con Catálogos (Places y Booths)
- Cada ruta pertenece a un destino/lugar (`belongsTo`)
- Cada ruta puede tener casetas de peaje asociadas a través de `ClientPlaceBooth`
- Ver: [modulo-catalogos.md](modulo-catalogos.md)

### Con Pagos de Operadores
- Las tarifas de `client_places` se utilizan en el cálculo de montos para pagos semanales de operadores
- En `ServiceController@weekly_operator_payments`, se suman las tarifas correspondientes al cliente y destinos del servicio:

```php
'amount' => ClientPlace::where('client_id', $item->client_id)
    ->whereIn('place_id', $item->containers->pluck('place_id')->unique()->toArray())
    ->where('zombie', 0)
    ->sum('amount'),
```

- Ver: [modulo-operadores.md](modulo-operadores.md)

---

## 📝 Notas de Implementación

### Consideraciones

1. **Unicidad en client_places** - La constraint UNIQUE en BD es sobre `client_id` + `place_id` + `type_unit_id`. Como `type_unit_id` es nullable, MySQL considera los NULL como distintos entre sí, por lo que múltiples registros con `type_unit_id = NULL` no entran en conflicto. La validación de aplicación excluye registros con `zombie = 1`.
2. **Unicidad en client_place_booths** - La constraint es sobre `client_place_id` + `booth_id` + `direction`, permitiendo que una misma caseta aparezca tanto en ida como en regreso.
3. **Catálogos** - Los selects filtran clientes por `active = 1` y `zombie = 0`, y destinos por `zombie = 0`
4. **Monto** - Se utiliza el componente `CurrencyInput` para formato de moneda
5. **Sin formulario dedicado** - A diferencia de otros módulos, no tiene página de formulario separada; usa modales directamente en la vista de listado

### Migraciones

- `2026_03_24_000001_create_client_places_table.php` - Crea la tabla `client_places`
- `2026_03_24_000002_add_client_places_permissions.php` - Crea los 4 permisos y los asigna al rol Administrador (id=1)
- `2026_03_31_000001_create_client_place_booths_table.php` - Crea la tabla `client_place_booths`
- `2026_04_11_000002_add_type_unit_to_client_places_table.php` - Agrega `type_unit_id` y actualiza el constraint UNIQUE

---

**Última actualización:** Abril 11, 2026 (v3 — auto-replicación de casetas)
**Ver también:** [modulo-clientes.md](modulo-clientes.md) | [modulo-catalogos.md](modulo-catalogos.md) | [modulo-operadores.md](modulo-operadores.md) | [context.md](context.md)
