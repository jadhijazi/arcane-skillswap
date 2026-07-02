<template>
  <section class="panel">

    <!-- Greeting -->
    <div class="home-hero card">
      <div>
        <p class="eyebrow">{{ greeting }}</p>
        <h1>{{ auth.user?.name?.split(' ')[0] ?? 'Welcome' }}</h1>
        <p class="section-header__lead">{{ subline }}</p>
      </div>
      <div class="hero-actions">
        <RouterLink to="/tutors" class="button solid">Find a tutor</RouterLink>
        <RouterLink v-if="auth.activeRole === 'Tutor'" to="/dashboard" class="button ghost">My dashboard</RouterLink>
        <RouterLink v-else to="/profile" class="button ghost">Become a tutor</RouterLink>
      </div>
    </div>

    <!-- Quick stats row -->
    <div class="stat-row">
      <div class="card card-raised">
        <p class="faint">Sessions booked</p>
        <h2>{{ bookingStore.learnerBookings.length }}</h2>
      </div>
      <div class="card card-raised">
        <p class="faint">Unread messages</p>
        <h2>{{ messageStore.unreadCount }}</h2>
      </div>
      <div class="card card-raised" v-if="auth.activeRole === 'Tutor'">
        <p class="faint">Pending requests</p>
        <h2>{{ pendingRequests }}</h2>
      </div>
      <div class="card card-raised" v-else>
        <p class="faint">Tutors available</p>
        <h2>{{ tutorStore.tutors.length || '—' }}</h2>
      </div>
    </div>

    <!-- Two-column layout -->
    <div class="home-grid">

      <!-- Upcoming bookings -->
      <div class="stack-sm">
        <div class="section-header">
          <div>
            <p class="eyebrow">Upcoming</p>
            <h3>Your sessions</h3>
          </div>
          <RouterLink to="/tutors" class="button ghost" style="font-size: 0.8rem;">+ Book one</RouterLink>
        </div>

        <div v-if="bookingStore.status === 'loading'" class="card empty-state">
          <p class="muted">Loading…</p>
        </div>

        <template v-else-if="upcomingBookings.length">
          <div v-for="b in upcomingBookings" :key="b.id" class="card row-between">
            <div>
              <p style="font-weight: 600;">{{ b.tutorName || `Tutor #${b.tutorId}` }}</p>
              <p class="muted">{{ b.skillName || 'Session' }} · {{ b.date }} · {{ b.time }} · {{ b.duration }}hr</p>
            </div>
            <span class="pill" :class="`status-${b.status}`">{{ capitalize(b.status) }}</span>
          </div>
        </template>

        <div v-else class="card empty-state">
          <h3>No sessions yet</h3>
          <p>Find a tutor and book your first session.</p>
          <RouterLink to="/tutors" class="button solid" style="margin-top: 14px; display: inline-flex;">Browse tutors</RouterLink>
        </div>
      </div>

      <!-- Right column -->
      <aside class="stack-sm">

        <!-- Recent messages -->
        <div class="section-header">
          <div>
            <p class="eyebrow">Messages</p>
            <h3>Recent chats</h3>
          </div>
          <RouterLink to="/messages" class="button ghost" style="font-size: 0.8rem;">View all</RouterLink>
        </div>

        <template v-if="recentConvs.length">
          <RouterLink
            v-for="conv in recentConvs"
            :key="conv.otherUserId"
            :to="`/messages/${conv.otherUserId}`"
            class="card"
            style="display: flex; justify-content: space-between; align-items: center; text-decoration: none;"
          >
            <div>
              <p style="font-weight: 600; font-size: 0.9rem;">{{ conv.otherUserName || `User #${conv.otherUserId}` }}</p>
              <p class="faint" style="margin-top: 2px;">{{ conv.messages.at(-1)?.body.slice(0, 42) ?? 'No messages' }}…</p>
            </div>
            <span v-if="messageStore.unreadCount > 0" class="pill skill" style="min-width: 20px; justify-content: center;">!</span>
          </RouterLink>
        </template>

        <div v-else class="card empty-state" style="padding: 24px 16px;">
          <p class="muted">No conversations yet.</p>
          <p class="faint" style="margin-top: 4px;">Message a tutor from their profile.</p>
        </div>

        <!-- Tutor dashboard shortcut (if they have the role) -->
        <template v-if="auth.activeRole === 'Tutor'">
          <div class="section-header" style="margin-top: 8px;">
            <div>
              <p class="eyebrow">You're tutoring</p>
              <h3>Pending requests</h3>
            </div>
            <RouterLink to="/dashboard" class="button ghost" style="font-size: 0.8rem;">Full dashboard</RouterLink>
          </div>

          <template v-if="pendingTutorBookings.length">
            <div v-for="b in pendingTutorBookings" :key="b.id" class="card row-between">
              <div>
                <p style="font-weight: 600; font-size: 0.9rem;">{{ b.learnerName || `Learner #${b.learnerId}` }}</p>
                <p class="muted">{{ b.date }} · {{ b.time }} · {{ b.duration }}hr · RM {{ b.amount.toFixed(2) }}</p>
              </div>
              <div style="display: flex; gap: 6px;">
                <button class="button ghost" style="padding: 6px 12px; font-size: 0.78rem;" @click="act(b.id, 'decline')">Decline</button>
                <button class="button solid" style="padding: 6px 12px; font-size: 0.78rem;" @click="act(b.id, 'accept')">Accept</button>
              </div>
            </div>
          </template>

          <div v-else class="card empty-state" style="padding: 24px 16px;">
            <p class="muted">No pending requests.</p>
          </div>
        </template>

      </aside>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/useAuthStore'
