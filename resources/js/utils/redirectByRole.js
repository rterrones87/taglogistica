/**
 * Obtiene la ruta inicial según el rol del usuario
 * @param {string} roleName - Nombre del rol del usuario
 * @returns {string} Ruta inicial para el rol
 */
export function getInitialRouteByRole(roleName) {
  if (!roleName) {
    console.warn('No role name provided, redirecting to unauthorized');
    return '/panel/unauthorized';
  }
  
  const roleRoutes = {
    'Administrador': '/panel/approvals',
    'Dirección': '/panel/approvals',
    'Logística': '/panel/services',
    'Operaciones': '/panel/services',
    'Operador': '/panel/services',
    'Documentación': '/panel/services',
    'Mantenimiento': '/panel/maintenances',
    'Tesorería': '/panel/treasury/services',
    'Control de Llantas': '/panel/maintenances',
    'Control de Combustible': '/panel/maintenances',
  };
  
  const route = roleRoutes[roleName];
  
  if (!route) {
    console.warn(`No route defined for role: ${roleName}, redirecting to unauthorized`);
    return '/panel/unauthorized';
  }
  
  return route;
}
