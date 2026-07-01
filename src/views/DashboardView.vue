<template>
  <section class="panel">
    <div class="section-header">
      <div>
        <p class="eyebrow">Tutor dashboard</p>
        <h2>This month</h2>
      </div>
    </div>

    <div v-if="bookings.status === 'loading'" class="card empty-state">
      <p class="muted">Loading your sessions…</p>
    </div>

    <template v-else>
      <div class="stat-row">
        <div class="card">
          <p class="muted">Earnings</p>
          <h2>RM {{ wallet.totalEarned.toFixed(2) }}</h2>
        </div>
        <div class="card">
          <p class="muted">Sessions done</p>
          <h2>{{ wallet.sessionsCompleted }}</h2>
        </div>
        <div class="card">
          <p class="muted">Avg rating</p>
          <h2>⭐ {{ avgRating }}</h2>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 16px;">
        <div>
          <div class="section-header"><h3>Upcoming & pending sessions</h3></div>
          <div v-if="sessions.length" class="stack-sm">
            <div v-for="b in sessions" :key="b.id" class="card row-between">
              <div>
                <p style="font-weight: 600;">{{ b.learnerName || `Learner #${b.learnerId}` }}</p>
                <p class="muted">{{ b.skillName || 'Session' }} · {{ b.date }} · {{ b.time }} · {{ b.duration }}hr</p>
              </div>
              <div class="row-between" style="gap: 8px;">
                <span class="pill" :class="`status-${b.status}`">{{ capitalize(b.status) }}</span>
                <div v-if="b.status === 'pending'" style="display: flex; gap: 6px;">
                  <button class="button ghost" :disabled="actionLoading === b.id" @click="act(b.id, 'decline')">Decline</button>
                  <button class="button solid" :disabled="actionLoading === b.id" @click="act(b.id, 'accept')">Accept</button>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="card empty-state">
            <h3>No sessions yet</h3>
            <p>Once learners book you, requests will land here for accept/decline.</p>
          </div>
        </div>

        <aside class="stack-sm">
          <div class="card">
            <p class="faint">Accepting bookings</p>
            <div class="row-between" style="margin-top: 4px;">
              <strong>{{ accepting ? 'Open' : 'Paused' }}</strong>
              <button class="button" :class="{ solid: accepting }" @click="accepting = !accepting">
                {{ accepting ? 'On' : 'Off' }}
              </button>
            </div>
          </div>

          <div class="card stack-sm">
            <p class="faint">Wallet</p>
            <h2>RM {{ wallet.availableToWithdraw.toFixed(2) }}</h2>
            <p class="muted">Available to withdraw</p>
            <hr class="divider" />
            <p class="row-between"><span class="muted">Pending clearance</span><strong>RM {{ wallet.pendingClearance.toFixed(2) }}</strong></p>
            <p class="row-between"><span class="muted">Total earned (all-time)</span><strong>RM {{ wallet.totalEarned.toFixed(2) }}</strong></p>
            <p class="row-between"><span class="muted">Platform commission</span><strong>RM {{ wallet.platformCommission.toFixed(2) }}</strong></p>
            <button class="button solid block">Withdraw to bank</button>
          </div>
        </aside>
      </div>
    </template>
    <p v-if="bookings.error" class="faint" style="color: var(--red, #e05);">{{ bookings.error }}</p>
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useBookingStore } from '@/stores/useBookingStore'
import { useAuthStore } from '@/stores/useAuthStore'

const bookings = useBookingStore()
const auth = useAuthStore()

const accepting = ref(true)
const actionLoading = ref<string | null>(null)

// Only show active (pending/accepted) and recent (confirmed/completed) sessions
const sessions = computed(() =>
  bookings.tutorBookings.filter((b) => !['cancelled', 'declined'].includes(b.status))
)

const wallet = computed(() => bookings.walletSummary)

// Avg rating isn't in the booking data so show a placeholder until
// the /users/me endpoint is augmented to return it.
const avgRating = computed(() => {
  const me = auth.user
  return me ? '—' : '—'
})

function capitalize(s: string) {
  return s.charAt(0).toUpperCase() + s.slice(1)
}

async function act(bookingId: string, action: 'accept' | 'decline') {
  actionLoading.value = bookingId
  try {
    await bookings.updateStatus(bookingId, action)
  } finally {
    actionLoading.value = null
  }
}

onMounted(() => {
  if (auth.user) bookings.fetchTutorBookings(auth.user.id)
})
</script>