import { useBookingStore } from '@/stores/useBookingStore'
import { useMessageStore } from '@/stores/useMessageStore'
import { useTutorStore } from '@/stores/useTutorStore'

const auth = useAuthStore()
const bookingStore = useBookingStore()
const messageStore = useMessageStore()
const tutorStore = useTutorStore()

const hour = new Date().getHours()
const greeting = hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening'

const subline = computed(() => {
  if (auth.activeRole === 'Tutor') return 'Check your pending requests and earnings below.'
  const n = bookingStore.learnerBookings.length
  return n ? `You have ${n} booking${n > 1 ? 's' : ''} so far.` : 'Ready to learn something new today?'
})

const upcomingBookings = computed(() =>
  bookingStore.learnerBookings
    .filter((b) => ['pending', 'accepted', 'confirmed'].includes(b.status))
    .slice(0, 4)
)

const pendingTutorBookings = computed(() =>
  bookingStore.tutorBookings.filter((b) => b.status === 'pending').slice(0, 3)
)

const pendingRequests = computed(() => pendingTutorBookings.value.length)

const recentConvs = computed(() =>
  messageStore.conversationList.slice(0, 3)
)

function capitalize(s: string) {
  return s.charAt(0).toUpperCase() + s.slice(1)
}

async function act(bookingId: string, action: 'accept' | 'decline') {
  await bookingStore.updateStatus(bookingId, action)
}

onMounted(async () => {
  if (!auth.user) return
  await Promise.all([
    bookingStore.fetchLearnerBookings(auth.user.id),
    bookingStore.fetchTutorBookings(auth.user.id),
    messageStore.fetchUnreadCount(),
  ])
  // Preload conversations with tutors from bookings
  const tutorIds = [...new Set(bookingStore.learnerBookings.map((b) => b.tutorId))]
  await Promise.all(tutorIds.map((id) => messageStore.fetchConversation(auth.user!.id, id).catch(() => {})))
})
</script>

<style scoped>
.home-hero {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  flex-wrap: wrap;
  gap: 20px;
  background: linear-gradient(135deg, var(--surface) 60%, rgba(110, 107, 244, 0.08));
  border-color: rgba(110, 107, 244, 0.2);
  padding: 28px 24px;
}
.home-hero h1 { margin-top: 4px; }
.home-hero .section-header__lead { color: var(--text-muted); margin-top: 6px; font-size: 0.9rem; }
.hero-actions { display: flex; gap: 8px; flex-wrap: wrap; }

.home-grid {
  display: grid;
  grid-template-columns: 1.3fr 1fr;
  gap: 24px;
  align-items: start;
}

@media (max-width: 720px) {
  .home-grid { grid-template-columns: 1fr; }
  .home-hero { flex-direction: column; align-items: flex-start; }
}
</style>