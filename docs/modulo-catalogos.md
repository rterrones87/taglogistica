# 📋 Módulo de Catálogos

[← Volver al índice](context.md)

---

## 📋 Descripción General

El módulo de catálogos gestiona lugares geográficos, casetas de peaje y catálogos dinámicos que se crean automáticamente durante la operación del sistema (terminales, tipos de contenedores, destinos, etc.).

---

## 🗂️ Modelos

### 1. Place (Lugares)

**Ubicación:** `app/Models/Place.php`

#### Campos de la Tabla `places`

| Campo | Tipo | Descripción | Requerido |
|-------|------|-------------|-----------|
| `id` | BIGINT | ID único del lugar | Auto |
| `name` | VARCHAR(255) | Nombre del lugar | ✅ |
| `zombie` | BOOLEAN | Soft delete | ❌ |
| `created_at` | TIMESTAMP | Fecha de creación | Auto |
| `updated_at` | TIMESTAMP | Fecha de actualización | Auto |

#### Relaciones
```php
public function booths() {
    return $this->hasMany(Booth::class);
}
```

### 2. Booth (Casetas)

**Ubicación:** `app/Models/Booth.php`

#### Campos de la Tabla `booths`

| Campo | Tipo | Descripción | Requerido |
|-------|------|-------------|-----------|
| `id` | BIGINT | ID único de la caseta | Auto |
| `name` | VARCHAR(255) | Nombre de la caseta | ✅ |
| `cost` | DECIMAL(10,2) | Costo de la caseta | ❌ |
| `zombie` | BOOLEAN | Soft delete | ❌ |
| `created_at` | TIMESTAMP | Fecha de creación | Auto |
| `updated_at` | TIMESTAMP | Fecha de actualización | Auto |

#### Relaciones
```php
public function place() {
    return $this->belongsTo(Place::class);
}

public function destinations() {
    return $this->hasMany(Destination::class);
}
```

### 3. Catalog (Catálogos Dinámicos)

**Ubicación:** `app/Models/Catalog.php`

#### Campos de la Tabla `catalogs`

| Campo | Tipo | Descripción | Requerido |
|-------|------|-------------|-----------|
| `id` | BIGINT | ID único | Auto |
| `type_collection` | VARCHAR(100) | Tipo de catálogo | ✅ |
| `title` | VARCHAR(255) | Valor del catálogo | ✅ |
| `zombie` | BOOLEAN | Soft delete | ❌ |
| `created_at` | TIMESTAMP | Fecha de creación | Auto |
| `updated_at` | TIMESTAMP | Fecha de actualización | Auto |

#### Tipos de Catálogos

