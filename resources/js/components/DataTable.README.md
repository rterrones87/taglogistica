# DataTable Component - Documentación

## Descripción
Componente Vue 3 reutilizable para mostrar datos en formato tabla con funcionalidades avanzadas:
- ✅ Filtrado tipo Excel por columna (valores únicos)
- ✅ Ordenamiento ascendente/descendente
- ✅ Paginación de 25 registros por página
- ✅ Botón de resincronización
- ✅ Responsive (tabla en desktop, cards en móvil)
- ✅ Slots para personalizar acciones
- ✅ Formatters personalizados por columna

## Instalación

Importar el componente en tu vista:

```javascript
import DataTable from '@/components/DataTable.vue';
```

## Props

| Prop | Tipo | Requerido | Default | Descripción |
|------|------|-----------|---------|-------------|
| `data` | Array | Sí | `[]` | Array de objetos con los datos a mostrar |
| `columns` | Array | Sí | - | Configuración de columnas (ver estructura abajo) |
| `onReload` | Function | No | `null` | Función async para recargar datos |
| `emptyMessage` | String | No | `'No hay registros disponibles.'` | Mensaje cuando no hay datos |
| `tableId` | String | No | `'datatable'` | ID único para keys |

## Estructura de Columnas

Cada objeto en el array `columns` debe tener:

```javascript
{
  key: 'nombre_propiedad',      // Requerido: nombre de la propiedad en el objeto
  label: 'Nombre Columna',      // Requerido: texto del header
  sortable: true,               // Opcional: si es ordenable (default: true)
  filterable: true,             // Opcional: si es filtrable (default: true)
  formatter: (value, row) => {} // Opcional: función para formatear el valor
}
```

### Propiedades Anidadas
El componente soporta propiedades anidadas usando notación de punto:

```javascript
{ 
  key: 'user.role.name',
  label: 'Rol' 
}
```

## Slots

### Slot `actions`
Slot scoped para personalizar las acciones de cada fila.

**Props del slot:**
- `row`: Objeto completo de la fila
- `index`: Índice de la fila

## Ejemplos de Uso

### Ejemplo 1: Tabla Simple (Clientes)

```vue
<template>
  <DataTable
    :data="items"
    :columns="columns"
    :onReload="loadItems"
    emptyMessage="No hay clientes disponibles."
  >
    <template #actions="{ row }">
      <div v-if="!isRole('Logística')" class="flex justify-center flex-col md:flex-row">
        <TableAction 
          title="Editar" 
          icon="edit.png" 
          :route="`client/${row.id}`" 
        />
        <TableAction 
          title="Eliminar" 
          icon="delete.png" 
          @click.prevent="deleteItem(row.id)" 
        />
      </div>
    </template>
  </DataTable>
</template>

<script setup>
import { inject } from 'vue';
import { actionslist } from '../composables/actionslist';
import DataTable from '@/components/DataTable.vue';
import TableAction from '@/components/TableAction.vue';

const dialogs = inject("swal");
const { items, deleteItem, loadItems } = actionslist({
  endpoint: 'clients',
  dialogs
});

const isRole = (role) => { 
  return role === localStorage.getItem('user_role');
}

// Configuración de columnas
const columns = [
  { 
    key: 'id', 
    label: 'ID', 
    sortable: true, 
    filterable: true 
  },
  { 
    key: 'name', 
    label: 'Nombre', 
    sortable: true, 
    filterable: true 
  },
  { 
    key: 'company_type', 
    label: 'Persona', 
    sortable: true, 
    filterable: true,
    formatter: (value) => value === 1 ? 'Física' : 'Moral'
  },
  { 
    key: 'RFC', 
    label: 'RFC', 
    sortable: true, 
    filterable: true 
  },
  { 
    key: 'active', 
    label: 'Activo', 
    sortable: true, 
    filterable: true,
    formatter: (value) => value === 1 ? 'Activo' : 'Inactivo'
  }
];
</script>
```

### Ejemplo 2: Tabla con Datos Relacionados (Usuarios)

