<template>
  <div class="auth-shell">
    <div class="card auth-card stack-sm">
      <div>
        <p class="eyebrow">Welcome</p>
        <h2>Sign in to SkillSwap</h2>
        <p class="muted" style="margin-top: 6px;">
          No real account yet — this stands in for the JWT login your backend
          team is wiring up, so you can move through every screen with a role.
        </p>
      </div>

      <label>
        Name
        <input v-model="name" type="text" placeholder="e.g. Alex Tan" />
      </label>

      <label>
        Faculty
        <select v-model="faculty">
          <option v-for="f in faculties" :key="f">{{ f }}</option>
        </select>
      </label>

      <div>
        <p class="muted" style="margin-bottom: 8px;">I want to sign in as</p>
        <div class="role-grid">
          <button
            v-for="r in roleOptions"
            :key="r.value"
            type="button"
            class="role-option"
            :class="{ active: roles.includes(r.value) }"
            @click="toggleRole(r.value)"
          >
            <strong>{{ r.label }}</strong>
            {{ r.hint }}
          </button>
        </div>
        <p class="faint" style="margin-top: 6px;">Pick both — one account can be a Learner and a Tutor.</p>
      </div>

      <button class="button solid block" :disabled="!canSubmit" @click="submit">
        Continue
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/useAuthStore'
import type { Role } from '@/types'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const name = ref('')
const faculty = ref('Computing')
const faculties = ['Computing', 'Engineering', 'Business']
const roles = ref<Role[]>(['Learner'])

const roleOptions: { value: Role; label: string; hint: string }[] = [
  { value: 'Learner', label: 'Learner', hint: 'Book sessions with tutors' },
  { value: 'Tutor', label: 'Tutor', hint: 'Offer sessions, get paid' },
]

function toggleRole(role: Role) {
  if (roles.value.includes(role)) {
    if (roles.value.length > 1) roles.value = roles.value.filter((r) => r !== role)
  } else {
    roles.value.push(role)
  }
}

const canSubmit = computed(() => name.value.trim().length > 1 && roles.value.length > 0)

function submit() {
  auth.login({ name: name.value.trim(), faculty: faculty.value, roles: roles.value })
  const redirect = route.query.redirect
  router.push(typeof redirect === 'string' ? redirect : { name: 'home' })
}
</script>
