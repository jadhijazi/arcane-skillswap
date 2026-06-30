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
            <p style="font-weight: 600;">{{ auth.user?.faculty }}</p>
          </div>
        </div>
      </div>

      <div class="stat-row">
        <div class="card card-raised">
          <strong style="font-size: 1.3rem;">{{ tutorRecord ? tutorRecord.rating : '—' }}</strong>
          <p class="muted">Average rating</p>
        </div>
        <div class="card card-raised">
          <strong style="font-size: 1.3rem;">{{ sessionCount }}</strong>
          <p class="muted">Sessions booked</p>
        </div>
        <div class="card card-raised">
          <strong style="font-size: 1.3rem;">{{ (store.commissionRate * 100).toFixed(0) }}%</strong>
          <p class="muted">Platform fee</p>
        </div>
      </div>
    </div>

    <div v-if="tutorRecord" class="card stack-sm">
      <h3>Tutor skills</h3>
      <div style="display: flex; flex-wrap: wrap; gap: 6px;">
        <span v-for="s in tutorRecord.skills" :key="s" class="pill skill">{{ s }}</span>
      </div>
      <p class="muted">RM {{ tutorRecord.rate }}/hr · {{ tutorRecord.category }}</p>
    </div>

    <div class="card stack-sm">
      <h3>Bio</h3>
      <textarea v-model="bio" placeholder="Tell learners or tutors a bit about yourself..." />
      <button class="button solid" style="align-self: flex-start;" @click="saved = true">Save profile</button>
      <p v-if="saved" class="muted" style="color: var(--green);">Saved locally (no backend wired up yet).</p>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/useAuthStore'
import { useAppStore } from '@/stores/useAppStore'

const auth = useAuthStore()
const store = useAppStore()

const tutorRecord = computed(() => store.tutors.find((t) => t.name === auth.user?.name))
const sessionCount = computed(() => store.bookings.filter((b) => b.learnerName === auth.user?.name).length)

const bio = ref('')
const saved = ref(false)
</script>
