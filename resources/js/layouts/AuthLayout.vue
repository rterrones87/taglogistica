<template>
    <main class="auth-shell">
        <div class="auth-photo" role="img" aria-label="Flota de camiones TAG Logística"></div>
        <section class="auth-panel">
            <div class="auth-content"><slot /></div>
            <footer>TAG LOGÍSTICA</footer>
        </section>
    </main>
</template>

<style scoped>
.auth-shell {
    min-height: 100vh;
    min-height: 100dvh;
    display: grid;
    grid-template-columns: 54% 46%;
    background: #fff;
    color: #171717;
}
.auth-photo {
    background: url('../assets/auth-banner.jpeg') center / cover no-repeat;
}
.auth-panel {
    display: flex;
    flex-direction: column;
    min-width: 0;
    padding: 64px clamp(32px, 5vw, 96px) 32px;
}
.auth-content { width: 100%; max-width: 440px; margin: auto; }
footer { padding-top: 56px; text-align: center; color: #18364a; font-size: 14px; letter-spacing: .04em; }
.auth-shell :deep(h1) { font-size: clamp(28px, 2.4vw, 36px); font-weight: 700; line-height: 1.2; letter-spacing: -.025em; }
.auth-shell :deep(.auth-subtitle) { margin-top: 20px; color: #6b7280; font-size: 16px; line-height: 1.5; }
.auth-shell :deep(.auth-form) { margin-top: 40px; }
.auth-shell :deep(.auth-field) { display: block; margin-bottom: 24px; }
.auth-shell :deep(.auth-field span) { display: block; margin-bottom: 10px; font-size: 16px; }
.auth-shell :deep(.auth-field input) {
    width: 100%; min-height: 54px; padding: 14px 16px;
    border: 1px solid #e5e7eb; border-radius: 10px;
    background: #f4f4f4; font-size: 16px; color: #171717;
}
.auth-shell :deep(.auth-field input::placeholder) { color: #737780; }
.auth-shell :deep(.auth-submit) {
    display: block; width: 100%; min-height: 54px; margin-top: 36px;
    padding: 14px 20px; border-radius: 10px; background: #18364a;
    color: white; font-size: 18px; font-weight: 600;
}
.auth-shell :deep(.auth-submit:hover) { background: #091b27; }
.auth-shell :deep(.auth-link) { color: #18364a; text-decoration: underline; text-underline-offset: 4px; }
.auth-shell :deep(input:focus-visible), .auth-shell :deep(button:focus-visible), .auth-shell :deep(a:focus-visible) {
    outline: 2px solid #2691e4; outline-offset: 3px;
}
@media (max-width: 767px) {
    .auth-shell { display: flex; flex-direction: column; }
    .auth-photo { flex: none; height: clamp(190px, 32vh, 320px); background-position: center 53%; }
    .auth-panel { flex: 1; padding: 28px 24px max(24px, env(safe-area-inset-bottom)); }
    .auth-content { margin: 0 auto; }
    .auth-shell :deep(h1) { font-size: 28px; }
    .auth-shell :deep(.auth-subtitle) { margin-top: 16px; }
    .auth-shell :deep(.auth-form) { margin-top: 28px; }
    .auth-shell :deep(.auth-submit) { margin-top: 28px; }
    footer { margin-top: auto; padding-top: 40px; font-size: 12px; }
}
</style>
