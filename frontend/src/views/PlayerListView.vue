<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { apiRequest, errorMessage } from '../api'
import PaginationControls from '../components/PaginationControls.vue'
import type { ApiPage, PaginationMeta, Player } from '../types'

const players = ref<Player[]>([])
const meta = ref<PaginationMeta | null>(null)
const loading = ref(true)
const error = ref('')

async function load(page = 1): Promise<void> {
  loading.value = true
  error.value = ''
  try {
    const result = await apiRequest<ApiPage<Player>>(`/players?page=${page}&per_page=15`)
    players.value = result.data
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
    <header class="page-heading"><p class="eyebrow">FÚTBOL</p><h1>Jugadores</h1><p class="muted">Jugadores importados y conservados en la base de datos local.</p></header>
    <div v-if="loading" class="state-panel" role="status">Cargando jugadores…</div>
    <div v-else-if="error" class="state-panel state-error" role="alert"><p>{{ error }}</p><button class="button button-outline" @click="load()">Reintentar</button></div>
    <div v-else-if="!players.length" class="empty-state"><span class="empty-icon">—</span><h2>No hay jugadores guardados</h2><p class="muted">Abre el detalle de una competición para importar las plantillas disponibles.</p><RouterLink class="button button-primary" to="/competitions">Ver competiciones</RouterLink></div>
    <template v-else>
      <div class="table-wrap">
        <table><thead><tr><th>Jugador</th><th>Equipo</th><th>Posición</th><th>Dorsal</th></tr></thead>
          <tbody><tr v-for="player in players" :key="player.id">
            <td><RouterLink class="text-link" :to="`/players/${player.id}`"><strong>{{ player.name }}</strong></RouterLink></td>
            <td><RouterLink v-if="player.team" :to="`/teams/${player.team.id}`" class="text-link">{{ player.team.name }}</RouterLink><span v-else>—</span></td>
            <td>{{ player.position || '—' }}</td><td>{{ player.shirt_number ?? '—' }}</td>
          </tr></tbody>
        </table>
      </div>
      <PaginationControls :meta="meta" @change="load" />
    </template>
  </div>
</template>
