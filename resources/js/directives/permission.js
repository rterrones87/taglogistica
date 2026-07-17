/**
 * Directiva Vue para ocultar/mostrar elementos basados en permisos del usuario
 * 
 * Uso:
 * - Single permission: v-permission="'services.create'"
 * - Multiple permissions (OR): v-permission="['services.edit', 'services.delete']"
 */
export default {
    mounted(el, binding) {
        const permissions = JSON.parse(
            localStorage.getItem('user_permissions') || '[]'
        );
        
        // Convertir el valor a array si es un string
        const requiredPermissions = Array.isArray(binding.value) 
            ? binding.value 
            : [binding.value];
        
        // Verificar si tiene al menos uno de los permisos (OR)
        const hasPermission = requiredPermissions.some(p => 
            permissions.includes(p)
        );
        
        if (!hasPermission) {
            // Ocultar el elemento si no tiene permiso
            el.style.display = 'none';
        }
    },
    
    updated(el, binding) {
        // Actualizar visibilidad si cambian los permisos
        const permissions = JSON.parse(
            localStorage.getItem('user_permissions') || '[]'
        );
        
        const requiredPermissions = Array.isArray(binding.value) 
            ? binding.value 
            : [binding.value];
        
        const hasPermission = requiredPermissions.some(p => 
            permissions.includes(p)
        );
        
        el.style.display = hasPermission ? '' : 'none';
    }
};
