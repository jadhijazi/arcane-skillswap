<template>
  <section class="panel">
    <div class="section-header">
      <div>
        <p class="eyebrow">Tutor dashboard</p>
        <h2>This month</h2>
      </div>
    </div>

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
        <h2>⭐ {{ tutor.rating }}</h2>
      </div>
    </div>

    <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 16px;">
      <div>
        <div class="section-header"><h3>Upcoming & pending sessions</h3></div>
        <div v-if="sessions.length" class="stack-sm">
          <div v-for="b in sessions" :key="b.id" class="card row-between">
            <div>
              <p style="font-weight: 600;">{{ b.learnerName }}</p>
              <p class="muted">{{ b.skill }} · {{ b.date }} · {{ b.time }} · {{ b.duration }}hr</p>
            </div>
            <div class="row-between" style="gap: 8px;">
              <span class="pill" :class="`status-${b.status}`">{{ statusLabel(b.status) }}</span>
              <div v-if="b.status === 'pending'" style="display: flex; gap: 6px;">
                <button class="button ghost" @click="store.setBookingStatus(b.id, 'declined')">Decline</button>
                <button class="button solid" @click="store.setBookingStatus(b.id, 'confirmed')">Accept</button>
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
  </section>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useAppStore } from '@/stores/useAppStore'
import { useAuthStore } from '@/stores/useAuthStore'

const store = useAppStore()
const auth = useAuthStore()

// demo fallback: tie the logged-in tutor to a seeded tutor record so the
// dashboard has data to show even though there's no real backend yet
const tutor = computed(() => store.tutors.find((t) => t.name === auth.user?.name) ?? store.tutors[0]!)

const sessions = computed(() => store.bookingsForTutor(tutor.value.id))
const wallet = computed(() => store.walletForTutor(tutor.value.id))
const accepting = ref(true)

function statusLabel(s: string) {
  return s.charAt(0).toUpperCase() + s.slice(1)
}
</script>
