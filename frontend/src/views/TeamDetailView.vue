<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { apiRequest, errorMessage } from '../api'
import type { Team } from '../types'

const route = useRoute()
const team = ref<Team | null>(null)
const loading = ref(true)
const error = ref('')

async function load(): Promise<void> {
  loading.value = true
  error.value = ''
  try {
    const result = await apiRequest<{ data: Team }>(`/team/${route.params.id}`)
    team.value = result.data
  } catch (cause) {
    error.value = errorMessage(cause)
  } finally {
    loading.value = false
  }
}

onMounted(load)
watch(() => route.params.id, load)
</script>

<template>
  <div>
    <RouterLink class="back-link" to="/teams">← Todos los equipos</RouterLink>
    <div v-if="loading" class="state-panel" role="status">Cargando detalle del equipo…</div>
    <div v-else-if="error" class="state-panel state-error" role="alert"><p>{{ error }}</p><button class="button button-outline" @click="load">Reintentar</button></div>
    <template v-else-if="team">
      <section class="detail-hero">
        <img v-if="team.crest" class="detail-crest" :src="team.crest" :alt="`Escudo de ${team.name}`" />
        <span v-else class="detail-crest crest-placeholder">{{ team.tla || 'FC' }}</span>
        <div><p class="eyebrow">{{ team.tla || 'EQUIPO' }}</p><h1>{{ team.name }}</h1><p class="muted">{{ team.short_name || 'Información de plantilla guardada localmente' }}</p></div>
      </section>
      <div class="section-heading"><div><p class="eyebrow">PLANTILLA</p><h2>Jugadores <span class="count-pill">{{ team.players?.length ?? 0 }}</span></h2></div></div>
      <div v-if="!team.players?.length" class="empty-state compact"><h3>No hay jugadores disponibles</h3><p class="muted">Los jugadores aparecerán cuando la competición se importe con datos de plantilla.</p></div>
      <div v-else class="table-wrap">
        <table><thead><tr><th>Jugador</th><th>Posición</th><th>Dorsal</th></tr></thead>
          <tbody><tr v-for="player in team.players" :key="player.id"><td><RouterLink class="text-link" :to="`/players/${player.id}`"><strong>{{ player.name }}</strong></RouterLink></td><td>{{ player.position || '—' }}</td><td>{{ player.shirt_number ?? '—' }}</td></tr></tbody>
        </table>
      </div>
    </template>
  </div>
</template>
