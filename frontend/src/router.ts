import { createRouter, createWebHistory } from 'vue-router'
import { hasPermission, session } from './session'
import LoginView from './views/LoginView.vue'
import DashboardView from './views/DashboardView.vue'
import CompetitionListView from './views/CompetitionListView.vue'
import CompetitionDetailView from './views/CompetitionDetailView.vue'
import TeamListView from './views/TeamListView.vue'
import TeamDetailView from './views/TeamDetailView.vue'
import PlayerListView from './views/PlayerListView.vue'
import PlayerDetailView from './views/PlayerDetailView.vue'
import UserAdminView from './views/UserAdminView.vue'
import RoleAdminView from './views/RoleAdminView.vue'
import PermissionListView from './views/PermissionListView.vue'
import ForbiddenView from './views/ForbiddenView.vue'

declare module 'vue-router' {
  interface RouteMeta {
    requiresAuth?: boolean
    permission?: string
  }
}

export const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/login', name: 'login', component: LoginView },
    { path: '/', redirect: '/dashboard' },
    { path: '/dashboard', component: DashboardView, meta: { requiresAuth: true } },
    {
      path: '/competitions',
      component: CompetitionListView,
      meta: { requiresAuth: true, permission: 'competitions.view' },
    },
    {
      path: '/competitions/:id',
      component: CompetitionDetailView,
      meta: { requiresAuth: true, permission: 'competitions.view' },
    },
    { path: '/teams', component: TeamListView, meta: { requiresAuth: true, permission: 'teams.view' } },
    { path: '/teams/:id', component: TeamDetailView, meta: { requiresAuth: true, permission: 'teams.view' } },
    { path: '/players', component: PlayerListView, meta: { requiresAuth: true, permission: 'players.view' } },
    { path: '/players/:id', component: PlayerDetailView, meta: { requiresAuth: true, permission: 'players.view' } },
    { path: '/admin/users', component: UserAdminView, meta: { requiresAuth: true, permission: 'users.view' } },
    { path: '/admin/roles', component: RoleAdminView, meta: { requiresAuth: true, permission: 'roles.view' } },
    {
      path: '/admin/permissions',
      component: PermissionListView,
      meta: { requiresAuth: true, permission: 'permissions.view' },
    },
    { path: '/forbidden', component: ForbiddenView, meta: { requiresAuth: true } },
    { path: '/:pathMatch(.*)*', redirect: '/dashboard' },
  ],
})

router.beforeEach((to) => {
  if (to.meta.requiresAuth && !session.user) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }
  if (to.name === 'login' && session.user) return { name: 'dashboard' }
  if (to.meta.permission && !hasPermission(to.meta.permission)) return { path: '/forbidden' }
  return true
})
