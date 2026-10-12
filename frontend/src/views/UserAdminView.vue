<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { apiRequest, errorMessage, validationMessage } from '../api'
import PaginationControls from '../components/PaginationControls.vue'
import { hasPermission } from '../session'
import type { ApiPage, PaginationMeta, Role, User } from '../types'

const users = ref<User[]>([])
const roles = ref<Role[]>([])
const meta = ref<PaginationMeta | null>(null)
const loading = ref(true)
const saving = ref(false)
const error = ref('')
const formError = ref('')
const editingId = ref<number | null>(null)
const formOpen = ref(false)
const form = reactive({ name: '', email: '', password: '', role_ids: [] as number[] })

async function load(page = 1): Promise<void> {
  loading.value = true
  error.value = ''
  try {
    const results = await Promise.all([
      apiRequest<ApiPage<User>>(`/users?page=${page}&per_page=15`),
      apiRequest<ApiPage<Role>>('/roles?per_page=100'),
    ])
    users.value = results[0].data
    meta.value = results[0].meta
    roles.value = results[1].data
  } catch (cause) {
    error.value = errorMessage(cause)
  } finally {
    loading.value = false
  }
}

function resetForm(): void {
  editingId.value = null
  form.name = ''
  form.email = ''
  form.password = ''
  form.role_ids = []
  formError.value = ''
}

function startCreate(): void {
  resetForm()
  formOpen.value = true
}

function startEdit(user: User): void {
  resetForm()
  editingId.value = user.id
  form.name = user.name
  form.email = user.email
  form.role_ids = user.roles.map((role) => role.id)
  formOpen.value = true
}

async function saveUser(): Promise<void> {
  saving.value = true
  formError.value = ''
  const payload: Record<string, unknown> = {
    name: form.name,
    email: form.email,
    role_ids: form.role_ids,
  }
  if (form.password) payload.password = form.password
  if (editingId.value === null) payload.password = form.password
  try {
    if (editingId.value === null) {
      await apiRequest('/users', { method: 'POST', body: JSON.stringify(payload) })
    } else {
      await apiRequest(`/users/${editingId.value}`, { method: 'PUT', body: JSON.stringify(payload) })
    }
    formOpen.value = false
    await load(meta.value?.current_page ?? 1)
  } catch (cause) {
    formError.value = validationMessage(cause)
  } finally {
    saving.value = false
  }
}

async function toggleStatus(user: User): Promise<void> {
  const action = user.is_active ? 'desactivar' : 'activar'
  if (!window.confirm(`¿Confirmas ${action} a ${user.name}?`)) return
  error.value = ''
  try {
    await apiRequest(`/users/${user.id}/status`, { method: 'PATCH' })
    await load(meta.value?.current_page ?? 1)
  } catch (cause) {
    error.value = errorMessage(cause)
  }
}

onMounted(() => load())
</script>

<template>
  <div>
    <header class="page-heading page-heading-row">
      <div><p class="eyebrow">ADMINISTRACIÓN</p><h1>Usuarios</h1><p class="muted">Cuentas, estado de acceso y roles asignados.</p></div>
      <button v-if="hasPermission('users.create')" class="button button-primary" @click="startCreate">+ Nuevo usuario</button>
    </header>
    <p v-if="error" class="alert alert-error" role="alert">{{ error }}</p>
    <section v-if="formOpen" class="form-panel">
      <div class="section-heading"><div><p class="eyebrow">{{ editingId ? 'EDITAR CUENTA' : 'NUEVA CUENTA' }}</p><h2>{{ editingId ? 'Actualizar usuario' : 'Crear usuario' }}</h2></div><button class="button button-quiet" type="button" @click="formOpen = false">Cerrar</button></div>
      <form class="form-grid" @submit.prevent="saveUser">
        <label>Nombre<input v-model.trim="form.name" required maxlength="255" /></label>
        <label>Correo electrónico<input v-model.trim="form.email" type="email" required /></label>
        <label class="field-full">Contraseña
          <input v-model="form.password" type="password" :required="editingId === null" minlength="8" autocomplete="new-password" />
          <small v-if="editingId">Déjala vacía para conservar la contraseña actual.</small>
        </label>
        <fieldset class="field-full checkbox-group"><legend>Roles</legend>
          <label v-for="role in roles" :key="role.id" class="checkbox-label"><input v-model="form.role_ids" type="checkbox" :value="role.id" />{{ role.name }}</label>
        </fieldset>
        <p v-if="formError" class="field-full alert alert-error" role="alert">{{ formError }}</p>
        <div class="field-full form-actions"><button class="button button-primary" :disabled="saving">{{ saving ? 'Guardando…' : 'Guardar usuario' }}</button><button class="button button-outline" type="button" @click="formOpen = false">Cancelar</button></div>
      </form>
    </section>
    <div v-if="loading" class="state-panel" role="status">Cargando usuarios…</div>
    <div v-else-if="!error && users.length === 0" class="empty-state compact"><h2>No hay usuarios disponibles</h2></div>
    <template v-else-if="!error">
      <div class="table-wrap">
        <table><thead><tr><th>Usuario</th><th>Correo</th><th>Roles</th><th>Estado</th><th>Acciones</th></tr></thead>
          <tbody><tr v-for="user in users" :key="user.id">
            <td><strong>{{ user.name }}</strong></td><td>{{ user.email }}</td>
            <td><span v-if="user.roles.length" class="tag-list"><span v-for="role in user.roles" :key="role.id" class="tag">{{ role.name }}</span></span><span v-else class="muted">Sin rol</span></td>
            <td><span class="status-tag" :class="user.is_active ? 'status-active' : 'status-inactive'">{{ user.is_active ? 'Activo' : 'Inactivo' }}</span></td>
            <td class="row-actions">
              <button v-if="hasPermission('users.update')" class="button button-small button-outline" @click="startEdit(user)">Editar</button>
              <button v-if="hasPermission('users.deactivate')" class="button button-small button-outline" @click="toggleStatus(user)">{{ user.is_active ? 'Desactivar' : 'Activar' }}</button>
            </td>
          </tr></tbody>
        </table>
      </div>
      <PaginationControls :meta="meta" @change="load" />
    </template>
  </div>
</template>
