<template>
    <AuthLayout>
        <h1>Te damos la bienvenida a TAG Logística</h1>
        <p class="auth-subtitle">Inicia sesión para continuar</p>
        <form class="auth-form" @submit.prevent="login">
            <label class="auth-field">
                <span>Correo electrónico</span>
                <input type="email" v-model="email" placeholder="Correo electrónico" autocomplete="username" required>
            </label>
            <label class="auth-field">
                <span>Contraseña</span>
                <input type="password" v-model="password" placeholder="Contraseña" autocomplete="current-password" required>
            </label>
            <button class="auth-submit" type="submit">Ingresar</button>
            <router-link class="auth-link access-request-link" to="/solicitar-acceso">Solicita tu acceso</router-link>
        </form>
    </AuthLayout>
</template>

<script setup>
import { ref, inject } from 'vue';
import { useRouter } from 'vue-router';
import AuthLayout from '../layouts/AuthLayout.vue';
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
.access-request-link { display: block; text-align: center; margin-top: 20px; }
@media (min-width: 768px) {
    .access-request-link { display: none; }
}
</style>
