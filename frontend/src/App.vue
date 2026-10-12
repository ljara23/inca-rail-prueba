<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import { clearSession, hasPermission, session, signOut } from './session'

const route = useRoute()
const router = useRouter()
const isLogin = computed(() => route.path === '/login')
const initials = computed(() => session.user?.name.split(/\s+/).slice(0, 2).map((word) => word[0]).join('').toUpperCase() ?? '')

function handleExpiredSession(): void {
  clearSession()
  void router.replace({ name: 'login', query: { expired: '1', redirect: route.fullPath } })
}

onMounted(() => window.addEventListener('inca-rail-session-expired', handleExpiredSession))
onUnmounted(() => window.removeEventListener('inca-rail-session-expired', handleExpiredSession))

async function logout(): Promise<void> {
  try {
    await signOut()
    await router.replace({ name: 'login' })
  } catch {
    clearSession()
    await router.replace({ name: 'login' })
  }
}
</script>

<template>
  <RouterView v-if="isLogin" />
  <div v-else class="app-shell">
    <aside class="sidebar">
      <RouterLink class="brand" to="/dashboard" aria-label="Inca Rail Football, inicio">
        <span class="brand-mark brand-mark-small">IR</span>
        <span><strong>INCA RAIL</strong><small>FOOTBALL PLATFORM</small></span>
      </RouterLink>
      <div class="nav-label">ESPACIO DE TRABAJO</div>
      <nav class="main-nav" aria-label="Navegación principal">
        <RouterLink to="/dashboard" class="nav-item"><span class="nav-icon">⌂</span>Inicio</RouterLink>
        <RouterLink v-if="hasPermission('competitions.view')" to="/competitions" class="nav-item"><span class="nav-icon">◉</span>Competiciones</RouterLink>
        <RouterLink v-if="hasPermission('teams.view')" to="/teams" class="nav-item"><span class="nav-icon">▦</span>Equipos</RouterLink>
        <RouterLink v-if="hasPermission('players.view')" to="/players" class="nav-item"><span class="nav-icon">◎</span>Jugadores</RouterLink>
      </nav>
      <template v-if="hasPermission('users.view') || hasPermission('roles.view')">
        <div class="nav-label nav-label-spaced">GESTIÓN</div>
        <nav class="main-nav" aria-label="Administración">
          <RouterLink v-if="hasPermission('users.view')" to="/admin/users" class="nav-item"><span class="nav-icon">♙</span>Usuarios</RouterLink>
          <RouterLink v-if="hasPermission('roles.view')" to="/admin/roles" class="nav-item"><span class="nav-icon">◇</span>Roles</RouterLink>
          <RouterLink v-if="hasPermission('permissions.view')" to="/admin/permissions" class="nav-item"><span class="nav-icon">☷</span>Permisos</RouterLink>
        </nav>
      </template>
      <div class="sidebar-footer"><span class="provider-indicator"></span><span>Datos protegidos por Inca Rail</span></div>
    </aside>
    <div class="app-main">
      <header class="topbar">
        <div class="mobile-brand"><span class="brand-mark brand-mark-small">IR</span><strong>INCA RAIL</strong></div>
        <div class="topbar-spacer"></div>
        <div v-if="session.user" class="user-menu">
          <span class="user-avatar">{{ initials }}</span>
          <span class="user-ident"><strong>{{ session.user.name }}</strong><small>{{ session.user.roles.map((role) => role.name).join(', ') || 'Usuario' }}</small></span>
          <button class="button button-quiet logout-button" @click="logout">Cerrar sesión</button>
        </div>
      </header>
      <nav class="mobile-nav" aria-label="Navegación móvil">
        <RouterLink to="/dashboard">Inicio</RouterLink>
        <RouterLink v-if="hasPermission('competitions.view')" to="/competitions">Competiciones</RouterLink>
        <RouterLink v-if="hasPermission('teams.view')" to="/teams">Equipos</RouterLink>
        <RouterLink v-if="hasPermission('players.view')" to="/players">Jugadores</RouterLink>
        <RouterLink v-if="hasPermission('users.view')" to="/admin/users">Usuarios</RouterLink>
        <RouterLink v-if="hasPermission('roles.view')" to="/admin/roles">Roles</RouterLink>
      </nav>
      <main class="content-area">
        <RouterView />
        <footer class="page-footer"><span>INCA RAIL FOOTBALL</span><span>Panel interno · Datos locales y proveedor externo</span></footer>
      </main>
    </div>
  </div>
</template>
