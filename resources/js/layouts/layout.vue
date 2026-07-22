<template>
    <div class="flex w-full h-full">
        
        <aside class="sidebar overflow-y-auto min-w-[240px] z-40" :style="{ left: sidebarLeft }">
            <div class="p-1 bg-[var(--primarycolor)] text-white lg:justify-center flex">
                <button class="menu-app" @click="toggleSidebar"></button>
                <span class="logotype"></span>
            </div>
            <nav>
                <ul>
                    <li v-for="(item, index) in menuItems" :key="index" :class="{ active: isActive(item) }">
                        <!-- Si tiene submenú, muestra el botón para expandir -->
                        <template v-if="item.subMenu">
                            <button @click="toggleSubMenu(index)">
                                <template v-if="item.icon">
                                    <span class="me-2" v-html="getIcon(item.icon)"></span>
                                </template>
                                {{ item.name }}
                            </button>
                            <ul class="mt-1" v-if="openSubMenu === index">
                                <li class="px-2 py-1" v-for="(subItem, subIndex) in item.subMenu" :key="subIndex" :class="{ active: isActive(subItem) }">
                                    <router-link :to="subItem.path" @click="toggleSidebar">{{ subItem.name }}</router-link>
                                </li>
                            </ul>
                        </template>

                        <!-- Si no tiene submenú, solo muestra el enlace normal -->
                        <template v-else>
                            <router-link :to="item.path" @click="toggleSidebar">
                              <template v-if="item.icon">
                                  <span class="me-2" v-html="getIcon(item.icon)"></span>
                              </template>
                              {{ item.name }}
                            </router-link>
                        </template>
                    </li>
                </ul>
            </nav>
        </aside>
        <main class="content md:overflow-auto">
            <div class="px-4 py-1 bg-[var(--secondarycolor)] flex items-center z-40 sticky top-0 left-0">
                <div class="grow">
                    <button class="menu-app" @click="toggleSidebar"></button>  
                </div>
                <div>
                    <Dropdown align="right" width="48">
                      <template #trigger>
                          <span class="inline-flex rounded-md">
                              <button
                                  type="button"
                                  class="inline-flex items-center px-3 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white/10 hover:text-gray-700 focus:outline-none transition ease-in-out duration-150"
                              >
                                  <img :src="user.avatar" class="rounded-full w-[40px] h-[40px] me-1 me-2 bg-[--primarycolor] object-cover"/>
                                  <div>
                                    <h3 class="block text-white text-right">{{ user.role }}</h3>
                                    <small class="block text-white opacity-80">{{ user.name }}</small>
                                  </div>
                                  <img src="/public/img/down.png" class="ml-3 w-6 h-6 invert"/>

                              </button>
                          </span>
                      </template>

                      <template #content>
                          <DropdownLink class="text-left" @click="profilePath" as="button"> Mi cuenta </DropdownLink>
                          <DropdownLink class="text-left" @click="logout" method="post" as="button">
                              Cerrar sesión
                          </DropdownLink>
                      </template>
                  </Dropdown>
                    
                </div>
            </div>
            <router-view></router-view>
        </main>
        
    </div>
</template>

<script setup>
import { useRoute, useRouter } from 'vue-router';
import { ref } from 'vue';
import Dropdown from '@/components/Dropdown.vue';
import DropdownLink from '@/components/DropdownLink.vue';
import { usePermissions } from '@/composables/usePermissions';

const route = useRoute();
const router = useRouter();

// Composable de permisos
const { hasPermission, hasAnyPermission } = usePermissions();

// Estado para controlar qué submenú está abierto
const openSubMenu = ref(null);
const open = ref(false);
const sidebarLeft = ref('-100%');


