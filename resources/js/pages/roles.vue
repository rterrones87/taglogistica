<template>
    <breadcrumb :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 rounded shadow-md">
        <div class="flex items-center flex-col md:flex-row">
            <h2 class="text-3xl my-4 font-bold grow text-center md:text-left">Gestión de Permisos</h2>
            <div class="my-2 flex justify-end gap-2">
                <GenericAction
                    title="Nuevo rol"
                    icon="add.png"
                    @click="openNewRoleModal"
                />
            </div>
        </div>

        <!-- Selector de Rol -->
        <div class="mb-6">
            <label class="block text-sm font-bold mb-2">Selecciona un Rol</label>
            <select 
                v-model="selectedRoleId" 
                @change="loadRolePermissions"
                class="w-full md:w-1/2 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <option value="">-- Seleccione un rol --</option>
                <option v-for="role in roles" :key="role.id" :value="role.id">
                    {{ role.name }}
                </option>
            </select>
        </div>

        <!-- Permisos (solo se muestran si hay un rol seleccionado) -->
        <div v-if="selectedRoleId" class="mt-6">
            <h3 class="text-xl font-bold mb-4">Permisos Disponibles</h3>
            
            <!-- Loading spinner -->
            <div v-if="loading" class="flex justify-center my-8">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-gray-900"></div>
            </div>

            <!-- Grid de permisos en 3 columnas -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div 
                    v-for="(permissions, module) in groupedPermissions" 
                    :key="module"
                    class="bg-gray-50 p-4 rounded-lg border border-gray-200"
                >
                    <h4 class="font-bold text-lg mb-3 text-gray-800 capitalize">
                        {{ formatModuleName(module) }}
                    </h4>
                    <div v-for="perm in permissions" :key="perm.id" class="mb-2">
                        <label class="flex items-center cursor-pointer hover:bg-gray-100 p-1 rounded">
                            <input 
                                type="checkbox" 
                                :value="perm.id"
                                v-model="selectedPermissions"
                                class="mr-2 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            />
                            <span class="text-sm">{{ formatActionName(perm.action) }}</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Botón Guardar -->
            <div class="mt-6 flex justify-end">
                <GenericAction
                    title="Guardar Cambios"
                    icon="save.png"
                    @click="savePermissions"
                />
            </div>
        </div>

        <!-- Mensaje cuando no hay rol seleccionado -->
        <div v-else class="text-center text-gray-500 my-8">
            <p>Selecciona un rol para gestionar sus permisos</p>
        </div>
    </div>

    <!-- Modal de Nuevo Rol -->
    <BaseModal
        :show="showNewRoleModal"
        title="Nuevo Rol"
        @close="closeNewRoleModal"
    >
                <div class="form-item">
                    <label>Nombre del rol:</label>
                    <input v-model="newRoleForm.name" type="text" required placeholder="Ingrese el nombre del rol" />
                </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <button @click="closeNewRoleModal" class="form-button bg-[#6e7881]">Cancelar</button>
                <button @click="saveNewRole" class="form-button">Guardar</button>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import { ref, computed, onMounted, inject } from 'vue';
import axios from 'axios';
import breadcrumb from '../components/breadcrumb.vue';
import GenericAction from '../components/GenericAction.vue';
import BaseModal from '../components/BaseModal.vue';

const dialogs = inject("swal");

// Estado reactivo
const roles = ref([]);
const allPermissions = ref([]);
const selectedRoleId = ref('');
const selectedPermissions = ref([]);
const loading = ref(false);
const showNewRoleModal = ref(false);
const newRoleForm = ref({ name: '' });

// Breadcrumb
const breadcrumbItems = [
    { title: 'Gestión de Permisos' }
];

// Permisos agrupados por módulo
const groupedPermissions = computed(() => {
    if (!Array.isArray(allPermissions.value) || allPermissions.value.length === 0) return {};
    
    const grouped = {};
    allPermissions.value.forEach(perm => {
        const [module, action] = perm.name.split('.');
        if (!grouped[module]) {
            grouped[module] = [];
        }
        grouped[module].push({
            id: perm.id,
            name: perm.name,
            action: action || perm.name
        });
    });
    
    return grouped;
});

// Cargar roles
const loadRoles = async () => {
    try {
        const response = await axios.get('/roles');
        roles.value = Array.isArray(response.data) ? response.data : [];
    } catch (error) {
        console.error('Error cargando roles:', error);
        dialogs.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudieron cargar los roles'
        });
    }
};

// Cargar todos los permisos
const loadAllPermissions = async () => {
    try {
        const response = await axios.get('/roles/permissions');
        allPermissions.value = Array.isArray(response.data) ? response.data : [];
    } catch (error) {
        console.error('Error cargando permisos:', error);
        dialogs.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudieron cargar los permisos'
        });
    }
};

