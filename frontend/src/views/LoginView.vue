<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { errorMessage } from '../api'
import { signIn } from '../session'

const route = useRoute()
const router = useRouter()
const sessionExpired = computed(() => route.query.expired === '1')
const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')

async function submit(): Promise<void> {
  loading.value = true
  error.value = ''
  try {
    await signIn(email.value, password.value)
    const destination = typeof route.query.redirect === 'string' ? route.query.redirect : '/dashboard'
    await router.replace(destination.startsWith('/') ? destination : '/dashboard')
  } catch (cause) {
    error.value = errorMessage(cause)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <main class="login-screen">
    <section class="login-card">
      <div class="brand-mark">IR</div>
      <p class="eyebrow">INCA RAIL FOOTBALL</p>
      <h1>Bienvenido</h1>
      <p class="muted">Inicia sesión para consultar la plataforma.</p>
      <p v-if="sessionExpired" class="alert alert-warning" role="status">Tu sesión venció. Inicia sesión nuevamente para continuar.</p>
      <form class="form-stack" @submit.prevent="submit">
        <label>
          Correo electrónico
          <input v-model.trim="email" type="email" autocomplete="username" required />
        </label>
        <label>
          Contraseña
          <input v-model="password" type="password" autocomplete="current-password" required />
        </label>
        <p v-if="error" class="alert alert-error" role="alert">{{ error }}</p>
        <button class="button button-primary button-wide" type="submit" :disabled="loading">
          {{ loading ? 'Ingresando…' : 'Iniciar sesión' }}
        </button>
      </form>
    </section>
  </main>
</template>