- **`units`** - Tipos de unidades (Tractocamión, Remolque, etc.)
- **`containers`** - Tipos de contenedores (20', 40', 53', etc.)
- **`terminals`** - Terminales de carga
- **`destines`** - Destinos de contenedores
- **`container-numbers`** - Números de contenedor

---

## 🔌 API Endpoints

### Lugares (Places)

#### Listar Lugares

**Endpoint:** `GET /api/places`  
**Permiso:** `places.view` o `places.consult`  
**Controlador:** `PlaceController@index`

**Respuesta:**
```json
[
  {
    "id": 1,
    "name": "MONTERREY",
    "zombie": 0
  }
]
```

#### Crear Lugar

**Endpoint:** `POST /api/places`  
**Permiso:** `places.create`  
**Controlador:** `PlaceController@store`

**Request Body:**
```json
{
  "name": "GUADALAJARA"
}
```

#### Ver Lugar

**Endpoint:** `GET /api/places/{id}`  
**Permiso:** `places.view`  
**Controlador:** `PlaceController@show`

#### Actualizar Lugar

**Endpoint:** `PUT /api/places/{id}`  
**Permiso:** `places.edit`  
**Controlador:** `PlaceController@update`

#### Eliminar Lugar

**Endpoint:** `DELETE /api/places/{id}`  
**Permiso:** `places.delete`  
**Controlador:** `PlaceController@destroy`

### Casetas (Booths)

#### Listar Casetas

**Endpoint:** `GET /api/booths`  
**Permiso:** `booths.view` o `booths.consult`  
**Controlador:** `BoothController@index`

**Respuesta:**
```json
[
  {
    "id": 1,
    "name": "CASETA SALTILLO",
    "cost": 125.50,
    "zombie": 0
  }
]
```

#### Crear Caseta

**Endpoint:** `POST /api/booths`  
**Permiso:** `booths.create`  
**Controlador:** `BoothController@store`

**Request Body:**
```json
{
  "name": "CASETA PUEBLA",
  "cost": 85.00
}
```

#### Ver Caseta

**Endpoint:** `GET /api/booths/{id}`  
**Permiso:** `booths.view`  
**Controlador:** `BoothController@show`

#### Actualizar Caseta

**Endpoint:** `PUT /api/booths/{id}`  
**Permiso:** `booths.edit`  
**Controlador:** `BoothController@update`

#### Eliminar Caseta

**Endpoint:** `DELETE /api/booths/{id}`  
**Permiso:** `booths.delete`  
**Controlador:** `BoothController@destroy`

### Catálogos Dinámicos

#### Listar Tipos de Unidades

**Endpoint:** `GET /api/catalog/units`  
**Controlador:** `CatalogController@units`

**Respuesta:**
```json
[
  "TRACTOCAMIÓN",
  "REMOLQUE",
  "TORTON"
]
```

#### Listar Tipos de Contenedores

**Endpoint:** `GET /api/catalog/containers`  
**Controlador:** `CatalogController@containers`

**Respuesta:**
```json
[
  "20'",
  "40'",
  "53'"
]
```

#### Listar Terminales

**Endpoint:** `GET /api/catalog/terminals`  
**Controlador:** `CatalogController@terminals`

**Respuesta:**
```json
[
  "TERMINAL APM",
  "TERMINAL HUTCHISON",
  "TERMINAL MARITIMA"
]
```

#### Listar Destinos

**Endpoint:** `GET /api/catalog/destines`  
**Controlador:** `CatalogController@destines`

**Respuesta:**
```json
[
  "BODEGA NORTE",
  "BODEGA SUR",
  "ALMACEN CENTRAL"
]
```

#### Listar Números de Contenedor

**Endpoint:** `GET /api/catalog/container-numbers`  
**Controlador:** `CatalogController@container_numbers`

---

## 🎨 Frontend - Vistas

### Lugares

#### Listado: `places.vue`

**Ruta:** `/panel/places`  
**Ubicación:** `resources/js/pages/places.vue`  
**Permisos:** `places.view`

**Funcionalidades:**
- Listado de lugares con DataTable
- Búsqueda por nombre
- Botón "Nuevo Lugar"
- Botón "Editar" por fila
- Botón "Eliminar" con confirmación

**Endpoints Consumidos:**
- `GET /api/places`
- `DELETE /api/places/{id}`

#### Formulario: `place.vue`

**Rutas:** 
- `/panel/place` (crear)
- `/panel/place/{id}` (editar)

**Ubicación:** `resources/js/pages/forms/place.vue`  
**Permisos:** `places.view`, `places.create`, `places.edit`

**Endpoints Consumidos:**
- `GET /api/places/{id}`
- `POST /api/places`
- `PUT /api/places/{id}`

### Casetas

#### Listado: `booths.vue`

**Ruta:** `/panel/booths`  
**Ubicación:** `resources/js/pages/booths.vue`  
**Permisos:** `booths.view`

**Funcionalidades:**
- Listado de casetas con DataTable
- Búsqueda por nombre
- Visualización de costo
- Botón "Nueva Caseta"
- Botón "Editar" por fila
- Botón "Eliminar" con confirmación

**Endpoints Consumidos:**
- `GET /api/booths`
- `DELETE /api/booths/{id}`

#### Formulario: `booth.vue`

**Rutas:** 
- `/panel/booth` (crear)
- `/panel/booth/{id}` (editar)

**Ubicación:** `resources/js/pages/forms/booth.vue`  
**Permisos:** `booths.view`, `booths.create`, `booths.edit`

**Endpoints Consumidos:**
- `GET /api/booths/{id}`
- `POST /api/booths`
- `PUT /api/booths/{id}`

### Componentes Relacionados

#### `destinocasetas.vue`

Componente para gestión de destinos y casetas por contenedor en servicios.

**Funcionalidad:**
- Permite asignar casetas a cada destino de contenedor
- Selector de lugar y caseta
- Cálculo automático de costos de casetas

---

## 🔒 Seguridad

### Control de Acceso

**Roles con acceso:**
- Administrador (todos los permisos)
- Documentación (gestión de lugares y casetas)

**Permisos:**

**Lugares:**
- `places.view` - Ver listado y detalles
- `places.consult` - Consultar para selección
- `places.create` - Crear nuevos lugares
- `places.edit` - Editar lugares existentes
- `places.delete` - Eliminar lugares (soft delete)

**Casetas:**
- `booths.view` - Ver listado y detalles
- `booths.consult` - Consultar para selección en servicios
- `booths.create` - Crear nuevas casetas
- `booths.edit` - Editar casetas existentes
- `booths.delete` - Eliminar casetas (soft delete)

---

## 💡 Características Especiales

### 1. Catálogos Dinámicos

Los catálogos se crean automáticamente cuando se ingresan nuevos valores en los formularios de servicio:

```php
// ServiceController@store
if(isset($data['terminal']) && $data['terminal']) {
    Catalog::firstOrCreate([
        'type_collection' => 'terminals',
        'title' => strtoupper($data['terminal'])
    ]);
}

if(isset($data['type_unit']) && $data['type_unit']) {
    Catalog::firstOrCreate([
        'type_collection' => 'units',
        'title' => strtoupper($data['type_unit'])
    ]);
}
```

**Ventajas:**
- No requiere mantenimiento manual de catálogos
- Se adapta automáticamente a nuevos valores
- Autocompletar en formularios con valores históricos

### 2. Soft Deletes

Lugares y casetas utilizan el campo `zombie` para soft deletes:
- Preserva integridad referencial
- Permite auditoría histórica
- Posibilidad de recuperación

### 3. Uppercase Automático

El trait `UppercaseAttributes` convierte nombres a mayúsculas automáticamente:
- Consistencia en la base de datos
- Mejor presentación visual
- Facilita búsquedas

### 4. Costos de Casetas

Las casetas tienen un costo asociado que se utiliza para:
- Cálculo automático de costos de viaje
- Estimaciones de gastos
- Reportes financieros

---

## 📋 Flujos de Trabajo

### Crear Nuevo Lugar

1. Usuario (Documentación) accede a `/panel/places`
2. Clic en botón "Nuevo Lugar"
3. Ingresa nombre del lugar
4. Clic en "Guardar"
5. `POST /api/places` crea el lugar
6. Nombre se convierte a mayúsculas
7. Redirige a `/panel/places`

### Crear Nueva Caseta

1. Usuario accede a `/panel/booths`
2. Clic en botón "Nueva Caseta"
3. Ingresa nombre y costo de la caseta
4. Clic en "Guardar"
5. `POST /api/booths` crea la caseta
6. Nombre se convierte a mayúsculas
7. Redirige a `/panel/booths`

### Uso de Catálogos Dinámicos

1. Usuario crea un servicio en `/panel/service`
2. Ingresa "TERMINAL NUEVA" en campo terminal
3. Al guardar servicio, se crea entrada en `catalogs`:
   ```php
   {
     "type_collection": "terminals",
     "title": "TERMINAL NUEVA"
   }
   ```
4. En futuros servicios, "TERMINAL NUEVA" aparece en autocompletar

---

## 🔗 Relaciones con Otros Módulos

### Con Servicios
- Casetas se asocian a destinos de contenedores
- Terminales se ingresan en formulario de servicio
- Ver: [modulo-servicios.md](modulo-servicios.md)

### Con Costos
- Costos de casetas se calculan automáticamente
- Se suman al costo total del servicio
- Ver: [modulo-servicios.md](modulo-servicios.md)

---

## 📝 Notas de Implementación

### Consideraciones

1. **Catálogos Dinámicos** - No requieren CRUD manual, se crean automáticamente
2. **Costo de Casetas** - Pueden actualizarse para reflejar cambios en tarifas
3. **Nombres** - Se convierten a mayúsculas automáticamente

### Mejoras Sugeridas

1. Implementar historial de cambios en costos de casetas
2. Agregar coordenadas GPS a lugares
3. Crear dashboard de casetas más usadas
4. Implementar sistema de tarifas por fecha (temporadas)
5. Agregar fotos o mapas de ubicación de casetas
6. Sistema de alertas de cambios en costos de casetas
7. Exportación de catálogos a Excel/PDF
8. Importación masiva de lugares y casetas
9. Validación de duplicados en nombres
10. Agregar descripción o notas a lugares y casetas

---

**Última actualización:** Enero 23, 2026  
**Ver también:** [modulo-servicios.md](modulo-servicios.md) | [context.md](context.md)
