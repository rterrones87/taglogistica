# 📦 TAG Logística - Sistema de Gestión de Viajes

**Versión:** 2.2  
**Última Actualización:** Abril 26, 2026  
**Estado:** 🟢 Activo en Producción  
**URL Producción:** https://sistema.taglogistica.com/

---

## 🎯 Resumen Ejecutivo

TAG Logística es un sistema integral de gestión de servicios logísticos que administra operaciones de transporte de carga (importación, exportación y carga suelta), incluyendo seguimiento de servicios, mantenimiento de unidades, gestión de operadores, inventarios de refacciones, sistema de aprobaciones y módulo completo de tesorería.

---

## 🛠️ Stack Tecnológico

| Capa | Tecnología | Versión |
|------|------------|---------|
| **Backend** | Laravel | 9.19 |
| **Lenguaje** | PHP | ≥ 8.0.2 |
| **Frontend** | Vue | 3.2.25 |
| **Router** | Vue Router | 4.5.0 |
| **Build Tool** | Vite | 3.2.11 |
| **Estilos** | Tailwind CSS | 3.3.3 |
| **HTTP Client** | Axios | 1.8.3 |
| **Autenticación** | Laravel Sanctum | 3.3 |
| **Base de Datos** | MySQL | - |

---

## 📚 Documentación Modular

La documentación del sistema está organizada en módulos funcionales para facilitar la navegación y mantenimiento:

### 📖 Arquitectura y Configuración
- **[arquitectura.md](arquitectura.md)** - Stack tecnológico, estructura de directorios, componentes reutilizables, configuración

### 🔐 Seguridad y Acceso
- **[autenticacion.md](autenticacion.md)** - Sistema de autenticación, roles (7), permisos, middleware, guards

### 🗄️ Base de Datos
- **[base-de-datos.md](base-de-datos.md)** - Schema completo (48 tablas), diagrama ER, relaciones, migraciones

### 🚚 Módulos Funcionales

#### Módulo Principal
- **[modulo-servicios.md](modulo-servicios.md)** - Servicios/Viajes (importación, exportación, carga suelta), estados, subestados, contenedores, costos, gastos

#### Módulos de Gestión
- **[modulo-clientes.md](modulo-clientes.md)** - Gestión de clientes, contactos, contenedores
- **[modulo-operadores.md](modulo-operadores.md)** - Operadores/Choferes, pagos semanales, nóminas, descuentos
- **[modulo-unidades.md](modulo-unidades.md)** - Unidades de transporte, llantas, mantenimiento
- **[modulo-mantenimientos.md](modulo-mantenimientos.md)** - Mantenimientos, inventarios, refacciones, solicitudes
- **[modulo-catalogos.md](modulo-catalogos.md)** - Lugares, casetas, catálogos dinámicos
- **[modulo-destinos-clientes.md](modulo-destinos-clientes.md)** - Tarifas por destino de cliente, cálculo de pagos a operadores

#### Módulos de Control
- **[modulo-aprobaciones.md](modulo-aprobaciones.md)** - Sistema de aprobaciones (diesel, gastos, mantenimientos)
- **[modulo-diesel-extra.md](modulo-diesel-extra.md)** - Solicitud de diesel extra durante viajes activos
- **[modulo-tesoreria.md](modulo-tesoreria.md)** - Órdenes de pago, aplicación de pagos, evidencias

### 🛠️ Desarrollo
- **[guia-desarrollo.md](guia-desarrollo.md)** - Comandos, testing, recomendaciones, historial de cambios

---

## 📊 Estadísticas del Sistema

| Categoría | Cantidad |
|-----------|----------|
| **Controladores** | 21 |
| **Modelos Eloquent** | 40 |
| **Tablas BD** | 48 |
| **Endpoints API** | 65+ |
| **Páginas Vue** | 40+ |
| **Componentes Reutilizables** | 25+ |
| **Rutas Frontend** | 35+ |
| **Roles de Usuario** | 7 |
| **Migraciones** | 49 |
| **Services** | 2 |
| **Traits** | 3 |
| **Helpers** | 1 |

