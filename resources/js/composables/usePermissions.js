import { computed } from 'vue';

/**
 * Composable para verificar permisos del usuario
 * 
 * @returns {Object} Funciones para verificar permisos
 */
export function usePermissions() {
    // Obtener permisos del usuario desde localStorage
    const userPermissions = computed(() => {
        const permissions = localStorage.getItem('user_permissions');
        return permissions ? JSON.parse(permissions) : [];
    });
    
    /**
     * Verifica si el usuario tiene un permiso específico
     * 
     * @param {string} permission - Nombre del permiso (ej: 'services.view')
     * @returns {boolean}
     */
    const hasPermission = (permission) => {
        return userPermissions.value.includes(permission);
    };
    
    /**
     * Verifica si el usuario tiene al menos uno de los permisos dados
     * 
     * @param {Array<string>} permissions - Array de nombres de permisos
     * @returns {boolean}
     */
    const hasAnyPermission = (permissions) => {
        return permissions.some(p => userPermissions.value.includes(p));
    };
    
    /**
     * Verifica si el usuario tiene todos los permisos dados
     * 
     * @param {Array<string>} permissions - Array de nombres de permisos
     * @returns {boolean}
     */
    const hasAllPermissions = (permissions) => {
        return permissions.every(p => userPermissions.value.includes(p));
    };
    
    return {
        userPermissions,
        hasPermission,
        hasAnyPermission,
        hasAllPermissions
    };
}