// Menú con submenús
const rawMenuItems = [
  { 
    icon: "dashboard", 
    name: "Dashboard", 
    requiresAnyPermission: ['dashboard.view_services', 'dashboard.view_maintenances'],
    subMenu: [
        { 
            name: "Gráfico Viajes", 
            path: "/panel/dashboard/services",
            requiresPermission: 'dashboard.view_services'
        },
        { 
            name: "Detalles Viajes", 
            path: "/panel/dashboard/services-details",
            requiresPermission: 'dashboard.view_services'
        },
        { 
            name: "Gráfico Manttos", 
            path: "/panel/dashboard/maintenances",
            requiresPermission: 'dashboard.view_maintenances'
        },
        { 
            name: "Detalles Manttos", 
            path: "/panel/dashboard/maintenances-details",
            requiresPermission: 'dashboard.view_maintenances'
        }
    ]
  },
  { 
    icon: "document", 
    name: "Viajes", 
    path: "/panel/services", 
    requiresPermission: 'services.view'
  },
  { 
    icon: "money", 
    name: "Pago Operadores", 
    path: "/panel/operators/payments",
    requiresPermission: 'operator_payments.view'
  },
  { 
    icon: "money", 
    name: "Mis nominas", 
    path: "/panel/nominas",
    requiresPermission: 'operators.view_payments'
  },
  { 
    icon: "mantto", 
    name: "Mantenimientos", 
    requiresAnyPermission: ['maintenances.view', 'suppliers.view', 'units.view'],
    subMenu: [
        { 
            name: "Mantenimientos", 
            path: "/panel/maintenances",
            requiresPermission: 'maintenances.view'
        },
        { 
            name: "Proveedores", 
            path: "/panel/suppliers",
            requiresPermission: 'suppliers.view'
        },
        { 
            name: "Unidades", 
            path: "/panel/units",
            requiresPermission: 'units.view'
        },
    ]
  },
  { 
    icon: "money", 
    name: "Tesorería", 
    requiresAnyPermission: ['treasury.view_services', 'treasury.view_maintenances', 'treasury.view_payments'],
    subMenu: [
      { 
        name: "Viajes", 
        path: "/panel/treasury/services",
        requiresPermission: 'treasury.view_services'
      },
      { 
        name: "Mantenimientos", 
        path: "/panel/treasury/maintenances",
        requiresPermission: 'treasury.view_maintenances'
      },
      { 
        name: "Nóminas", 
        path: "/panel/treasury/nominas",
        requiresPermission: 'treasury.view_payments'
      }
    ] 
  },
  { 
    icon: "inventory", 
    name: "Inventario", 
    path: "/panel/inventories",
    requiresPermission: 'inventories.view'
  },
  { 
    icon: "build", 
    name: "Llantas", 
    path: "/panel/tires",
    requiresPermission: 'tires.view'
  },
  { 
    icon: "notify", 
    name: "Notificaciones", 
    path: "/panel/approvals",
    requiresPermission: 'approvals.view'
  },
  { 
    icon: "settings", 
    name: "Sistema",
    requiresAnyPermission: ['users.view', 'clients.view', 'suppliers.view', 'units.view', 'places.view', 'booths.view', 'diesel_costs.view', 'roles.view', 'client_places.view'],
    subMenu: [
        { 
            name: "Usuarios", 
            path: "/panel/users",
            requiresPermission: 'users.view'
        },
        { 
            name: "Clientes", 
            path: "/panel/clients",
            requiresPermission: 'clients.view'
        },
        { 
            name: "Proveedores", 
            path: "/panel/suppliers",
            requiresPermission: 'suppliers.view'
        },
        { 
            name: "Unidades", 
            path: "/panel/units",
            requiresPermission: 'units.view'
        },
        { 
            name: "Destinos", 
            path: "/panel/places",
            requiresPermission: 'places.view'
        },
        { 
            name: "Casetas", 
            path: "/panel/booths",
            requiresPermission: 'booths.view'
        },
        { 
            name: "Diesel", 
            path: "/panel/diesel_costs",
            requiresPermission: 'diesel_costs.view'
        },
        { 
            name: "Roles", 
            path: "/panel/roles",
            requiresPermission: 'roles.view'
        },
        {
            name: "Control de rutas",
            path: "/panel/client-places",
            requiresPermission: 'client_places.view'
        }
    ] 
  }
];

function filterMenuByPermissions(menuItems) {
  return menuItems
    .map(item => {
      // Verificar si tiene submenú, y filtrarlo también recursivamente
      if (item.subMenu) {
        item.subMenu = filterMenuByPermissions(item.subMenu)
      }

      // Verificar permisos
      let hasRequiredPermission = true;
      
      if (item.requiresPermission) {
        // Permiso único
        hasRequiredPermission = hasPermission(item.requiresPermission)
      } else if (item.requiresAnyPermission) {
        // Múltiples permisos (al menos uno)
        hasRequiredPermission = hasAnyPermission(item.requiresAnyPermission)
      }

      // Mostrar solo si tiene permiso directo
      // Si tiene submenú, también verificar que tenga al menos un elemento visible
      if (hasRequiredPermission) {
        // Si tiene submenú pero está vacío después del filtrado, no mostrarlo
        if (item.subMenu && item.subMenu.length === 0) {
          return null
        }
        return item
      }

      return null
    })
    .filter(Boolean) // Eliminar los null
}

