<template>
  <section class="panel booking-panel">
    <div class="section-header">
      <div>
        <p class="eyebrow">Booking</p>
        <h2>Book a session</h2>
      </div>
    </div>

    <!-- No tutor resolved yet (navigated directly to /booking) -->
    <div v-if="!tutor" class="card stack-sm">
      <p class="muted">Navigate here from the <RouterLink to="/tutors">Find a tutor</RouterLink> page to pre-select a tutor.</p>
    </div>

    <div v-else-if="tutor.userSkillId === 0" class="card stack-sm">
      <p class="muted">This tutor's skill listing could not be resolved. Please go back and try again.</p>
      <RouterLink to="/tutors" class="button ghost" style="align-self: flex-start;">Back to tutors</RouterLink>
    </div>

    <div v-else class="booking-grid" style="display: grid; grid-template-columns: 1.4fr 1fr; gap: 16px;">
      <div class="card stack-sm">
        <div class="row-between">
          <div>
            <p class="faint">Selected tutor</p>
            <h3>{{ tutor.name }}</h3>
            <p class="muted">{{ tutor.skillName }} · RM {{ tutor.rate }}/hr</p>
          </div>
          <RouterLink to="/tutors" class="button ghost">Change</RouterLink>
        </div>

        <hr class="divider" />

        <label>
          Date
          <input type="date" v-model="form.date" :min="today" />
        </label>

        <label>
          Start time
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
          <textarea v-model="form.note" placeholder="e.g. Need help setting up my Vue store and API integration…" />
        </label>
      </div>

      <aside class="card stack-sm">
        <h3>Booking summary</h3>
        <p class="summary-row row-between"><span class="muted">Tutor</span><strong>{{ tutor.name }}</strong></p>
        <p class="summary-row row-between"><span class="muted">Skill</span><strong>{{ tutor.skillName }}</strong></p>
        <p class="summary-row row-between"><span class="muted">Date</span><strong>{{ form.date || 'Not set' }}</strong></p>
        <p class="summary-row row-between"><span class="muted">Time</span><strong>{{ form.time || 'Not set' }}</strong></p>
        <p class="summary-row row-between"><span class="muted">Duration</span><strong>{{ form.duration }} hr</strong></p>

        <hr class="divider" />

        <p class="summary-row row-between"><span class="muted">Subtotal</span><span>RM {{ pricing.subtotal.toFixed(2) }}</span></p>
        <p class="summary-row row-between"><span class="muted">Platform fee ({{ (bookings.commissionRate * 100).toFixed(0) }}%)</span><span>RM {{ pricing.fee.toFixed(2) }}</span></p>
        <p class="summary-row row-between" style="font-size: 1.05rem;"><strong>Total</strong><strong>RM {{ pricing.total.toFixed(2) }}</strong></p>

        <p v-if="apiError" class="faint" style="color: var(--red, #e05);">{{ apiError }}</p>

        <button class="button solid block" :disabled="!canConfirm || submitting" @click="confirm">
          {{ submitting ? 'Sending request…' : 'Confirm booking' }}
        </button>
        <RouterLink :to="`/messages/${tutor.userId}`" class="button ghost block">Message tutor first</RouterLink>

        <p v-if="confirmed" class="muted" style="color: var(--green);">
          Booking request sent — {{ tutor.name }} will confirm shortly.
        </p>
      </aside>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useTutorStore } from '@/stores/useTutorStore'
import { useBookingStore } from '@/stores/useBookingStore'

const props = withDefaults(defineProps<{ tutorId?: string }>(), { tutorId: '' })
const store = useTutorStore()
const bookings = useBookingStore()
const router = useRouter()

// tutorId prop is the composite "{userId}:{skillId}" card id set in TutorsView
const tutor = computed(() => store.tutorByCardId(props.tutorId))

const today = new Date().toISOString().slice(0, 10)
const form = reactive({ date: '', time: '', duration: 1, note: '' })
const confirmed = ref(false)
const submitting = ref(false)
const apiError = ref('')

const pricing = computed(() => {
  if (!tutor.value) return { subtotal: 0, fee: 0, total: 0 }
  return bookings.priceBreakdown(tutor.value.rate, form.duration)
})

const canConfirm = computed(() => !!tutor.value && !!form.date && !!form.time)

async function confirm() {
  if (!tutor.value) return
  apiError.value = ''
  submitting.value = true
  try {
    await bookings.createBooking({
      userSkillId: tutor.value.userSkillId,
      date: form.date,
      time: form.time,
      durationHours: form.duration,
    })
    confirmed.value = true
    setTimeout(() => router.push('/'), 1100)
  } catch (e: unknown) {
    apiError.value = e instanceof Error ? e.message : 'Could not create booking. Try again.'
  } finally {
    submitting.value = false
  }
}
</script>
