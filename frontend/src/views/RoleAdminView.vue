<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { apiRequest, errorMessage, validationMessage } from '../api'
import PaginationControls from '../components/PaginationControls.vue'
import { hasPermission } from '../session'
import type { ApiPage, PaginationMeta, Permission, Role } from '../types'

const roles = ref<Role[]>([])
const permissions = ref<Permission[]>([])
const meta = ref<PaginationMeta | null>(null)
const loading = ref(true)
const saving = ref(false)
const error = ref('')
const formError = ref('')
const formOpen = ref(false)
const editingId = ref<number | null>(null)
const form = reactive({ name: '', slug: '', permission_ids: [] as number[] })

async function load(page = 1): Promise<void> {
  loading.value = true
  error.value = ''
  try {
    const results = await Promise.all([
      apiRequest<ApiPage<Role>>(`/roles?page=${page}&per_page=15`),
      apiRequest<ApiPage<Permission>>('/permissions?per_page=100'),
    ])
    roles.value = results[0].data
    meta.value = results[0].meta
    permissions.value = results[1].data
  } catch (cause) {
    error.value = errorMessage(cause)
  } finally {
    loading.value = false
  }
}

function resetForm(): void {
  editingId.value = null
  form.name = ''
  form.slug = ''
  form.permission_ids = []
  formError.value = ''
}

function startCreate(): void {
  resetForm()
  formOpen.value = true
}

function startEdit(role: Role): void {
  resetForm()
  editingId.value = role.id
  form.name = role.name
  form.slug = role.slug
  form.permission_ids = role.permissions.map((permission) => permission.id)
  formOpen.value = true
}

async function saveRole(): Promise<void> {
  saving.value = true
  formError.value = ''
  const method = editingId.value === null ? 'POST' : 'PUT'
  const path = editingId.value === null ? '/roles' : `/roles/${editingId.value}`
  try {
    await apiRequest(path, {
      method,
      body: JSON.stringify({ name: form.name, slug: form.slug, permission_ids: form.permission_ids }),
    })
    formOpen.value = false
    await load(meta.value?.current_page ?? 1)
  } catch (cause) {
    formError.value = validationMessage(cause)
  } finally {
    saving.value = false
  }
}

onMounted(() => load())
</script>

<template>
  <div>
    <header class="page-heading page-heading-row">
      <div><p class="eyebrow">ADMINISTRACIÓN</p><h1>Roles y permisos</h1><p class="muted">Define qué acciones puede realizar cada tipo de usuario.</p></div>
      <button v-if="hasPermission('roles.manage')" class="button button-primary" @click="startCreate">+ Nuevo rol</button>
    </header>
    <p v-if="error" class="alert alert-error" role="alert">{{ error }}</p>
    <section v-if="formOpen" class="form-panel">
      <div class="section-heading"><div><p class="eyebrow">{{ editingId ? 'EDITAR ROL' : 'NUEVO ROL' }}</p><h2>{{ editingId ? 'Actualizar rol' : 'Crear rol' }}</h2></div><button class="button button-quiet" type="button" @click="formOpen = false">Cerrar</button></div>
      <form class="form-grid" @submit.prevent="saveRole">
        <label>Nombre<input v-model.trim="form.name" required maxlength="255" /></label>
        <label>Identificador (slug)<input v-model.trim="form.slug" required maxlength="255" pattern="[a-z0-9-]+" /><small>Usa letras minúsculas, números y guiones.</small></label>
        <fieldset class="field-full checkbox-group"><legend>Permisos</legend>
          <p v-if="permissions.length === 0" class="muted">No se pudo cargar el catálogo de permisos.</p>
          <label v-for="permission in permissions" :key="permission.id" class="checkbox-label"><input v-model="form.permission_ids" type="checkbox" :value="permission.id" />{{ permission.name }} <code>{{ permission.slug }}</code></label>
        </fieldset>
        <p v-if="formError" class="field-full alert alert-error" role="alert">{{ formError }}</p>
        <div class="field-full form-actions"><button class="button button-primary" :disabled="saving">{{ saving ? 'Guardando…' : 'Guardar rol' }}</button><button class="button button-outline" type="button" @click="formOpen = false">Cancelar</button></div>
      </form>
    </section>
    <div v-if="loading" class="state-panel" role="status">Cargando roles y permisos…</div>
    <div v-else-if="!error && roles.length === 0" class="empty-state compact"><h2>No hay roles disponibles</h2></div>
    <template v-else-if="!error">
      <div class="role-grid">
        <article v-for="role in roles" :key="role.id" class="role-card">
          <div class="role-card-head"><div><h2>{{ role.name }}</h2><code>{{ role.slug }}</code></div><button v-if="hasPermission('roles.manage')" class="button button-small button-outline" @click="startEdit(role)">Editar</button></div>
          <div class="tag-list"><span v-for="permission in role.permissions" :key="permission.id" class="tag">{{ permission.name }}</span><span v-if="!role.permissions.length" class="muted">Sin permisos asignados</span></div>
        </article>
      </div>
      <PaginationControls :meta="meta" @change="load" />
    </template>
  </div>
</template>
