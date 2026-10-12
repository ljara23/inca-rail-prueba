<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { apiRequest, errorMessage } from '../api'
import type { ApiPage, Permission } from '../types'

const permissions = ref<Permission[]>([])
const loading = ref(true)
const error = ref('')

onMounted(async () => {
  try {
    const result = await apiRequest<ApiPage<Permission>>('/permissions?per_page=100')
    permissions.value = result.data
  } catch (cause) {
    error.value = errorMessage(cause)
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <header class="page-heading"><p class="eyebrow">ADMINISTRACIÓN</p><h1>Permisos disponibles</h1><p class="muted">Catálogo de acciones autorizables del sistema.</p></header>
    <div v-if="loading" class="state-panel" role="status">Cargando permisos…</div>
    <p v-else-if="error" class="alert alert-error" role="alert">{{ error }}</p>
    <div v-else-if="!permissions.length" class="empty-state compact"><h2>No hay permisos registrados</h2></div>
    <div v-else class="permission-grid">
      <article v-for="permission in permissions" :key="permission.id" class="permission-card">
        <span class="permission-dot" aria-hidden="true"></span><div><h2>{{ permission.name }}</h2><code>{{ permission.slug }}</code></div>
      </article>
    </div>
  </div>
</template>
