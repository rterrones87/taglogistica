<template>

    <div class="fixed top-0 left-0 w-full h-full flex items-center justify-center">
        <div class="bg-white rounded shadow-md w-[80%] max-w-[600px] flex justify-center overflow-hidden">
            <div class="grow bg-[var(--primarycolor)] hidden md:block">

            </div>
            <div class="p-4">
                <h2 class="text-3xl my-4 font-bold">Iniciar Sesión</h2>
                <form @submit.prevent="login">
                    <label class="form-item">
                        <span>Correo</span>
                        <input type="email" v-model="email" placeholder="Correo" required>
                    </label>
                    <label class="form-item">
                        <span>Contraeña</span>
                        <input type="password" v-model="password" placeholder="Contraseña" required>
                    </label>
                    <div class="flex justify-end">
                        <FormAction
                            title="Ingresar"
                        />
                    </div>
                </form>
            </div>
        </div>
    </div>
    
</template>

<script setup>
import { ref, inject } from 'vue';
import { useRouter } from 'vue-router';
import FormAction from '@/components/FormAction.vue';
import { getInitialRouteByRole } from '../utils/redirectByRole';

import axios from 'axios';

const email = ref('');
const password = ref('');
const router = useRouter();

const dialogs = inject("swal");

const login = async () => {

    dialogs.fire({
        title: "Procesando...",
        text: "Por favor, espere",
        allowOutsideClick: false,
        didOpen: () => {
            dialogs.showLoading();
        }
    });
    
    try {
        const response = await axios.post('login', {
            email: email.value,
            password: password.value,
        });

        dialogs.close();
        
        localStorage.setItem('token', response.data.token); // Guardar token
        localStorage.setItem('user_id', response.data.user.id);
        localStorage.setItem('user_name', response.data.user.name);
        localStorage.setItem('user_role', response.data.user.role.name);
        localStorage.setItem('user_avatar', response.data.user.picture);
        
        // Guardar permisos del usuario
        localStorage.setItem('user_permissions', JSON.stringify(response.data.permissions || []));

        notifyAppUserId(response.data.user.id);

        // Redirigir según el rol del usuario
        const initialRoute = getInitialRouteByRole(response.data.user.role.name);
        router.push(initialRoute);
    } catch (err) {
        dialogs.close();
        dialogs.fire(
            'Lo sentimos!',
            'Esta cuenta no existe o las credenciales son incorrectas',
            'error'
        );
    }

    function notifyAppUserId(userId) {
      try {
        if (typeof window !== 'undefined' &&
            window.Android &&
            typeof window.Android.setUserId === 'function') {
          window.Android.setUserId(parseInt(userId))
        } else {
          console.log('Android interface no disponible (modo navegador)')
        }
      } catch (err) {
        console.log('No se pudo notificar a Android:', err)
      }
    }

};
</script>


