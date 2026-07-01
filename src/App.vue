<template>
  <div class="app-shell">
    <header class="topbar" v-if="auth.isAuthenticated">
      <RouterLink to="/" class="brand">
        <span class="brand-mark">S</span>
        <div>
          <strong>SkillSwap</strong>
          <small>Peer tutoring marketplace</small>
        </div>
      </RouterLink>

      <nav class="nav-links">
        <RouterLink to="/" class="nav-link">Home</RouterLink>
        <RouterLink to="/tutors" class="nav-link">Find tutors</RouterLink>
        <RouterLink v-if="auth.activeRole === 'Tutor'" to="/dashboard" class="nav-link">Dashboard</RouterLink>
        <RouterLink to="/messages" class="nav-link">Messages</RouterLink>
        <RouterLink to="/profile" class="nav-link">Profile</RouterLink>
      </nav>

      <div class="topbar-right">
        <div class="role-switch" v-if="auth.user && auth.user.roles.length > 1">
          <button
            v-for="role in auth.user.roles"
            :key="role"
            :class="{ active: auth.activeRole === role }"
            @click="auth.switchRole(role)"
          >{{ role }}</button>
        </div>
        <button class="avatar-pill" :title="auth.user?.name" @click="handleLogout">
          {{ auth.user?.avatar }}
        </button>
      </div>
    </header>

    <main class="page-shell">
      <RouterView />
    </main>

    <footer class="footer" v-if="auth.isAuthenticated">
      <p>SkillSwap · Built for SCSM2223 · Group arcane</p>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/useAuthStore'

const auth = useAuthStore()
const router = useRouter()

function handleLogout() {
  auth.logout()
  router.push({ name: 'login' })
}
</script>
