export class ApiError extends Error {
  public readonly status: number
  public readonly details?: Record<string, string[]>

  constructor(
    message: string,
    status: number,
    details?: Record<string, string[]>,
  ) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.details = details
  }
}

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || '/api'

export async function apiRequest<T>(
  path: string,
  options: RequestInit = {},
): Promise<T> {
  const token = sessionStorage.getItem('inca-rail-token')
  const headers = new Headers(options.headers)
  headers.set('Accept', 'application/json')

  if (options.body && !(options.body instanceof FormData)) {
    headers.set('Content-Type', 'application/json')
  }
  if (token) headers.set('Authorization', `Bearer ${token}`)

  let response: Response
  try {
    response = await fetch(`${apiBaseUrl}${path}`, { ...options, headers })
  } catch {
    throw new ApiError('No se pudo conectar con la API. Verifica que el backend esté iniciado.', 0)
  }

  const payload: unknown = await response.json().catch(() => null)

  if (response.status === 401) {
    sessionStorage.removeItem('inca-rail-token')
    window.dispatchEvent(new Event('inca-rail-session-expired'))
  }

  if (!response.ok) {
    const body = payload as {
      message?: string
      errors?: Record<string, string[]>
      error?: { message?: string }
    } | null
    const message = body?.error?.message ?? body?.message ?? statusMessage(response.status)
    throw new ApiError(message, response.status, body?.errors)
  }

  return payload as T
}

function statusMessage(status: number): string {
  if (status === 401) return 'Tu sesión venció. Inicia sesión nuevamente.'
  if (status === 403) return 'Tu cuenta no tiene permiso para realizar esta acción.'
  if (status === 404) return 'No se encontró el recurso solicitado.'
  if (status === 422) return 'Revisa los datos ingresados.'
  if (status === 429) return 'Se alcanzó el límite de solicitudes. Espera un minuto e inténtalo otra vez.'
  if (status === 502 || status === 503) return 'El proveedor de fútbol no está disponible temporalmente.'
  return 'Ocurrió un error al comunicarse con la API.'
}

export function errorMessage(error: unknown): string {
  return error instanceof Error ? error.message : 'Ocurrió un error inesperado.'
}

export function validationMessage(error: unknown): string {
  if (error instanceof ApiError && error.details) {
    return Object.values(error.details).flat().join(' ')
  }
  return errorMessage(error)
}
