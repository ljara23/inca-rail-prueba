import { reactive } from 'vue'
import { apiRequest } from './api'
import type { CurrentUser } from './types'

const TOKEN_KEY = 'inca-rail-token'

export const session = reactive({
  user: null as CurrentUser | null,
  ready: false,
})

export function hasPermission(permission: string): boolean {
  return session.user?.permissions.some((item) => item.slug === permission) ?? false
}

export async function initializeSession(): Promise<void> {
  if (sessionStorage.getItem(TOKEN_KEY)) {
    try {
      const response = await apiRequest<{ data: CurrentUser }>('/me')
      session.user = response.data
    } catch {
      clearSession()
    }
  }
  session.ready = true
}

export async function signIn(email: string, password: string): Promise<void> {
  const response = await apiRequest<{
    data: { token: string }
  }>('/login', {
    method: 'POST',
    body: JSON.stringify({ email, password }),
  })
  sessionStorage.setItem(TOKEN_KEY, response.data.token)
  try {
    const profile = await apiRequest<{ data: CurrentUser }>('/me')
    session.user = profile.data
  } catch (error) {
    clearSession()
    throw error
  }
}

export async function signOut(): Promise<void> {
  try {
    if (sessionStorage.getItem(TOKEN_KEY)) {
      await apiRequest('/logout', { method: 'POST' })
    }
  } finally {
    clearSession()
  }
}

export function clearSession(): void {
  sessionStorage.removeItem(TOKEN_KEY)
  session.user = null
}