```vue
<template>
  <DataTable
    :data="items"
    :columns="columns"
    :onReload="loadItems"
    emptyMessage="No hay usuarios disponibles."
  >
    <template #actions="{ row }">
      <div class="flex justify-center flex-col md:flex-row">
        <TableAction 
          title="Editar" 
          icon="edit.png" 
          :route="`user/${row.id}`" 
        />
        <TableAction 
          title="Eliminar" 
          icon="delete.png" 
          @click.prevent="deleteItem(row.id)" 
        />
      </div>
    </template>
  </DataTable>
</template>

<script setup>
import { inject } from 'vue';
import { actionslist } from '../composables/actionslist';
import DataTable from '@/components/DataTable.vue';
import TableAction from '@/components/TableAction.vue';

const dialogs = inject("swal");
const { items, deleteItem, loadItems } = actionslist({
  endpoint: 'users',
  dialogs
});

const columns = [
  { key: 'id', label: 'ID' },
  { key: 'name', label: 'Nombre' },
  { key: 'email', label: 'Correo' },
  { 
    key: 'role.name',  // Propiedad anidada
    label: 'Perfil' 
  },
  { 
    key: 'active', 
    label: 'Activo',
    formatter: (value) => value === 1 ? 'Activo' : 'Inactivo'
  }
];
</script>
```

### Ejemplo 3: Tabla con Formatters Complejos (Unidades)

```vue
<template>
  <DataTable
    :data="items"
    :columns="columns"
    :onReload="loadItems"
  >
    <template #actions="{ row }">
      <div class="flex justify-center flex-col md:flex-row">
        <TableAction title="Editar" icon="edit.png" :route="`unit/${row.id}`" />
        <TableAction title="Eliminar" icon="delete.png" @click.prevent="deleteItem(row.id)" />
      </div>
    </template>
  </DataTable>
</template>

<script setup>
import { inject } from 'vue';
import { actionslist } from '../composables/actionslist';
import DataTable from '@/components/DataTable.vue';
import TableAction from '@/components/TableAction.vue';

const dialogs = inject("swal");
const { items, deleteItem, loadItems } = actionslist({
  endpoint: 'units',
  dialogs
});

// Funciones auxiliares para formatear
const getTypeTitle = (id) => {
  const types = { 1: 'Tractocamión', 2: 'Remolque' };
  return types[id] || '';
};

const columns = [
  { key: 'id', label: 'ID' },
  { 
    key: 'type', 
    label: 'Tipo',
    formatter: (value) => getTypeTitle(value)
  },
  { key: 'econame', label: 'Nombre' },
  { key: 'brand', label: 'Marca' },
  { key: 'model', label: 'Modelo' },
  { key: 'TAG', label: 'TAG' },
  { 
    key: 'active', 
    label: 'Activo',
    formatter: (value) => value === 1 ? 'Si' : 'No'
  }
];
</script>
```

### Ejemplo 4: Columnas No Ordenables/Filtrables

```vue
<script setup>
const columns = [
  { 
    key: 'id', 
    label: 'ID',
    sortable: false,    // No se puede ordenar
    filterable: false   // No se puede filtrar
  },
  { 
    key: 'name', 
    label: 'Nombre' 
    // Por defecto sortable y filterable son true
  },
  { 
    key: 'avatar', 
    label: 'Avatar',
    sortable: false,
    filterable: false,
    formatter: (value) => value ? '✓' : '✗'
  }
];
</script>
```

### Ejemplo 5: Sin Botón de Recarga

```vue
<template>
  <!-- Si no pasas onReload, no se muestra el botón de recargar -->
  <DataTable
    :data="items"
    :columns="columns"
    emptyMessage="No hay datos."
  >
    <template #actions="{ row }">
      <!-- tus acciones -->
    </template>
  </DataTable>
</template>
```

### Ejemplo 6: Acciones Condicionales

```vue
<template>
  <DataTable
    :data="items"
    :columns="columns"
    :onReload="loadItems"
  >
    <template #actions="{ row }">
      <div class="flex justify-center flex-col md:flex-row">
        <!-- Mostrar botón solo si el estado permite edición -->
        <TableAction 
          v-if="row.state_id === 1"
          title="Editar" 
          icon="edit.png" 
          :route="`service/${row.id}`" 
        />
        
        <!-- Mostrar botón solo para administradores -->
        <TableAction 
          v-if="isRole('Administrador')"
          title="Eliminar" 
          icon="delete.png" 
          @click.prevent="deleteItem(row.id)" 
        />
        
        <!-- Acción personalizada -->
        <TableAction 
          v-if="row.state_id >= 3"
          title="Ver Detalles" 
          icon="view.png" 
          @click.prevent="viewDetails(row)" 
        />
      </div>
    </template>
  </DataTable>
</template>
```

### Ejemplo 7: Formatter con Lógica Compleja

