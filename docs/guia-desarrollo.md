# 🛠️ Guía de Desarrollo - TAG Logística

**Versión:** 2.1  
**Última Actualización:** Enero 25, 2026  

---

## 📋 Tabla de Contenidos

- [Comandos Útiles](#comandos-útiles)
- [Manejo de Cantidades Monetarias](#manejo-de-cantidades-monetarias)
- [Convenciones de Código](#convenciones-de-código)
- [Testing](#testing)
- [Deployment](#deployment)

---

## Comandos Útiles

### Desarrollo Local

```bash
# Instalar dependencias
composer install
npm install

# Generar key de aplicación
php artisan key:generate

# Ejecutar migraciones
php artisan migrate

# Ejecutar seeders
php artisan db:seed

# Compilar assets frontend
npm run dev        # Desarrollo
npm run build      # Producción
```

### Artisan

```bash
# Crear migración
php artisan make:migration nombre_migracion

# Crear modelo con migración
php artisan make:model NombreModelo -m

# Crear controlador
php artisan make:controller NombreController

# Limpiar cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

---

## Manejo de Cantidades Monetarias

Todas las cantidades monetarias en el sistema usan `decimal(10, 2)`:
- **Precisión:** 2 decimales
- **Rango:** hasta $99,999,999.99

### En Migraciones

```php
// Al crear nuevas tablas
Schema::create('nombre_tabla', function (Blueprint $table) {
    $table->id();
    $table->decimal('amount', 10, 2);
    $table->timestamps();
});

// Al modificar columnas existentes
DB::statement('ALTER TABLE nombre_tabla MODIFY COLUMN amount DECIMAL(10, 2) NOT NULL');
```

### En Modelos

```php
class NombreModelo extends Model
{
    protected $fillable = [
        'amount',
        // otros campos...
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}
```

**Nota:** El cast `decimal:2` garantiza que Laravel siempre retorne el valor con 2 decimales, incluso si la base de datos almacena `.00`.

### En Validaciones

```php
// En Request o Controller
$validator = Validator::make($request->all(), [
    'amount' => 'required|numeric|decimal:0,2|min:0'
]);

// Con mínimo mayor a cero
'price' => 'required|numeric|decimal:0,2|min:0.01'
```

**Regla `decimal:0,2`:** Permite hasta 2 decimales (0 enteros obligatorios, 2 decimales máximo).

### En Frontend (Vue.js)

```html
<!-- Input de cantidad monetaria -->
<input 
    type="number" 
    step="0.01" 
    min="0" 
    v-model="item.amount" 
    placeholder="0.00"
/>
```

**Atributos importantes:**
- `type="number"` - Activa teclado numérico en móviles
- `step="0.01"` - Permite incrementos de centavos
- `min="0"` - Evita valores negativos
- `placeholder="0.00"` - Indica formato esperado

### Al Inicializar Valores

```js
// Correcto - con decimales
const newRow = { concept: '', cost: 0.00 };

// Evitar - sin decimales
const newRow = { concept: '', cost: 0 }; // ❌
```

### Al Mostrar Valores

```js
// En JavaScript
const formatted = Number(value).toFixed(2); // "1234.50"

// En Vue template
{{ Number(item.amount).toFixed(2) }}

// Con símbolo de moneda
${{ Number(item.amount).toFixed(2) }}
```

### Campos Monetarios en el Sistema

| Tabla | Campo | Descripción |
|-------|-------|-------------|
| `costs` | `booth_costs` | Costos de casetas |
| `costs` | `travel_cost` | Costo del viaje |
| `expenses` | `cost` | Costo del gasto |
| `diesel` | `amount` | Cantidad de diesel |
| `booths` | `cost` | Costo de caseta |
| `treasury_services` | `total` | Total de orden de servicio |
| `treasury_maintenances` | `total` | Total de orden de mantenimiento |
| `treasury_payments` | `total` | Total de pago semanal |
| `payments` | `total` | Total de pago individual |
| `discounts` | `total` | Total de descuento |
| `parts_supplier_requests` | `cost` | Costo de refacción/servicio |
| `services` | `commission` | Comisión del servicio |
| `diesel_cost` | `price` | Precio por litro de diesel |

---

## Convenciones de Código

### PHP

- **PSR-12:** Seguir estándar PSR-12 para formato de código
- **Nombres:** PascalCase para clases, camelCase para métodos y variables
- **Traits:** Usar `HasApproval`, `HasMexicoTimezone`, `UppercaseAttributes` cuando aplique
- **Soft Deletes:** Usar flag `zombie` en lugar de borrado físico

### Vue.js

- **Composition API:** Usar `<script setup>` para nuevos componentes
- **Nombres de archivos:** kebab-case para componentes
- **Props:** Definir con `defineProps()`
- **Emits:** Definir con `defineEmits()`
- **Composables:** Colocar en `resources/js/composables/`

### Base de Datos

- **Timestamps:** Usar `created_at` y `updated_at` con `useCurrent()` y `useCurrentOnUpdate()`
- **IDs:** Usar `integer` para IDs (compatibilidad con sistema legacy)
- **Flags:** Usar `integer` para booleans (0 = false, 1 = true)
- **Fechas:** Almacenar como `string` en formato 'YYYY-MM-DD'

---

## Testing

### Backend (PHPUnit)

```bash
# Ejecutar todos los tests
php artisan test

# Ejecutar test específico
php artisan test --filter NombreTest
```

### Frontend (Vitest)

```bash
# Ejecutar tests
npm run test

# Con coverage
npm run test:coverage
```

---

## Deployment

### Checklist Pre-Deployment

- [ ] Backup de base de datos
- [ ] Revisar migraciones pendientes con `php artisan migrate --pretend`
- [ ] Compilar assets de producción: `npm run build`
- [ ] Verificar variables de entorno (.env)
- [ ] Limpiar cache: `php artisan optimize:clear`

### Proceso de Deployment

1. **Backup**
   ```bash
   mysqldump -u usuario -p nombre_bd > backup_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **Pull de cambios**
   ```bash
   git pull origin master
   ```

3. **Dependencias**
   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci --production
   ```

4. **Migraciones**
   ```bash
   php artisan migrate --force
   ```

5. **Cache**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

6. **Assets**
   ```bash
   npm run build
   ```

### Rollback

En caso de problemas:

```bash
# 1. Restaurar backup
mysql -u usuario -p nombre_bd < backup_archivo.sql

# 2. Revertir migración (si aplica)
php artisan migrate:rollback --step=1

# 3. Revertir código
git checkout HEAD~1
```

---

## Historial de Cambios

### Enero 25, 2026
- **Implementación de soporte decimal:** Conversión de todos los campos monetarios a `decimal(10, 2)`
- **Actualización de validaciones:** Agregada regla `decimal:0,2` en validaciones
- **Actualización de frontend:** Inputs con `type="number" step="0.01"`
- **Modelos actualizados:** 12 modelos con casts `decimal:2`

### Enero 24, 2026
- **Tracking de servicios:** Agregados campos de tracking en tabla `services`

### Enero 13, 2026
- **Permisos:** Agregado permiso `services.assign_diesel`

---

**Nota:** Para contribuir a este proyecto, asegúrate de seguir las convenciones establecidas y mantener la documentación actualizada.
