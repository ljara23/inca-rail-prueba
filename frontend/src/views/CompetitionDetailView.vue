<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { apiRequest, errorMessage } from '../api'
import type { Competition } from '../types'

const route = useRoute()
const competition = ref<Competition | null>(null)
const loading = ref(true)
const error = ref('')
const warning = ref('')

async function load(): Promise<void> {
  loading.value = true
  error.value = ''
  warning.value = ''
  try {
    const result = await apiRequest<{ data: Competition; meta?: { warning?: string } }>(`/competitions/${route.params.id}`)
    competition.value = result.data
    warning.value = result.meta?.warning ?? ''
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
    <RouterLink class="back-link" to="/competitions">← Todas las competiciones</RouterLink>
    <div v-if="loading" class="state-panel" role="status">Consultando competición y cargando sus equipos…</div>
    <div v-else-if="error" class="state-panel state-error" role="alert"><p>{{ error }}</p><button class="button button-outline" @click="load">Reintentar</button></div>
    <template v-else-if="competition">
      <section class="detail-hero">
        <div class="competition-emblem detail-emblem">
          <img v-if="competition.emblem" :src="competition.emblem" :alt="`Emblema de ${competition.name}`" />
          <span v-else>{{ competition.code || 'FC' }}</span>
        </div>
        <div><p class="eyebrow">{{ competition.code || 'COMPETICIÓN' }}</p><h1>{{ competition.name }}</h1><p class="muted">{{ competition.area_name }}</p></div>
      </section>
      <p v-if="warning" class="alert alert-warning" role="status">{{ warning }} Se muestran los datos locales.</p>
      <div class="section-heading">
        <div><p class="eyebrow">PLANTILLA DE LA COMPETICIÓN</p><h2>Equipos <span class="count-pill">{{ competition.teams?.length ?? 0 }}</span></h2></div>
        <span class="muted">Los equipos y jugadores se guardan al consultar este detalle.</span>
      </div>
      <div v-if="!competition.teams?.length" class="empty-state compact"><h3>Aún no hay equipos asociados</h3><p class="muted">El proveedor no devolvió equipos o la competición todavía no se ha sincronizado.</p></div>
      <div v-else class="card-grid team-grid">
        <RouterLink v-for="team in competition.teams" :key="team.id" class="team-card" :to="`/teams/${team.id}`">
          <img v-if="team.crest" class="team-crest" :src="team.crest" :alt="`Escudo de ${team.name}`" loading="lazy" />
          <span v-else class="team-crest crest-placeholder">{{ team.tla || 'FC' }}</span>
          <span><strong>{{ team.name }}</strong><small>{{ team.players?.length ?? 0 }} jugadores importados</small></span>
          <span class="arrow" aria-hidden="true">↗</span>
        </RouterLink>
      </div>
    </template>
  </div>
</template>
