<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { apiRequest, errorMessage } from '../api'
import PaginationControls from '../components/PaginationControls.vue'
import type { ApiPage, Competition, PaginationMeta } from '../types'

const competitions = ref<Competition[]>([])
const meta = ref<PaginationMeta | null>(null)
const loading = ref(true)
const error = ref('')

async function load(page = 1): Promise<void> {
  loading.value = true
  error.value = ''
  try {
    const result = await apiRequest<ApiPage<Competition>>(`/competitions?page=${page}&per_page=15`)
    competitions.value = result.data
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
    <header class="page-heading page-heading-row">
      <div><p class="eyebrow">FÚTBOL</p><h1>Competiciones</h1><p class="muted">Ligas disponibles desde football-data.org y tu base local.</p></div>
      <button class="button button-outline" :disabled="loading" @click="load(meta?.current_page ?? 1)">Actualizar</button>
    </header>
    <p v-if="meta?.warning" class="alert alert-warning" role="status">{{ meta.warning }} Se muestran los datos disponibles localmente.</p>
    <div v-if="loading" class="state-panel" role="status">Cargando competiciones…</div>
    <div v-else-if="error" class="state-panel state-error" role="alert"><p>{{ error }}</p><button class="button button-outline" @click="load()">Reintentar</button></div>
    <div v-else-if="competitions.length === 0" class="empty-state">
      <span class="empty-icon">—</span><h2>No hay competiciones guardadas</h2>
      <p class="muted">No se encontraron datos locales. Revisa la configuración del proveedor o vuelve a intentarlo.</p>
      <button class="button button-primary" @click="load()">Volver a consultar</button>
    </div>
    <template v-else>
      <div class="card-grid competition-grid">
        <RouterLink v-for="competition in competitions" :key="competition.id" class="competition-card" :to="`/competitions/${competition.id}`">
          <div class="competition-emblem">
            <img v-if="competition.emblem" :src="competition.emblem" :alt="`Emblema de ${competition.name}`" loading="lazy" />
            <span v-else>{{ competition.code || 'FC' }}</span>
          </div>
          <div class="competition-card-copy">
            <span class="eyebrow">{{ competition.code || 'COMPETICIÓN' }}</span>
            <h2>{{ competition.name }}</h2>
            <p class="muted">{{ competition.area_name || 'Zona no especificada' }}</p>
          </div>
          <span class="arrow" aria-hidden="true">↗</span>
        </RouterLink>
      </div>
      <PaginationControls :meta="meta" @change="load" />
    </template>
  </div>
</template>
