import { createRouter, createWebHistory } from 'vue-router';
import { getInitialRouteByRole } from './utils/redirectByRole';

// Importar los componentes de las páginas
import Login from './pages/login.vue';
import Clients from './pages/clients.vue';
import Services from './pages/services.vue';
import Suppliers from './pages/suppliers.vue';
//import Travels from './pages/travels.vue';
import Units from './pages/units.vue';
import Users from './pages/users.vue';
import Operators from './pages/operators.vue';
import Roles from './pages/roles.vue';
import Historical from './pages/historical.vue';
import Inventories from './pages/inventories.vue';
import Tires from './pages/tires.vue';
import Places from './pages/places.vue';
import Booths from './pages/booths.vue';
import Maintenances from './pages/maintenances.vue';
import Layout from './layouts/layout.vue';
// Importar los componentes de los formularios
import Client from './pages/forms/client.vue';
import Service from './pages/forms/service.vue';
import Assing from './pages/forms/assign.vue';
import Supplier from './pages/forms/supplier.vue';
//import Travel from './pages/forms/travel.vue';
import Unit from './pages/forms/unit.vue';
import User from './pages/forms/user.vue';
import Operator from './pages/forms/operator.vue';
import Profile from './pages/forms/profile.vue';
import Inventory from './pages/forms/inventory.vue';
import Tire from './pages/forms/tire.vue';
import Place from './pages/forms/place.vue';
import Booth from './pages/forms/booth.vue';
import Cost from './pages/forms/cost.vue';
import Maintenance from './pages/forms/maintenance.vue';
import Extras from './pages/forms/extras.vue';
import TreasuryServices from './pages/treasury/services.vue';
import TreasuryMaintenances from './pages/treasury/maintenances.vue';
import TreasuryNominas from './pages/treasury/nominas.vue';
import Unauthorized from './pages/unauthorized.vue';
import Approvals from './pages/approvals.vue';
import OperatorPayments from './pages/operator_payments.vue';
import OperatorPayment from './pages/forms/operator_payment.vue';
import Nominas from './pages/nominas.vue';
import DieselCosts from './pages/diesel_costs.vue';
import DieselCost from './pages/forms/diesel_cost.vue';
import ClientPlaces from './pages/client_places.vue';

import Dashboard from './pages/dashboards/dashboard.vue';
import DashboardServices from './pages/dashboards/dashboard_services.vue';
import DashboardMaintenances from './pages/dashboards/dashboard_maintenances.vue';
import DashboardMaintenancesDetails from './pages/dashboards/dashboard_maintenances_details.vue';

// Función para verificar si el usuario está autenticado
const isAuthenticated = () => !!localStorage.getItem('token');

// Función para verificar roles (mantener por compatibilidad temporal)
const hasAuthorization = (roles) => {
   const rol = localStorage.getItem('user_role');
   return roles.includes(rol);
};

// Función para verificar permisos
const hasPermission = (permission) => {
    const permissions = JSON.parse(localStorage.getItem('user_permissions') || '[]');
    if (Array.isArray(permission)) {
        return permission.some(p => permissions.includes(p));
    }
    return permissions.includes(permission);
};

