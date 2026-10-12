export interface Permission {
  id: number
  name: string
  slug: string
}

export interface Role {
  id: number
  name: string
  slug: string
  permissions: Permission[]
}

export interface User {
  id: number
  name: string
  email: string
  is_active: boolean
  roles: Pick<Role, 'id' | 'name' | 'slug'>[]
}

export interface CurrentUser extends User {
  permissions: Permission[]
}

export interface Competition {
  id: number
  external_id: number | null
  name: string
  code: string | null
  area_name: string | null
  emblem: string | null
  season: string | number | null
  last_synced_at: string | null
  teams?: Team[]
}

export interface Team {
  id: number
  external_id: number | null
  name: string
  short_name: string | null
  tla: string | null
  crest: string | null
  players?: Player[]
}

export interface Player {
  id: number
  external_id: number | null
  name: string
  position: string | null
  shirt_number: number | null
  team?: Pick<Team, 'id' | 'name'>
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  total: number
  warning?: string
}

export interface ApiPage<T> {
  data: T[]
  meta: PaginationMeta
}
