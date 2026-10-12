<script setup lang="ts">
import { computed } from 'vue'
import { hasPermission, session } from '../session'

const firstName = computed(() => session.user?.name.split(' ')[0] ?? '')
</script>

<template>
  <div>
    <header class="page-heading">
      <p class="eyebrow">PANEL DE CONTROL</p>
      <h1>Hola, {{ firstName }}</h1>
      <p class="muted">Consulta la información de fútbol y administra el acceso desde un solo lugar.</p>
    </header>
    <section class="welcome-panel">
      <div>
        <p class="eyebrow">INCA RAIL FOOTBALL</p>
        <h2>Datos del fútbol, listos para explorar.</h2>
        <p>Las competiciones consultadas se guardan localmente para que puedas volver a ellas.</p>
      </div>
      <RouterLink v-if="hasPermission('competitions.view')" class="button button-light" to="/competitions">
        Explorar competiciones
      </RouterLink>
    </section>
    <section class="section-block">
      <div class="section-heading">
        <div><p class="eyebrow">ACCESO RÁPIDO</p><h2>Tu espacio de trabajo</h2></div>
      </div>
      <div class="card-grid">
        <RouterLink v-if="hasPermission('competitions.view')" class="feature-card" to="/competitions">
          <span class="card-symbol">01</span><h3>Competiciones</h3><p>Explora e importa ligas disponibles.</p>
        </RouterLink>
        <RouterLink v-if="hasPermission('teams.view')" class="feature-card" to="/teams">
          <span class="card-symbol">02</span><h3>Equipos</h3><p>Consulta los equipos guardados localmente.</p>
        </RouterLink>
        <RouterLink v-if="hasPermission('players.view')" class="feature-card" to="/players">
          <span class="card-symbol">03</span><h3>Jugadores</h3><p>Revisa plantillas y datos de jugadores.</p>
        </RouterLink>
        <RouterLink v-if="hasPermission('users.view')" class="feature-card" to="/admin/users">
          <span class="card-symbol">04</span><h3>Usuarios</h3><p>Administra cuentas y roles asignados.</p>
        </RouterLink>
        <RouterLink v-if="hasPermission('roles.view')" class="feature-card" to="/admin/roles">
          <span class="card-symbol">05</span><h3>Roles</h3><p>Organiza los permisos de cada rol.</p>
        </RouterLink>
      </div>
    </section>
  </div>
</template>
