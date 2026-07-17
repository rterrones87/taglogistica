<template>
    <div class="flex items-center mt-2 mb-8">
      <!-- Breadcrumb -->
      <div class="grow">
        <breadcrumb :items="breadcrumbItems" />
      </div>
  
      <!-- User Info -->
      <div class="flex items-center justify-center cursor-pointer" @click="open = !open">
        <img :src="user.avatar" class="rounded-full w-[40px] h-[40px] me-1 me-2 bg-[--primarycolor]"/>
        <div class="text-sm">
          <b>{{ user.name }}</b>
          <small class="block opacity-50">{{ user.role }}</small>
        </div>
        <div class="relative ms-4">
          ▾
          <nav :class="{'hidden': !open}" class="absolute top-[2em] right-0 w-[150px] bg-white shadow-md p-1 z-10">
            <a class="block px-1 m-2 opacity-75 hover:opacity-100">Perfil</a>
            <a class="block px-1 m-2 opacity-75 hover:opacity-100" @click="logout">Cerrar sesión</a>
          </nav>
        </div>
      </div>
    </div>
</template>
  
<script>
  import { ref } from 'vue';
  import breadcrumb from './breadcrumb.vue';

  export default {
    components: {
      breadcrumb
    },
    props: {
      breadcrumbItems: {
        type: Array,
        required: true
      }
    },
    setup() {
      const user = ref({
        name: localStorage.getItem('user_name') || 'Usuario',
        role: localStorage.getItem('user_role') || 'Invitado',
        avatar: localStorage.getItem('user_avatar') || ''
      });
      const open = ref(false);

      const logout = async () => {
        try {
          localStorage.removeItem('token'); 
          window.location.href = '/login';  
        } catch (error) {
          console.error('Error al cerrar sesión:', error);
        }
      };

      return { user, logout, open };
    }
  };

</script>
  
  
  