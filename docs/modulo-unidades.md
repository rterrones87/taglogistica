# 🚛 Módulo de Unidades

[← Volver al índice](context.md)

---

## 📋 Descripción General

El módulo de unidades gestiona la flota de vehículos de transporte y sus componentes (llantas). Incluye registro de tractocamiones, remolques, información técnica y seguimiento de llantas por posición.

---

## 🗂️ Modelo: Unit

**Ubicación:** `app/Models/Unit.php`

### Campos de la Tabla `units`

| Campo | Tipo | Descripción | Requerido |
|-------|------|-------------|-----------|
| `id` | BIGINT | ID único de la unidad | Auto |
| `type` | VARCHAR(100) | Tipo de unidad (Tractocamión, Remolque, etc.) | ❌ |
| `econame` | VARCHAR(50) | Número económico | ✅ |
| `brand` | VARCHAR(100) | Marca del vehículo | ❌ |
| `model` | VARCHAR(100) | Modelo del vehículo | ❌ |
| `llantas` | INTEGER | Número de llantas | ❌ |
| `trailer` | VARCHAR(50) | Número de remolque asociado | ❌ |
| `TAG` | VARCHAR(50) | Identificador TAG | ❌ |
| `active` | BOOLEAN | Unidad activa | ❌ |
| `zombie` | BOOLEAN | Soft delete (0=activo, 1=eliminado) | ❌ |
| `created_at` | TIMESTAMP | Fecha de creación | Auto |
| `updated_at` | TIMESTAMP | Fecha de actualización | Auto |

### Traits Aplicados

- **`HasFactory`** - Permite crear factories para testing
- **`UppercaseAttributes`** - Convierte automáticamente a mayúsculas ciertos campos
- **`HasMexicoTimezone`** - Maneja fechas en zona horaria México

### Relaciones

```php
// Relación uno a muchos con servicios
public function services() {
    return $this->hasMany(Service::class);
}

// Relación uno a muchos con mantenimientos
public function maintenances() {
    return $this->hasMany(Maintenance::class);
}

// Relación uno a muchos con llantas
public function tires() {
    return $this->hasMany(Tire::class);
}
```

---

## 🗂️ Modelo: Tire

**Ubicación:** `app/Models/Tire.php`

### Campos de la Tabla `tires`

| Campo | Tipo | Descripción | Requerido |
|-------|------|-------------|-----------|
| `id` | BIGINT | ID único de la llanta | Auto |
| `unit_id` | BIGINT | ID de la unidad | ✅ |
| `inventory_id` | BIGINT | ID del inventario (refacción) | ❌ |
| `serial` | VARCHAR(100) | Número de serie de la llanta | ❌ |
| `position` | VARCHAR(50) | Posición en la unidad | ❌ |
| `date` | DATE | Fecha de instalación | ❌ |
| `zombie` | BOOLEAN | Soft delete | ❌ |
| `created_at` | TIMESTAMP | Fecha de creación | Auto |
| `updated_at` | TIMESTAMP | Fecha de actualización | Auto |

### Relaciones

```php
// Pertenece a una unidad
public function unit() {
    return $this->belongsTo(Unit::class);
}

// Pertenece a un item del inventario
public function inventory() {
    return $this->belongsTo(Inventory::class);
}
```

---

## 🔌 API Endpoints

### Unidades

#### Listar Unidades

**Endpoint:** `GET /api/units`  
**Permiso:** `units.view` o `units.consult`  
**Controlador:** `UnitController@index`

**Respuesta:**
```json
[
  {
    "id": 1,
    "type": "TRACTOCAMIÓN",
    "econame": "T-001",
    "brand": "KENWORTH",
    "model": "T680",
    "llantas": 18,
    "trailer": "R-001",
    "TAG": "TAG001",
    "active": 1,
    "zombie": 0
  }
]
```

#### Listar Unidades para Mantenimiento

**Endpoint:** `GET /api/units/maintenance`  
**Permiso:** `units.view` o `units.consult`  
**Controlador:** `UnitController@indexForMaintenance`

**Descripción:** Endpoint especializado que filtra unidades disponibles para asignar a mantenimientos.

#### Crear Unidad

**Endpoint:** `POST /api/units`  
**Permiso:** `units.create`  
**Controlador:** `UnitController@store`

**Request Body:**
```json
{
  "type": "TRACTOCAMIÓN",
  "econame": "T-002",
  "brand": "FREIGHTLINER",
  "model": "CASCADIA",
  "llantas": 18,
  "trailer": "R-002",
  "TAG": "TAG002",
  "active": 1
}
```

