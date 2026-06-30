import { defineStore } from 'pinia'
import type { AuthUser, Role } from '@/types'

interface AuthState {
  token: string | null
  user: AuthUser | null
}

interface LoginPayload {
  name: string
  faculty: string
  roles: Role[]
}

/*
  Mock auth store. There is no real backend wired up yet, so this
  simulates what the JWT flow will look like once the API is ready:
  - login() pretends to call POST /auth/login and stores a fake token
  - currentUser carries the active role so every view can react to it
  - a single account can hold Learner + Tutor (per the proposal's scope),
    represented here as `roles: Role[]` with one `activeRole` toggle
*/
export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    token: null,
    user: null,
  }),
  getters: {
    isAuthenticated: (state): boolean => !!state.token,
    activeRole: (state): Role | null => state.user?.activeRole ?? null,
  },
  actions: {
    login({ name, faculty, roles }: LoginPayload) {
      // Stand-in for: const res = await api.post('/auth/login', credentials)
      this.token = `mock-jwt-${Date.now()}`
      const firstRole = roles[0] ?? 'Learner'
      this.user = {
        id: 'u-local',
        name,
        faculty,
        roles,
        activeRole: firstRole,
        avatar: name.trim().charAt(0).toUpperCase() || '?',
      }
    },
    switchRole(role: Role) {
      if (this.user && this.user.roles.includes(role)) {
        this.user.activeRole = role
      }
    },
    logout() {
      this.token = null
      this.user = null
    },
  },
})
