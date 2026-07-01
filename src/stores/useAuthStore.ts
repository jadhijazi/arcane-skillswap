import { defineStore } from 'pinia'
import { authApi, userApi, type BackendUser } from '@/lib/api'
import { tokenStorage, ApiError } from '@/lib/http'
import type { AuthUser, Role } from '@/types'

interface AuthState {
  user: AuthUser | null
  status: 'idle' | 'loading' | 'ready' | 'error'
  error: string | null
}

function toAuthUser(backendUser: BackendUser, roles: Role[]): AuthUser {
  const name = `${backendUser.first_name} ${backendUser.last_name}`.trim()
  const activeRole = roles.includes('Tutor') ? 'Tutor' : (roles[0] ?? 'Learner')
  return {
    id: backendUser.id,
    email: backendUser.email,
    name: name || backendUser.email,
    faculty: backendUser.faculty ?? '',
    year: backendUser.year ?? '',
    bio: backendUser.bio ?? '',
    roles,
    activeRole,
    avatar: name.trim().charAt(0).toUpperCase() || '?',
  }
}

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    user: null,
    status: 'idle',
    error: null,
  }),

  getters: {
    isAuthenticated: (state): boolean => !!state.user,
    activeRole: (state): Role | null => state.user?.activeRole ?? null,
  },

  actions: {
    async register(payload: {
      email: string
      password: string
      firstName: string
      lastName: string
      faculty?: string
      year?: string
    }) {
      this.status = 'loading'
      this.error = null
      try {
        const result = await authApi.register({
          email: payload.email,
          password: payload.password,
          first_name: payload.firstName,
          last_name: payload.lastName,
          faculty: payload.faculty,
          year: payload.year,
        })
        tokenStorage.setTokens(result.access_token, result.refresh_token)
        this.user = toAuthUser(result.user, result.roles)
        this.status = 'ready'
      } catch (e) {
        this.status = 'error'
        this.error = e instanceof ApiError ? e.message : 'Could not create your account.'
        throw e
      }
    },

    async login(email: string, password: string) {
      this.status = 'loading'
      this.error = null
      try {
        const result = await authApi.login(email, password)
        tokenStorage.setTokens(result.access_token, result.refresh_token)
        this.user = toAuthUser(result.user, result.roles)
        this.status = 'ready'
      } catch (e) {
        this.status = 'error'
        this.error = e instanceof ApiError ? e.message : 'Invalid email or password.'
        throw e
      }
    },

    // Call this once on app boot: if a token is already stored, fetch the
    // profile to rebuild session state instead of forcing a re-login.
    async restoreSession() {
      if (!tokenStorage.getAccessToken()) return
      this.status = 'loading'
      try {
        const me = await userApi.me()
        // /users/me doesn't return roles directly, so infer Tutor from
        // whether they have any skill offerings; default to Learner.
        const roles: Role[] = ['Learner']
        this.user = toAuthUser(me.user, roles)
        this.status = 'ready'
      } catch {
        tokenStorage.clear()
        this.user = null
        this.status = 'idle'
      }
    },

    switchRole(role: Role) {
      if (this.user && this.user.roles.includes(role)) {
        this.user.activeRole = role
      }
    },

    // Call after the user creates their first skill offering (becoming a
    // Tutor server-side) so the UI's role switcher picks it up immediately.
    grantTutorRole() {
      if (this.user && !this.user.roles.includes('Tutor')) {
        this.user.roles.push('Tutor')
      }
    },

    async logout() {
      const refreshToken = tokenStorage.getRefreshToken()
      tokenStorage.clear()
      this.user = null
      this.status = 'idle'
      if (refreshToken) {
        try {
          await authApi.logout(refreshToken)
        } catch {
          // already logged out locally; ignore network/API errors here
        }
      }
    },
  },
})
