<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { apiRequest, errorMessage } from '../api'
import PaginationControls from '../components/PaginationControls.vue'
import type { ApiPage, PaginationMeta, Team } from '../types'

const teams = ref<Team[]>([])
const meta = ref<PaginationMeta | null>(null)
const loading = ref(true)
const error = ref('')

async function load(page = 1): Promise<void> {
  loading.value = true
  error.value = ''
  try {
    const result = await apiRequest<ApiPage<Team>>(`/team?page=${page}&per_page=15`)
    teams.value = result.data
    meta.value = result.meta
  } catch (cause) {
    error.value = errorMessage(cause)
  } finally {
    loading.value = false
  }
}

onMounted(() => load())
</script>

<template>
  <div>
    <header class="page-heading"><p class="eyebrow">FÚTBOL</p><h1>Equipos</h1><p class="muted">Equipos almacenados localmente desde competiciones consultadas.</p></header>
    <div v-if="loading" class="state-panel" role="status">Cargando equipos…</div>
    <div v-else-if="error" class="state-panel state-error" role="alert"><p>{{ error }}</p><button class="button button-outline" @click="load()">Reintentar</button></div>
    <div v-else-if="!teams.length" class="empty-state"><span class="empty-icon">—</span><h2>No hay equipos guardados</h2><p class="muted">Abre una competición para importar sus equipos.</p><RouterLink class="button button-primary" to="/competitions">Ver competiciones</RouterLink></div>
    <template v-else>
      <div class="card-grid team-grid">
        <RouterLink v-for="team in teams" :key="team.id" class="team-card" :to="`/teams/${team.id}`">
          <img v-if="team.crest" class="team-crest" :src="team.crest" :alt="`Escudo de ${team.name}`" loading="lazy" />
          <span v-else class="team-crest crest-placeholder">{{ team.tla || 'FC' }}</span>
          <span><strong>{{ team.name }}</strong><small>{{ team.players?.length ?? 0 }} jugadores</small></span><span class="arrow" aria-hidden="true">↗</span>
        </RouterLink>
      </div>
      <PaginationControls :meta="meta" @change="load" />
    </template>
  </div>
</template>