// Cargar permisos del rol seleccionado
const loadRolePermissions = async () => {
    if (!selectedRoleId.value) {
        selectedPermissions.value = [];
        return;
    }

    loading.value = true;
    try {
        const response = await axios.get(`/roles/${selectedRoleId.value}/permissions`);
        selectedPermissions.value = Array.isArray(response.data.permission_ids) 
            ? response.data.permission_ids 
            : [];
    } catch (error) {
        console.error('Error cargando permisos del rol:', error);
        dialogs.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudieron cargar los permisos del rol'
        });
    } finally {
        loading.value = false;
    }
};

// Guardar permisos
const savePermissions = async () => {
    if (selectedPermissions.value.length === 0) {
        dialogs.fire({
            icon: 'warning',
            title: 'Advertencia',
            text: 'Debes seleccionar al menos un permiso'
        });
        return;
    }

    const result = await dialogs.fire({
        title: '¿Guardar cambios?',
        text: 'Se actualizarán los permisos del rol seleccionado',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, guardar',
        cancelButtonText: 'Cancelar'
    });

    if (!result.isConfirmed) return;

    loading.value = true;
    try {
        await axios.put(`/roles/${selectedRoleId.value}/permissions`, {
            permission_ids: selectedPermissions.value
        });

        dialogs.fire({
            icon: 'success',
            title: 'Éxito',
            text: 'Permisos actualizados correctamente'
        });
    } catch (error) {
        dialogs.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.error || 'No se pudieron actualizar los permisos'
        });
    } finally {
        loading.value = false;
    }
};

// Formatear nombre del módulo
const formatModuleName = (module) => {
    const translations = {
        'clients': 'Clientes',
        'suppliers': 'Proveedores',
        'units': 'Unidades',
        'places': 'Destinos',
        'users': 'Usuarios',
        'booths': 'Casetas',
        'services': 'Viajes',
        'operators': 'Operadores',
        'operator_payments': 'Pagos Operadores',
        'inventories': 'Inventarios',
        'tires': 'Llantas',
        'costs': 'Costos',
        'expenses': 'Gastos',
        'maintenances': 'Mantenimientos',
        'treasury': 'Tesorería',
        'approvals': 'Aprobaciones',
        'diesel_costs': 'Costos Diesel',
        'dashboard': 'Dashboard',
        'roles': 'Roles',
        'client_places': 'Control de rutas'
    };
    return translations[module] || module;
};

// Formatear nombre de acción
const formatActionName = (action) => {
    const translations = {
        'view': 'Ver',
        'create': 'Crear',
        'edit': 'Editar',
        'delete': 'Eliminar',
        'assign': 'Asignar',
        'cancel': 'Cancelar',
        'reassign': 'Reasignar',
        'request_diesel': 'Solicitar Diesel',
        'request_booth': 'Solicitar Caseta',
        'change_substate': 'Cambiar Subestado',
        'download': 'Descargar',
        'view_payments': 'Ver Pagos',
        'change_state': 'Cambiar Estado',
        'view_maintenances': 'Ver Mantenimientos',
        'view_services': 'Ver Viajes',
        'apply_payment': 'Aplicar Pago',
        'init_expenses': 'Gastos Iniciales',
        'ext_expenses': 'Gastos Extras',
        'upload_evidence': 'Subir Evidencia',
        'approve': 'Aprobar',
        'reject': 'Rechazar',
        'change_password': 'Cambiar Contraseña',
        'manage_permissions': 'Gestionar Permisos',
        'commission': 'Comisión',
        'consult': 'Consultar',
        'assign_diesel': 'Asignar solo diesel inicial'
    };
    return translations[action] || action;
};

// Abrir modal de nuevo rol
const openNewRoleModal = () => {
    newRoleForm.value = { name: '' };
    showNewRoleModal.value = true;
};

// Cerrar modal de nuevo rol
const closeNewRoleModal = () => {
    showNewRoleModal.value = false;
    newRoleForm.value = { name: '' };
};

// Guardar nuevo rol
const saveNewRole = async () => {
    if (!newRoleForm.value.name || newRoleForm.value.name.trim() === '') {
        dialogs.fire({
            icon: 'warning',
            title: 'Advertencia',
            text: 'El nombre del rol es obligatorio'
        });
        return;
    }

    loading.value = true;
    try {
        const { data } = await axios.post('/roles', {
            name: newRoleForm.value.name
        });

        // Agregar el nuevo rol a la lista
        roles.value.push(data.role);

        closeNewRoleModal();

        await dialogs.fire({
            icon: 'success',
            title: 'Éxito',
            text: 'Rol creado correctamente'
        });

        // Recargar la página después de confirmar
        window.location.reload();
    } catch (error) {
        const errorMessage = error.response?.data?.messages?.name?.[0] 
            || error.response?.data?.error 
            || 'No se pudo crear el rol';

        dialogs.fire({
            icon: 'error',
            title: 'Error',
            text: errorMessage
        });
    } finally {
        loading.value = false;
    }
};

// Cargar datos iniciales
onMounted(() => {
    loadRoles();
    loadAllPermissions();
});
</script>
