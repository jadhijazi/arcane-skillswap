<template>
  <section class="panel booking-panel">
    <div class="section-header">
      <div>
        <p class="eyebrow">Booking</p>
        <h2>Book a session</h2>
      </div>
    </div>

    <div v-if="!tutor" class="card stack-sm">
      <label>
        Select a tutor
        <select v-model="selectedTutorId">
          <option value="">Choose a tutor</option>
          <option v-for="t in store.tutors" :key="t.id" :value="t.id">{{ t.name }} — {{ t.skills[0] }}</option>
        </select>
      </label>
    </div>

    <div v-else class="booking-grid" style="display: grid; grid-template-columns: 1.4fr 1fr; gap: 16px;">
      <div class="card stack-sm">
        <div class="row-between">
          <div>
            <p class="faint">Selected tutor</p>
            <h3>{{ tutor.name }}</h3>
            <p class="muted">{{ tutor.skills.join(' · ') }} · RM {{ tutor.rate }}/hr</p>
          </div>
          <RouterLink to="/tutors" class="button ghost">Change</RouterLink>
        </div>

        <hr class="divider" />

        <label>
          Skill for this session
          <select v-model="form.skill">
            <option v-for="s in tutor.skills" :key="s">{{ s }}</option>
          </select>
        </label>

        <label>
          Date
          <input type="date" v-model="form.date" :min="today" />
        </label>

        <label>
          Time
          <input type="time" v-model="form.time" />
        </label>

        <label>
          Duration
          <div style="display: flex; gap: 6px;">
            <button
              v-for="d in [1, 1.5, 2, 3]"
              :key="d"
              type="button"
              class="button"
              :class="{ solid: form.duration === d }"
              @click="form.duration = d"
            >{{ d }}hr</button>
          </div>
        </label>

        <label>
          Note for tutor (optional)
          <textarea v-model="form.note" placeholder="e.g. Need help setting up Vuex store and component communication..." />
        </label>
      </div>

      <aside class="card stack-sm">
        <h3>Booking summary</h3>
        <p class="summary-row row-between"><span class="muted">Tutor</span><strong>{{ tutor.name }}</strong></p>
        <p class="summary-row row-between"><span class="muted">Skill</span><strong>{{ form.skill || '—' }}</strong></p>
        <p class="summary-row row-between"><span class="muted">Date</span><strong>{{ form.date || 'Not set' }}</strong></p>
        <p class="summary-row row-between"><span class="muted">Time</span><strong>{{ form.time || 'Not set' }}</strong></p>
        <p class="summary-row row-between"><span class="muted">Duration</span><strong>{{ form.duration }} hr</strong></p>

        <hr class="divider" />

        <p class="summary-row row-between"><span class="muted">Subtotal</span><span>RM {{ pricing.subtotal.toFixed(2) }}</span></p>
        <p class="summary-row row-between"><span class="muted">Platform fee ({{ (store.commissionRate * 100).toFixed(0) }}%)</span><span>RM {{ pricing.fee.toFixed(2) }}</span></p>
        <p class="summary-row row-between" style="font-size: 1.05rem;"><strong>Total</strong><strong>RM {{ pricing.total.toFixed(2) }}</strong></p>

        <button class="button solid block" :disabled="!canConfirm" @click="confirm">Confirm booking</button>
        <RouterLink :to="`/messages/${tutor.id}`" class="button ghost block">Message tutor first</RouterLink>

        <p v-if="confirmed" class="muted" style="color: var(--green);">
          Booking request sent — {{ tutor.name }} will confirm shortly.
        </p>
      </aside>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAppStore } from '@/stores/useAppStore'
import { useAuthStore } from '@/stores/useAuthStore'

const props = withDefaults(defineProps<{ tutorId?: string }>(), { tutorId: '' })
const store = useAppStore()
const auth = useAuthStore()
const router = useRouter()

const selectedTutorId = ref(props.tutorId)
const tutor = computed(() => store.tutorById(selectedTutorId.value))

const today = new Date().toISOString().slice(0, 10)
const form = reactive({ skill: '', date: '', time: '', duration: 1, note: '' })
const confirmed = ref(false)

watch(
  tutor,
  (t) => {
    if (t) form.skill = t.skills[0] ?? ''
  },
  { immediate: true },
)

const pricing = computed(() => {
  if (!tutor.value) return { subtotal: 0, fee: 0, total: 0 }
  return store.priceBreakdown(tutor.value.rate, form.duration)
})

const canConfirm = computed(() => !!tutor.value && !!form.skill && !!form.date && !!form.time)

function confirm() {
  if (!tutor.value) return
  store.createBooking({
    tutorId: tutor.value.id,
    learnerName: auth.user?.name ?? 'Learner',
    skill: form.skill,
    date: form.date,
    time: form.time,
    duration: form.duration,
  })
  confirmed.value = true
  setTimeout(() => router.push('/'), 1100)
}
</script>