---

## 🎭 Roles del Sistema

1. **Administrador** - Acceso completo, aprobaciones, configuración
2. **Logística** - Gestión de clientes y creación de servicios
3. **Operaciones** - Asignación de operadores/unidades, seguimiento de viajes
4. **Chofer** - Vista de servicios asignados, cambio de subestados
5. **Documentación** - Gestión de costos, gastos, lugares y pagos semanales
6. **Mantenimiento** - Gestión de unidades, mantenimientos, inventarios
7. **Tesorería** - Aplicación de pagos, órdenes de tesorería

---

## 🚀 Características Principales

### Sistema de Servicios
- 3 tipos de operaciones (Importación, Exportación, Carga Suelta)
- 6 estados principales + 18+ subestados
- Generación automática de folios (TAG{AAMMDD}{###})
- Tracking en tiempo real por operador
- Sistema dinámico de roles de operador por tipo de operación (catálogo en BD)
- Visibilidad de servicios para choferes basada en subestados configurados en BD
- Historial completo de cambios
- Evidencias fotográficas

### Sistema de Aprobaciones
- 6 tipos de aprobaciones diferentes
- Trait HasApproval reutilizable
- Snapshots de datos al solicitar
- Callbacks onApproved() y onRejected()
- Notificaciones push (FCM)

### Gestión de Operadores
- Roles de operador dinámicos por tipo de operación (Flete, Entrega de Vacío, Recolección, Ingreso de Lleno)
- Pagos semanales automáticos para operador principal (jueves-miércoles)
- Sistema de descuentos
- Nóminas con PDF
- Vista para choferes

### Gestión de Mantenimientos
- Solicitudes de inventario
- Solicitudes a proveedores
- Generación de folios (MTT{YmdHis})
- Órdenes de tesorería por proveedor

### Tesorería
- Órdenes de servicio y mantenimiento
- Aplicación de pagos
- Exportación Excel y PDF
- Upload de evidencias

### Características Técnicas
- Soft deletes (flag zombie)
- Catálogos dinámicos
- Timezone México (trait HasMexicoTimezone)
- Uppercase automático (trait UppercaseAttributes)
- DataTable avanzado con filtros tipo Excel
- Sistema de permisos granular

---

## 🔗 Enlaces Rápidos

- **API Base URL:** `https://sistema.taglogistica.com/api/`
- **Frontend URL:** `https://sistema.taglogistica.com/`
- **Repositorio Git:** (Branch: `master`)

---

## 📝 Notas Importantes

- El archivo original `context.md` ha sido respaldado como `context_backup_YYYYMMDD_HHMMSS.md`
- Esta documentación modular reemplaza el archivo monolítico anterior (1,614 líneas)
- Para contribuir, actualiza el módulo correspondiente y mantén las referencias cruzadas
- Sigue las convenciones de formato establecidas en cada archivo

---

## 🏗️ Arquitectura General

```
Sistema TAG Logística
├── Módulo Servicios (Core)
│   ├── Importación/Exportación/Carga Suelta
│   ├── Estados y Subestados
│   ├── Contenedores y Destinos
│   └── Costos y Gastos
├── Módulo Aprobaciones
│   ├── Diesel y Gastos (Servicios)
│   └── Mantenimientos
├── Módulo Operadores
│   ├── Gestión de Choferes
│   └── Pagos Semanales
├── Módulo Mantenimientos
│   ├── Unidades
│   ├── Inventarios
│   └── Solicitudes
└── Módulo Tesorería
    ├── Órdenes de Pago
    ├── Aplicación de Pagos
    └── Reportes
```

---

## 📅 Última Actualización

**Fecha:** Enero 23, 2026  
**Tipo:** Reorganización de documentación en módulos funcionales  
**Archivos creados:** 13 archivos .md  
**Objetivo:** Mejorar mantenibilidad y navegación de la documentación

---

**Desarrollado por:** TAG Logística  
**Documentación generada:** Sistema de Análisis de Proyectos AI  
**Versión del Sistema:** 2.1
