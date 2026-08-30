<template>
    <AuthLayout>
        <div v-if="submitted" class="access-confirmation" role="status" aria-live="polite">
            <h1 tabindex="-1" ref="confirmation">Solicitud de acceso</h1>
            <p class="auth-subtitle">Un asesor se pondrá en contacto contigo para brindarte el acceso</p>
            <p class="return-notice">Volverás al inicio de sesión en 5 segundos.</p>
        </div>
        <template v-else>
            <router-link class="auth-link back-link" to="/login">Volver al inicio de sesión</router-link>
            <h1>Solicita tu acceso</h1>
            <p class="auth-subtitle">Completa tus datos. Todos los campos son obligatorios.</p>
            <form class="auth-form" @submit.prevent="requestAccess">
                <label class="auth-field">
                    <span>Nombre</span>
                    <input v-model.trim="firstName" name="firstName" autocomplete="given-name" placeholder="Nombre" maxlength="100" pattern=".*\S.*" required>
                </label>
                <label class="auth-field">
                    <span>Apellidos</span>
                    <input v-model.trim="lastName" name="lastName" autocomplete="family-name" placeholder="Apellidos" maxlength="150" pattern=".*\S.*" required>
                </label>
                <label class="auth-field">
                    <span>Correo</span>
                    <input v-model.trim="email" type="email" name="email" autocomplete="email" placeholder="Correo electrónico" maxlength="254" required>
                </label>
                <label class="auth-field">
                    <span>Teléfono</span>
                    <input v-model.trim="phone" type="tel" name="phone" autocomplete="tel" placeholder="Teléfono" maxlength="25" pattern="\+?[0-9\s\(\)\-]{7,25}" title="Ingresa un teléfono válido, con al menos 7 dígitos." required>
                </label>
                <button class="auth-submit" type="submit">Solicitar acceso</button>
            </form>
        </template>
    </AuthLayout>
</template>

<script setup>
import { ref, nextTick, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import AuthLayout from '../layouts/AuthLayout.vue';

const router = useRouter();
const firstName = ref('');
const lastName = ref('');
const email = ref('');
const phone = ref('');
const submitted = ref(false);
const confirmation = ref(null);
let returnTimer;

async function requestAccess(event) {
    if (submitted.value) return;
    const phoneInput = event.target.elements.phone;
    phoneInput.setCustomValidity(phone.value.replace(/\D/g, '').length >= 7 ? '' : 'Ingresa al menos 7 dígitos.');
    if (!event.target.reportValidity()) {
        phoneInput.addEventListener('input', () => phoneInput.setCustomValidity(''), { once: true });
        return;
    }
    // Flujo de demostración solicitado: no enviar ni persistir los datos.
    firstName.value = lastName.value = email.value = phone.value = '';
    submitted.value = true;
    await nextTick();
    confirmation.value?.focus();
    returnTimer = window.setTimeout(() => router.replace('/login'), 5000);
}

onBeforeUnmount(() => window.clearTimeout(returnTimer));
</script>

<style scoped>
.back-link { display: inline-block; margin-bottom: 28px; }
.return-notice { margin-top: 24px; color: #6b7280; font-size: 14px; }
.access-confirmation { padding: 32px 0; }
.access-confirmation h1:focus { outline: none; }
</style>
