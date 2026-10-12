<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { apiRequest, errorMessage } from '../api'
import type { Player } from '../types'

const route = useRoute()
const player = ref<Player | null>(null)
const loading = ref(true)
const error = ref('')

async function load(): Promise<void> {
  loading.value = true
  error.value = ''
  try {
    const response = await apiRequest<{ data: Player }>(`/players/${route.params.id}`)
    player.value = response.data
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
    <RouterLink class="back-link" to="/players">← Todos los jugadores</RouterLink>
    <div v-if="loading" class="state-panel" role="status">Cargando detalle del jugador…</div>
    <div v-else-if="error" class="state-panel state-error" role="alert">
      <p>{{ error }}</p>
      <button class="button button-outline" @click="load">Reintentar</button>
    </div>
    <template v-else-if="player">
      <section class="detail-hero">
        <span class="detail-crest player-avatar" aria-hidden="true">{{ player.name.split(' ').map((part) => part[0]).slice(0, 2).join('').toUpperCase() }}</span>
        <div>
          <p class="eyebrow">JUGADOR</p>
          <h1>{{ player.name }}</h1>
          <RouterLink v-if="player.team" class="text-link" :to="`/teams/${player.team.id}`">{{ player.team.name }}</RouterLink>
        </div>
      </section>
      <section class="player-facts" aria-label="Datos del jugador">
        <article class="player-fact"><span class="eyebrow">POSICIÓN</span><strong>{{ player.position || 'No especificada' }}</strong></article>
        <article class="player-fact"><span class="eyebrow">DORSAL</span><strong>{{ player.shirt_number ?? '—' }}</strong></article>
      </section>
    </template>
  </div>
</template>