const filteredMenu = filterMenuByPermissions(rawMenuItems)

const menuItems = ref(filteredMenu);

const user = ref({
    name: localStorage.getItem('user_id') || '',
    name: localStorage.getItem('user_name') || 'Usuario',
    role: localStorage.getItem('user_role') || 'Invitado',
    avatar: localStorage.getItem('user_avatar') || ''
});

const profilePath = () => {
    router.push({
        path: "/panel/profile/" + localStorage.getItem('user_id')
    });
}

const logout = async () => {
    try {
        localStorage.removeItem('token'); 
        localStorage.removeItem('user_id');
        localStorage.removeItem('user_name'); 
        localStorage.removeItem('user_role'); 
        localStorage.removeItem('user_avatar');
        localStorage.removeItem('user_permissions'); // Limpiar permisos
        window.location.href = '/login';  
    } catch (error) {
        console.error('Error al cerrar sesión:', error);
    }
};

// Función para alternar el submenú
const toggleSubMenu = (index) => {
    openSubMenu.value = openSubMenu.value === index ? null : index;
};

//Función para alternar el menu
const toggleSidebar = () => {
    sidebarLeft.value = (sidebarLeft.value === '0%') ? '-100%' : '0%';
}

// Función para determinar si un item es "activo"
const isActive = (item) => { 
    let current = route.path;

    if(current == '/panel/user') current = '/panel/users';
    if(current == '/panel/unit') current = '/panel/units';
    if(current == '/panel/supplier') current = '/panel/suppliers';
    if(current == '/panel/client') current = '/panel/clients';
    if(current == '/panel/service') current = '/panel/services';
    if(current == '/panel/tire') current = '/panel/tires';

    if (item.path) {
        return current === item.path;
    }
    if (item.subMenu) {
        //return item.subMenu.some((sub) => route.path.startsWith(sub.path));
        return item.subMenu.some((sub) => current == sub.path);
    }
    return false;
};

