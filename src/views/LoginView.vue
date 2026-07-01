<template>
  <div class="auth-shell">
    <div class="card auth-card stack-sm">
      <div>
        <p class="eyebrow">Welcome</p>
        <h2>{{ mode === 'login' ? 'Sign in to SkillSwap' : 'Create your account' }}</h2>
      </div>

      <!-- Register fields -->
      <template v-if="mode === 'register'">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
          <label>
            First name
            <input v-model="firstName" type="text" placeholder="Ali" autocomplete="given-name" />
          </label>
          <label>
            Last name
            <input v-model="lastName" type="text" placeholder="Hassan" autocomplete="family-name" />
          </label>
        </div>
        <label>
          Faculty
          <select v-model="faculty">
            <option value="">— select —</option>
            <option v-for="f in faculties" :key="f">{{ f }}</option>
          </select>
        </label>
        <label>
          Year
          <select v-model="year">
            <option value="">— select —</option>
            <option v-for="y in ['1','2','3','4']" :key="y">{{ y }}</option>
          </select>
        </label>
      </template>

      <label>
        Email
        <input v-model="email" type="email" placeholder="you@example.com" autocomplete="email" />
      </label>
      <label>
        Password
        <input v-model="password" type="password" placeholder="••••••••" autocomplete="current-password" />
      </label>

      <p v-if="error" class="faint" style="color: var(--red, #e05);">{{ error }}</p>

      <button class="button solid block" :disabled="!canSubmit || loading" @click="submit">
        {{ loading ? 'Please wait…' : mode === 'login' ? 'Sign in' : 'Create account' }}
      </button>

      <p class="faint" style="text-align: center;">
        {{ mode === 'login' ? "Don't have an account?" : 'Already have an account?' }}
        <button
          class="button ghost"
          style="display: inline; padding: 0; font-size: inherit;"
          @click="toggleMode"
        >{{ mode === 'login' ? 'Register' : 'Sign in' }}</button>
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/useAuthStore'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const mode = ref<'login' | 'register'>('login')
const email = ref('')
const password = ref('')
const firstName = ref('')
const lastName = ref('')
const faculty = ref('')
const year = ref('')
const loading = ref(false)
const error = ref('')

const faculties = ['Computing', 'Engineering', 'Business', 'Science', 'Medicine', 'Law', 'Arts']

const canSubmit = computed(() => {
  if (!email.value.trim() || password.value.length < 6) return false
  if (mode.value === 'register' && (!firstName.value.trim() || !lastName.value.trim())) return false
  return true
})

function toggleMode() {
  mode.value = mode.value === 'login' ? 'register' : 'login'
  error.value = ''
}

async function submit() {
  error.value = ''
  loading.value = true
  try {
    if (mode.value === 'login') {
      await auth.login(email.value.trim(), password.value)
    } else {
      await auth.register({
        email: email.value.trim(),
        password: password.value,
        firstName: firstName.value.trim(),
        lastName: lastName.value.trim(),
        faculty: faculty.value || undefined,
        year: year.value || undefined,
      })
    }
    const redirect = route.query.redirect
    router.push(typeof redirect === 'string' ? redirect : { name: 'home' })
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Something went wrong. Try again.'
  } finally {
    loading.value = false
  }
}
</script>
