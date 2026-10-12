<script setup lang="ts">
import type { PaginationMeta } from '../types'

defineProps<{ meta: PaginationMeta | null }>()
const emit = defineEmits<{ change: [page: number] }>()
</script>

<template>
  <nav v-if="meta && meta.last_page > 1" class="pagination" aria-label="Paginación">
    <span class="muted">Página {{ meta.current_page }} de {{ meta.last_page }}</span>
    <div class="pagination-actions">
      <button class="button button-outline" :disabled="meta.current_page <= 1" @click="emit('change', meta.current_page - 1)">
        Anterior
      </button>
      <button class="button button-outline" :disabled="meta.current_page >= meta.last_page" @click="emit('change', meta.current_page + 1)">
        Siguiente
      </button>
    </div>
  </nav>
</template>