#### Ver Unidad

**Endpoint:** `GET /api/units/{id}`  
**Permiso:** `units.view`  
**Controlador:** `UnitController@show`

#### Actualizar Unidad

**Endpoint:** `PUT /api/units/{id}`  
**Permiso:** `units.edit`  
**Controlador:** `UnitController@update`

#### Eliminar Unidad (Soft Delete)

**Endpoint:** `DELETE /api/units/{id}`  
**Permiso:** `units.delete`  
**Controlador:** `UnitController@destroy`

### Llantas

#### Listar Llantas

**Endpoint:** `GET /api/tires`  
**Permiso:** `tires.view`  
**Controlador:** `TireController@index`

**Respuesta:**
```json
[
  {
    "id": 1,
    "unit_id": 1,
    "inventory_id": 5,
    "serial": "DOT1234ABC",
    "position": "Eje delantero izquierdo",
    "date": "2025-01-15",
    "unit": { "econame": "T-001" },
    "inventory": { "name": "LLANTA 295/75R22.5" }
  }
]
```

#### Crear Llanta

**Endpoint:** `POST /api/tires`  
**Permiso:** `tires.create`  
**Controlador:** `TireController@store`

**Request Body:**
```json
{
  "unit_id": 1,
  "inventory_id": 5,
  "serial": "DOT5678DEF",
  "position": "Eje delantero derecho",
  "date": "2026-01-23"
}
```

#### Ver Llanta

**Endpoint:** `GET /api/tires/{id}`  
**Permiso:** `tires.view`  
**Controlador:** `TireController@show`

#### Actualizar Llanta

**Endpoint:** `PUT /api/tires/{id}`  
**Permiso:** `tires.edit`  
**Controlador:** `TireController@update`

#### Eliminar Llanta

**Endpoint:** `DELETE /api/tires/{id}`  
**Permiso:** `tires.delete`  
**Controlador:** `TireController@destroy`

---

## 🎨 Frontend - Vistas

### Unidades

#### Listado: `units.vue`

**Ruta:** `/panel/units`  
**Ubicación:** `resources/js/pages/units.vue`  
**Permisos:** `units.view`

**Funcionalidades:**
- Listado de unidades con DataTable
- Filtros por tipo, estado (activo/inactivo)
- Búsqueda por número económico, marca, modelo
- Botón "Nueva Unidad"
- Botón "Editar" por fila
- Botón "Eliminar" con confirmación

**Endpoints Consumidos:**
- `GET /api/units` - Listar unidades
- `DELETE /api/units/{id}` - Eliminar unidad

#### Formulario: `unit.vue`

**Rutas:** 
- `/panel/unit` (crear nueva)
- `/panel/unit/{id}` (editar existente)

**Ubicación:** `resources/js/pages/forms/unit.vue`  
**Permisos:** `units.view`, `units.create`, `units.edit`

**Funcionalidades:**
- Formulario de creación/edición
- Campos: Tipo, Número Económico, Marca, Modelo, Llantas, Remolque, TAG
- Toggle "Unidad Activa"
- Botón "Guardar"

**Endpoints Consumidos:**
- `GET /api/units/{id}` - Obtener unidad
- `POST /api/units` - Crear unidad
- `PUT /api/units/{id}` - Actualizar unidad

### Llantas

#### Listado: `tires.vue`

**Ruta:** `/panel/tires`  
**Ubicación:** `resources/js/pages/tires.vue`  
**Permisos:** `tires.view`

**Funcionalidades:**
- Listado de llantas con DataTable
- Vista por unidad y posición
- Búsqueda por serial, unidad
- Botón "Nueva Llanta"
- Botón "Editar" por fila
- Botón "Eliminar" con confirmación

**Endpoints Consumidos:**
- `GET /api/tires` - Listar llantas
- `DELETE /api/tires/{id}` - Eliminar llanta

#### Formulario: `tire.vue`

**Rutas:** 
- `/panel/tire` (crear nueva)
- `/panel/tire/{id}` (editar existente)

**Ubicación:** `resources/js/pages/forms/tire.vue`  
**Permisos:** `tires.view`, `tires.create`, `tires.edit`

**Funcionalidades:**
- Formulario de creación/edición
- Selector de unidad
- Selector de inventario (refacción)
- Campos: Serial, Posición, Fecha de instalación
- Botón "Guardar"

