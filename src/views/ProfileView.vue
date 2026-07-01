<template>
  <section class="panel profile-panel">
    <div class="section-header">
      <div>
        <p class="eyebrow">Profile</p>
        <h2>{{ auth.user?.name }}</h2>
      </div>
    </div>

    <div class="card stack-sm">
      <div class="row-between" style="align-items: flex-start;">
        <div style="display: flex; gap: 14px; align-items: center;">
          <div class="avatar-pill" style="width: 48px; height: 48px; font-size: 1.1rem;">{{ auth.user?.avatar }}</div>
          <div>
            <p class="muted">Role{{ (auth.user?.roles.length ?? 0) > 1 ? 's' : '' }}</p>
            <p style="font-weight: 600;">{{ auth.user?.roles.join(' & ') }}</p>
            <p class="muted" style="margin-top: 6px;">Faculty</p>
            <p style="font-weight: 600;">{{ auth.user?.faculty || '—' }}</p>
          </div>
        </div>
      </div>

      <div class="stat-row">
        <div class="card card-raised">
          <strong style="font-size: 1.3rem;">{{ sessionCount }}</strong>
          <p class="muted">Sessions booked</p>
        </div>
        <div class="card card-raised">
          <strong style="font-size: 1.3rem;">{{ (bookings.commissionRate * 100).toFixed(0) }}%</strong>
          <p class="muted">Platform fee</p>
        </div>
      </div>
    </div>

    <div class="card stack-sm">
      <h3>Bio</h3>
      <textarea v-model="bio" placeholder="Tell learners or tutors a bit about yourself…" />
      <div style="display: flex; gap: 8px; align-items: center;">
        <button class="button solid" :disabled="saving" @click="saveProfile">
          {{ saving ? 'Saving…' : 'Save profile' }}
        </button>
        <p v-if="saveSuccess" class="muted" style="color: var(--green);">Saved!</p>
        <p v-if="saveError" class="faint" style="color: var(--red, #e05);">{{ saveError }}</p>
      </div>
    </div>

    <div class="card stack-sm">
      <h3>Become a tutor</h3>
      <p class="muted">Register a skill you can teach to start appearing in tutor search results.</p>
      <!-- Skill offering form -->
      <div v-if="!tutorStore.skills.length" class="faint">Loading skills…</div>
      <template v-else>
        <label>
          Skill
          <select v-model="offerSkillId">
            <option :value="null">— choose —</option>
            <option v-for="s in tutorStore.skills" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </label>
        <label>
          Hourly rate (RM)
          <input v-model.number="offerRate" type="number" min="5" max="200" />
        </label>
        <label>
          Experience level
          <select v-model="offerLevel">
            <option value="">— select —</option>
            <option>Beginner</option>
            <option>Intermediate</option>
            <option>Advanced</option>
          </select>
        </label>
        <div style="display: flex; gap: 8px; align-items: center;">
          <button class="button solid" :disabled="!offerSkillId || !offerRate || offerLoading" @click="addSkillOffering">
            {{ offerLoading ? 'Saving…' : 'Add offering' }}
          </button>
          <p v-if="offerSuccess" class="muted" style="color: var(--green);">Skill offering created! You're now a tutor.</p>
          <p v-if="offerError" class="faint" style="color: var(--red, #e05);">{{ offerError }}</p>
        </div>
      </template>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/useAuthStore'
import { useBookingStore } from '@/stores/useBookingStore'
import { useTutorStore } from '@/stores/useTutorStore'
import { userApi, userSkillApi } from '@/lib/api'

const auth = useAuthStore()
const bookings = useBookingStore()
const tutorStore = useTutorStore()

const bio = ref(auth.user?.bio ?? '')
const saving = ref(false)
const saveSuccess = ref(false)
const saveError = ref('')

const offerSkillId = ref<number | null>(null)
const offerRate = ref<number>(20)
const offerLevel = ref('')
const offerLoading = ref(false)
const offerSuccess = ref(false)
const offerError = ref('')

const sessionCount = computed(() => bookings.learnerBookings.length)

async function saveProfile() {
  saving.value = true
  saveSuccess.value = false
  saveError.value = ''
  try {
    const updated = await userApi.update({ bio: bio.value })
    if (auth.user) auth.user.bio = updated.user.bio ?? ''
    saveSuccess.value = true
    setTimeout(() => (saveSuccess.value = false), 2000)
  } catch (e: unknown) {
    saveError.value = e instanceof Error ? e.message : 'Could not save.'
  } finally {
    saving.value = false
  }
}

async function addSkillOffering() {
  if (!offerSkillId.value) return
  offerLoading.value = true
  offerSuccess.value = false
  offerError.value = ''
  try {
    await userSkillApi.create({
      skill_id: offerSkillId.value,
      hourly_rate: offerRate.value,
      experience_level: offerLevel.value || undefined,
    })
    auth.grantTutorRole()
    offerSuccess.value = true
  } catch (e: unknown) {
    offerError.value = e instanceof Error ? e.message : 'Could not create offering.'
  } finally {
    offerLoading.value = false
  }
}

onMounted(async () => {
  if (!tutorStore.skills.length) await tutorStore.loadSkills()
  if (auth.user) {
    bookings.fetchLearnerBookings(auth.user.id)
  }
})
</script>