const getIcon = (name) => {

    const icons = {
        'dashboard' : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960"><path d="M80-120v-80h800v80H80Zm40-120v-280h120v280H120Zm200 0v-480h120v480H320Zm200 0v-360h120v360H520Zm200 0v-600h120v600H720Z"/></svg>',
        'document' : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960"><path d="M320-240h320v-80H320v80Zm0-160h320v-80H320v80ZM240-80q-33 0-56.5-23.5T160-160v-640q0-33 23.5-56.5T240-880h320l240 240v480q0 33-23.5 56.5T720-80H240Zm280-520v-200H240v640h480v-440H520ZM240-800v200-200 640-640Z"/></svg>',
        'build' : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960"><path d="M686-132 444-376q-20 8-40.5 12t-43.5 4q-100 0-170-70t-70-170q0-36 10-68.5t28-61.5l146 146 72-72-146-146q29-18 61.5-28t68.5-10q100 0 170 70t70 170q0 23-4 43.5T584-516l244 242q12 12 12 29t-12 29l-84 84q-12 12-29 12t-29-12Zm29-85 27-27-256-256q18-20 26-46.5t8-53.5q0-60-38.5-104.5T386-758l74 74q12 12 12 28t-12 28L332-500q-12 12-28 12t-28-12l-74-74q9 57 53.5 95.5T360-440q26 0 52-8t47-25l256 256ZM472-488Z"/></svg>',
        'inventory' : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960"><path d="M200-80q-33 0-56.5-23.5T120-160v-451q-18-11-29-28.5T80-680v-120q0-33 23.5-56.5T160-880h640q33 0 56.5 23.5T880-800v120q0 23-11 40.5T840-611v451q0 33-23.5 56.5T760-80H200Zm0-520v440h560v-440H200Zm-40-80h640v-120H160v120Zm200 280h240v-80H360v80Zm120 20Z"/></svg>',
        'money' : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960"><path d="M441-120v-86q-53-12-91.5-46T293-348l74-30q15 48 44.5 73t77.5 25q41 0 69.5-18.5T587-356q0-35-22-55.5T463-458q-86-27-118-64.5T313-614q0-65 42-101t86-41v-84h80v84q50 8 82.5 36.5T651-650l-74 32q-12-32-34-48t-60-16q-44 0-67 19.5T393-614q0 33 30 52t104 40q69 20 104.5 63.5T667-358q0 71-42 108t-104 46v84h-80Z"/></svg>',
        'settings' : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960"><path d="m370-80-16-128q-13-5-24.5-12T307-235l-119 50L78-375l103-78q-1-7-1-13.5v-27q0-6.5 1-13.5L78-585l110-190 119 50q11-8 23-15t24-12l16-128h220l16 128q13 5 24.5 12t22.5 15l119-50 110 190-103 78q1 7 1 13.5v27q0 6.5-2 13.5l103 78-110 190-118-50q-11 8-23 15t-24 12L590-80H370Zm70-80h79l14-106q31-8 57.5-23.5T639-327l99 41 39-68-86-65q5-14 7-29.5t2-31.5q0-16-2-31.5t-7-29.5l86-65-39-68-99 42q-22-23-48.5-38.5T533-694l-13-106h-79l-14 106q-31 8-57.5 23.5T321-633l-99-41-39 68 86 64q-5 15-7 30t-2 32q0 16 2 31t7 30l-86 65 39 68 99-42q22 23 48.5 38.5T427-266l13 106Zm42-180q58 0 99-41t41-99q0-58-41-99t-99-41q-59 0-99.5 41T342-480q0 58 40.5 99t99.5 41Zm-2-140Z"/></svg>',
        'mantto' : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960"><path d="M42-120v-112q0-33 17-62t47-44q51-26 115-44t141-18q77 0 141 18t115 44q30 15 47 44t17 62v112H42Zm80-80h480v-32q0-11-5.5-20T582-266q-36-18-92.5-36T362-320q-71 0-127.5 18T142-266q-9 5-14.5 14t-5.5 20v32Zm240-240q-66 0-113-47t-47-113h-10q-9 0-14.5-5.5T172-620q0-9 5.5-14.5T192-640h10q0-45 22-81t58-57v38q0 9 5.5 14.5T302-720q9 0 14.5-5.5T322-740v-54q9-3 19-4.5t21-1.5q11 0 21 1.5t19 4.5v54q0 9 5.5 14.5T422-720q9 0 14.5-5.5T442-740v-38q36 21 58 57t22 81h10q9 0 14.5 5.5T552-620q0 9-5.5 14.5T532-600h-10q0 66-47 113t-113 47Zm0-80q33 0 56.5-23.5T442-600H282q0 33 23.5 56.5T362-520Zm300 160-6-30q-6-2-11.5-4.5T634-402l-28 10-20-36 22-20v-24l-22-20 20-36 28 10q4-4 10-7t12-5l6-30h40l6 30q6 2 12 5t10 7l28-10 20 36-22 20v24l22 20-20 36-28-10q-5 5-10.5 7.5T708-390l-6 30h-40Zm20-70q12 0 21-9t9-21q0-12-9-21t-21-9q-12 0-21 9t-9 21q0 12 9 21t21 9Zm72-130-8-42q-9-3-16.5-7.5T716-620l-42 14-28-48 34-30q-2-5-2-8v-16q0-3 2-8l-34-30 28-48 42 14q6-6 13.5-10.5T746-798l8-42h56l8 42q9 3 16.5 7.5T848-780l42-14 28 48-34 30q2 5 2 8v16q0 3-2 8l34 30-28 48-42-14q-6 6-13.5 10.5T818-602l-8 42h-56Zm28-90q21 0 35.5-14.5T832-700q0-21-14.5-35.5T782-750q-21 0-35.5 14.5T732-700q0 21 14.5 35.5T782-650ZM362-200Z"/></svg>',
        'driver' : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960"><path d="M120-160v-112q0-34 17.5-62.5T184-378q62-31 126-46.5T440-440q20 0 40 1.5t40 4.5q-4 58 21 109.5t73 84.5v80H120ZM760-40l-60-60v-186q-44-13-72-49.5T600-420q0-58 41-99t99-41q58 0 99 41t41 99q0 45-25.5 80T790-290l50 50-60 60 60 60-80 80ZM440-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47Zm300 80q17 0 28.5-11.5T780-440q0-17-11.5-28.5T740-480q-17 0-28.5 11.5T700-440q0 17 11.5 28.5T740-400Z"/></svg>',
        'notify' : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960"><path d="M160-200v-80h80v-280q0-83 50-147.5T420-792v-28q0-25 17.5-42.5T480-880q25 0 42.5 17.5T540-820v28q80 20 130 84.5T720-560v280h80v80H160Zm320-300Zm0 420q-33 0-56.5-23.5T400-160h160q0 33-23.5 56.5T480-80ZM320-280h320v-280q0-66-47-113t-113-47q-66 0-113 47t-47 113v280Z"/></svg>'
    };

    return icons[name];
}

</script>





 

  
  
  