**Endpoints Consumidos:**
- `GET /api/tires/{id}` - Obtener llanta
- `GET /api/units` - Listar unidades
- `GET /api/inventories` - Listar inventario
- `POST /api/tires` - Crear llanta
- `PUT /api/tires/{id}` - Actualizar llanta

---

## 🔒 Seguridad

### Control de Acceso

**Roles con acceso:**
- Administrador (todos los permisos)
- Mantenimiento (gestión de unidades y llantas)

**Permisos:**

**Unidades:**
- `units.view` - Ver listado y detalles
- `units.consult` - Consultar para asignación en servicios/mantenimientos
- `units.create` - Crear nuevas unidades
- `units.edit` - Editar unidades existentes
- `units.delete` - Eliminar unidades (soft delete)

**Llantas:**
- `tires.view` - Ver listado y detalles
- `tires.create` - Crear registro de llanta
- `tires.edit` - Editar registro de llanta
- `tires.delete` - Eliminar registro de llanta

---

## 💡 Características Especiales

### 1. Soft Deletes (Zombie Flag)

Tanto unidades como llantas utilizan soft deletes con el campo `zombie`:

```php
// UnitController@destroy
public function destroy($id) {
    $unit = Unit::find($id);
    $unit->update(['zombie' => 1]);
    return response()->json(['message' => 'Unidad eliminada'], 200);
}
```

### 2. Uppercase Automático

El trait `UppercaseAttributes` convierte automáticamente ciertos campos a mayúsculas:
- `econame`
- `brand`
- `model`
- `type`

### 3. Gestión de Llantas por Posición

El sistema permite registrar la posición específica de cada llanta en la unidad:
- Eje delantero izquierdo/derecho
- Eje trasero izquierdo/derecho (interno/externo)
- Eje central, etc.

### 4. Tracking de Inventario

Cada llanta instalada está vinculada a un item del inventario, permitiendo:
- Rastrear qué refacción se usó
- Control de stock automático
- Historial de uso de refacciones

---

## 📋 Flujo de Trabajo

### Registrar Nueva Unidad

1. Usuario (Mantenimiento) accede a `/panel/units`
2. Clic en botón "Nueva Unidad"
3. Completa formulario con datos de la unidad
4. Clic en "Guardar"
5. `POST /api/units` crea la unidad
6. Trait convierte campos a mayúsculas
7. Redirige a `/panel/units`

### Registrar Llanta en Unidad

1. Usuario accede a `/panel/tires`
2. Clic en botón "Nueva Llanta"
3. Selecciona unidad del dropdown
4. Selecciona refacción del inventario
5. Ingresa serial y posición
6. Selecciona fecha de instalación
7. Clic en "Guardar"
8. `POST /api/tires` crea el registro
9. Se vincula con unidad e inventario
10. Redirige a `/panel/tires`

---

## 🔗 Relaciones con Otros Módulos

### Con Servicios
- Una unidad puede estar asignada a múltiples servicios
- Al asignar servicio, se selecciona unidad disponible
- Ver: [modulo-servicios.md](modulo-servicios.md)

### Con Mantenimientos
- Una unidad puede tener múltiples mantenimientos
- Cada mantenimiento está vinculado a una unidad específica
- Ver: [modulo-mantenimientos.md](modulo-mantenimientos.md)

### Con Inventario
- Las llantas se vinculan con items del inventario
- Control de stock al registrar llantas
- Ver: [modulo-mantenimientos.md](modulo-mantenimientos.md)

---

## 📝 Notas de Implementación

### Consideraciones

1. **Número Económico** - Identificador único de la unidad en la flota
2. **Llantas** - Campo numérico para especificar total de llantas
3. **Trailer** - Permite vincular remolque con tractocamión
4. **Posición de Llanta** - Texto libre, no validado contra esquema predefinido

### Mejoras Sugeridas

1. Implementar validación única de número económico
2. Crear catálogo de posiciones de llantas predefinidas
3. Agregar foto de la unidad
4. Implementar kilometraje actual de la unidad
5. Dashboard de estado de unidades (disponible, en servicio, mantenimiento)
6. Sistema de alertas de mantenimiento preventivo por kilometraje
7. Historial completo de servicios por unidad
8. Diagrama visual de llantas por posición
9. Alertas de reemplazo de llantas por desgaste/kilometraje
10. Integración con GPS para tracking en tiempo real

---

**Última actualización:** Enero 23, 2026  
**Ver también:** [modulo-servicios.md](modulo-servicios.md) | [modulo-mantenimientos.md](modulo-mantenimientos.md) | [context.md](context.md)