```vue
<script setup>
const columns = [
  { key: 'id', label: 'ID' },
  { 
    key: 'containers', 
    label: 'Referencias',
    sortable: false,  // Array no es ordenable
    filterable: false, // Array no es filtrable
    formatter: (containers) => {
      // Formatear array de contenedores
      if (!containers || !Array.isArray(containers)) return '';
      return containers
        .map(c => c.order_number)
        .filter(n => !!n)
        .join(', ');
    }
  },
  { 
    key: 'cost', 
    label: 'Costo',
    formatter: (value) => {
      // Formatear moneda
      return value ? `$${Number(value).toFixed(2)}` : '$0.00';
    }
  },
  { 
    key: 'created_at', 
    label: 'Fecha',
    formatter: (value) => {
      // Formatear fecha
      if (!value) return '';
      const date = new Date(value);
      return date.toLocaleDateString('es-MX');
    }
  }
];
</script>
```

## Características Detalladas

### Filtrado Tipo Excel
- Click en el ícono ▼ del header para abrir el dropdown de filtros
- Búsqueda interna dentro del dropdown
- Checkbox "Seleccionar todos" / "Deseleccionar todos"
- Badge con cantidad de filtros activos en cada columna
- Botón "Limpiar filtros" global cuando hay filtros activos
- Los filtros se resetean con el botón de recarga

### Ordenamiento
- Click en el header de la columna para ordenar
- Ciclo: Sin orden → ASC (↑) → DESC (↓) → Sin orden
- Solo una columna puede estar ordenada a la vez
- Valores null/undefined se ponen al final
- Ordenamiento numérico inteligente (1, 2, 10 en lugar de 1, 10, 2)

### Paginación
- Fija de 25 registros por página
- Controles: Primera, Anterior, Números de página, Siguiente, Última
- Máximo 5 números de página visibles con "..." para el resto
- Información: "Mostrando X-Y de Z registros"
- Se resetea a página 1 al filtrar si la página actual deja de existir

### Resincronización
- Botón con ícono ⟳ que llama a la función `onReload` prop
- Muestra spinner mientras recarga
- Resetea filtros, ordenamiento y vuelve a página 1
- Solo visible si se proporciona la prop `onReload`

### Responsividad
- **Desktop (≥768px)**: Tabla HTML con clase `.table`
- **Móvil (<768px)**: Cards con cada campo mostrado como "Label: Valor"
- Las funcionalidades de filtrado, ordenamiento y paginación funcionan igual en ambos modos

## Rendimiento

- Usa `computed` properties para cálculos eficientes
- Pipeline optimizado: filtrado → ordenamiento → paginación
- Renderizado condicional de dropdowns (lazy)
- Soporta propiedades anidadas sin problemas de rendimiento

## Notas Importantes

1. **Keys de Filas**: El componente usa `row.id` como key. Si tus objetos no tienen `id`, se genera una key automática.

2. **Formatters**: Los formatters reciben `(value, row)`, donde `value` es el valor de la columna y `row` es el objeto completo. Útil para formatters que necesitan datos de otras columnas.

3. **Propiedades Anidadas**: Usa notación de punto (ej: `user.role.name`) para acceder a propiedades anidadas.

4. **Filtros y Formatters**: Los filtros usan los valores formateados, no los valores originales. Esto significa que si formateas `1` → `'Activo'`, el filtro mostrará y filtrará por `'Activo'`.

5. **Estilos**: El componente usa la clase `.table` existente del proyecto, por lo que mantiene la consistencia visual con las tablas actuales.

## Solución de Problemas

### Los filtros no muestran valores
- Verifica que la columna tenga `filterable: true` (es el default)
- Verifica que los datos no sean todos `null` o `undefined`

### El ordenamiento no funciona correctamente
- Verifica que la columna tenga `sortable: true` (es el default)
- Para arrays u objetos complejos, marca la columna como `sortable: false`

### La paginación no aparece
- La paginación solo aparece si hay más de 25 registros después de filtrar
- Verifica que tengas suficientes datos

### El botón de recarga no aparece
- Debes pasar la prop `onReload` con una función async

## Migración de Tablas Existentes

### Antes (tabla tradicional)
```vue
<table class="table" v-if="items.length > 0">
  <thead>
    <tr>
      <th>ID</th>
      <th>Nombre</th>
      <!-- ... -->
    </tr>
  </thead>
  <tbody>
    <tr v-for="item in items" :key="item.id">
      <td data-label="ID">{{ item.id }}</td>
      <td data-label="Nombre">{{ item.name }}</td>
      <!-- ... -->
    </tr>
  </tbody>
</table>
```

### Después (con DataTable)
```vue
<DataTable
  :data="items"
  :columns="columns"
  :onReload="loadItems"
>
  <template #actions="{ row }">
    <!-- acciones aquí -->
  </template>
</DataTable>

<script setup>
const columns = [
  { key: 'id', label: 'ID' },
  { key: 'name', label: 'Nombre' }
];
</script>
```

## Contribución

Si encuentras bugs o tienes sugerencias de mejora, contacta al equipo de desarrollo.