const routes = [
    { 
        path: '/', 
        redirect: () => {
            if (isAuthenticated()) {
                const roleName = localStorage.getItem('user_role');
                return getInitialRouteByRole(roleName);  // Redirigir según el rol
            } else {
                return '/login';  // Si no está autenticado, llevar al login
            }
        }
    },
    { path: '/login', component: Login, beforeEnter: (to, from, next) => {
        if (isAuthenticated()) {
            const roleName = localStorage.getItem('user_role');
            const initialRoute = getInitialRouteByRole(roleName);
            next(initialRoute);  // Redirigir según el rol si ya está autenticado
        } else {
            next();  // Permitir acceso al login si no está autenticado
        }
    }},
    { path: '/logout', redirect: '/login', beforeEnter: (to, from, next) => {
        localStorage.removeItem('token'); // Eliminar token
        next('/login');
    }},
    {
        path: '/panel', // Ruta para módulos protegidos
        component: Layout,
        children: [
            { path: 'unauthorized', component: Unauthorized, meta: {requiresAuth: true} },
            { path: 'clients', component: Clients, meta: { requiresPermission: 'clients.view' }  },
            { path: 'services', component: Services, meta: { requiresPermission: 'services.view' } },
            { path: 'suppliers', component: Suppliers, meta: { requiresPermission: 'suppliers.view' } },
            //{ path: 'travels', component: Travels, meta: { requiresAuth: true } },
            { path: 'units', component: Units, meta: { requiresPermission: 'units.view' } },
            { path: 'users', component: Users, meta: { requiresPermission: 'users.view' } },
            { path: 'roles', component: Roles, meta: { requiresPermission: 'roles.manage_permissions' } },
            { path: 'operators', component: Operators, meta: { requiresPermission: 'operators.view' } },
            { path: 'booths', component: Booths, meta: { requiresPermission: 'booths.view' } },
            { path: 'places', component: Places, meta: { requiresPermission: 'places.view' } },
            { path: 'maintenances', component: Maintenances, meta: { requiresPermission: 'maintenances.view' } },
            { path: 'approvals', component: Approvals, meta: { requiresPermission: 'approvals.view' } },
            { path: 'client/:id?', component: Client, meta: { requiresPermission: ['clients.view', 'clients.create', 'clients.edit'] } },
            { 
                path: 'service/:id?', 
                component: () => {
                    const permissions = JSON.parse(localStorage.getItem('user_permissions') || '[]');
                    // Si tiene services.assign o services.assign_diesel, cargar Assign
                    if (permissions.includes('services.assign') || permissions.includes('services.assign_diesel')) {
                        return Promise.resolve(Assing); 
                    } else {
                        return Promise.resolve(Service);
                    }
                }, 
                meta: { requiresPermission: ['services.view', 'services.create', 'services.edit', 'services.assign', 'services.assign_diesel'] } 
            },
            { path: 'service/historical/:id?', component: Historical, meta: { requiresPermission: 'services.view' }  },
            { path: 'inventories', component: Inventories, meta: {requiresPermission: 'inventories.view'} },
            { path: 'tires', component: Tires, meta: {requiresPermission: 'tires.view'} },
            { path: 'supplier/:id?', component: Supplier, meta: { requiresPermission: ['suppliers.view', 'suppliers.create', 'suppliers.edit'] } },
            //{ path: 'travel/:id?', component: Travel, meta: { requiresAuth: true } },
            { path: 'unit/:id?', component: Unit, meta: { requiresPermission: ['units.view', 'units.create', 'units.edit'] } },
            { path: 'user/:id?', component: User, meta: { requiresPermission: ['users.view', 'users.create', 'users.edit'] } },
            { path: 'operator/:id?', component: Operator, meta: { requiresPermission: ['operators.view', 'operators.create', 'operators.edit'] } },
            { path: 'profile/:id?', component: Profile, meta: { requiresPermission: 'users.change_password' } },
            { path: 'inventory/:id?', component: Inventory, meta: { requiresPermission: ['inventories.view', 'inventories.create', 'inventories.edit'] } },
            { path: 'tire/:id?', component: Tire, meta: { requiresPermission: ['tires.view', 'tires.create', 'tires.edit'] } },
            { path: 'place/:id?', component: Place, meta: { requiresPermission: ['places.view', 'places.create', 'places.edit'] } },
            { path: 'booth/:id?', component: Booth, meta: { requiresPermission: ['booths.view', 'booths.create', 'booths.edit'] } },
            { path: 'cost/:id?', component: Cost, meta: { requiresPermission: ['costs.view', 'costs.edit'] } },
            { path: 'extras/:id?', component: Extras, meta: { requiresPermission: ['expenses.view', 'expenses.edit'] } },
            { path: 'maintenance/:id?', component: Maintenance, meta: { requiresPermission: ['maintenances.view', 'maintenances.create', 'maintenances.edit'] } },
            { path: 'treasury/services', component: TreasuryServices, meta: { requiresPermission: 'treasury.view_services' } },
            { path: 'treasury/maintenances', component: TreasuryMaintenances, meta: { requiresPermission: 'treasury.view_maintenances' } },
            { path: 'treasury/nominas', component: TreasuryNominas, meta: { requiresPermission: 'treasury.view_payments' } },
            { path: 'operators/payments', component: OperatorPayments, meta : { requiresPermission: 'operator_payments.view' } },
            { path: 'operators/operator_payment/:id?', component: OperatorPayment, meta : { requiresPermission: ['operator_payments.view', 'operator_payments.create', 'operator_payments.edit'] } },
            { path: 'nominas', component: Nominas, meta : { requiresPermission: 'operators.view_payments' } },
            { path: 'dashboard/services', component: Dashboard, meta: { requiresPermission: 'dashboard.view_services' }  },
            { path: 'dashboard/services-details', component: DashboardServices, meta: { requiresPermission: 'dashboard.view_services' } },
            { path: 'dashboard/maintenances', component: DashboardMaintenances, meta: { requiresPermission: 'dashboard.view_maintenances' } },
            { path: 'dashboard/maintenances-details', component: DashboardMaintenancesDetails, meta: {requiresAuth: true} },
            { path: 'diesel_costs', component: DieselCosts, meta : { requiresPermission: 'diesel_costs.view' } },
            { path: 'diesel_cost', component: DieselCost, meta : { requiresPermission: ['diesel_costs.view', 'diesel_costs.create', 'diesel_costs.edit'] } },
            { path: 'client-places', component: ClientPlaces, meta: { requiresPermission: 'client_places.view' } },
        ]
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Proteger rutas privadas
router.beforeEach((to, from, next) => {

    //Validación general de login
    if (to.meta.requiresAuth || to.meta.requiresPermission || to.meta.requiresRole) {
        if (!isAuthenticated()) {
          return next('/login')
        }
    }

    // Validación de permisos (prioridad)
    if (to.meta.requiresPermission) {
        const required = Array.isArray(to.meta.requiresPermission)
            ? to.meta.requiresPermission
            : [to.meta.requiresPermission];
        
        if (!hasPermission(required)) {
            return next('/panel/unauthorized');
        }
    }
    // Validación de roles (compatibilidad temporal)
    else if (to.meta.requiresRole) {
        const allowedRoles = to.meta.requiresRole
        if (!hasAuthorization(allowedRoles)) {
          return next('/panel/unauthorized')
        }
    }

    next()
});


export default router;

