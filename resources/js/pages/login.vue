<template>

    <div class="login-screen">
        <div class="login-backdrop" aria-hidden="true"></div>
        <div class="login-card bg-white rounded shadow-md w-full max-w-[600px] flex justify-center overflow-hidden">
            <div class="grow bg-[var(--primarycolor)] hidden md:block">

            </div>
            <div class="login-form p-6 sm:p-8">
                <h2 class="text-3xl my-4 font-bold">Iniciar Sesión</h2>
                <form @submit.prevent="login">
                    <label class="form-item">
                        <span>Correo</span>
                        <input type="email" v-model="email" placeholder="Correo" autocomplete="username" required>
                    </label>
                    <label class="form-item">
                        <span>Contraseña</span>
                        <input type="password" v-model="password" placeholder="Contraseña" autocomplete="current-password" required>
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

<style scoped>
.login-screen {
    position: fixed;
    inset: 0;
    isolation: isolate;
    display: flex;
    overflow-y: auto;
    padding: 24px 16px;
    background: #091b27;
}

.login-backdrop {
    position: fixed;
    inset: -8px;
    z-index: -2;
    pointer-events: none;
    background: url('../assets/login-background.png') center 58% / cover no-repeat;
    filter: blur(2px);
}

.login-screen::before {
    content: '';
    position: fixed;
    inset: 0;
    z-index: -1;
    pointer-events: none;
    background: linear-gradient(180deg, rgba(0, 0, 0, 0.38), rgba(3, 12, 20, 0.62));
}

.login-card {
    flex-shrink: 0;
    margin: auto;
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 16px;
    box-shadow: 0 24px 80px rgba(0, 0, 0, 0.4);
}

.login-form {
    width: 100%;
    max-width: 400px;
    min-width: 0;
}

@media (max-width: 767px) {
    .login-backdrop {
        background-position: 38% center;
    }

    .login-card {
        max-width: 400px;
    }

    .login-form input {
        font-size: 16px;
    }
}
</style>

