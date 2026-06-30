<template>
  <section class="panel">
    <div class="section-header">
      <div>
        <p class="eyebrow">Messages</p>
        <h2>{{ activeTutor ? activeTutor.name : 'Conversations' }}</h2>
      </div>
    </div>

    <div style="display: grid; grid-template-columns: 260px 1fr; gap: 16px;">
      <div class="stack-sm">
        <RouterLink
          v-for="t in store.tutors"
          :key="t.id"
          :to="`/messages/${t.id}`"
          class="card"
          :class="{ 'card-raised': t.id === activeTutor?.id }"
          style="text-decoration: none; display: flex; justify-content: space-between; align-items: center;"
        >
          <div>
            <p style="font-weight: 600;">{{ t.name }}</p>
            <p class="faint">{{ t.skills[0] }}</p>
          </div>
        </RouterLink>
      </div>

      <div class="card" style="display: flex; flex-direction: column; min-height: 420px;">
        <template v-if="activeTutor">
          <div style="flex: 1; display: flex; flex-direction: column; gap: 10px; overflow-y: auto; padding-bottom: 12px;">
            <div
              v-for="m in thread"
              :key="m.id"
              :style="{ alignSelf: m.from === 'me' ? 'flex-end' : 'flex-start', maxWidth: '75%' }"
            >
              <div
                class="card"
                :style="{ background: m.from === 'me' ? 'var(--indigo-soft)' : 'var(--surface-raised)', padding: '10px 14px' }"
              >
                <p style="font-size: 0.88rem;">{{ m.body }}</p>
              </div>
              <p class="faint" style="margin-top: 3px; text-align: right;">{{ formatTime(m.sentAt) }}</p>
            </div>
            <p v-if="!thread.length" class="muted">No messages yet — say hi before booking.</p>
          </div>
          <form class="row-between" style="gap: 8px;" @submit.prevent="send">
            <input v-model="draft" type="text" placeholder="Write a message..." style="flex: 1;" />
            <button class="button solid" type="submit" :disabled="!draft.trim()">Send</button>
          </form>
        </template>
        <div v-else class="empty-state">
          <h3>Pick a conversation</h3>
          <p>Select a tutor on the left to view or start a chat.</p>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useAppStore } from '@/stores/useAppStore'

const props = withDefaults(defineProps<{ tutorId?: string }>(), { tutorId: '' })
const store = useAppStore()

const activeTutor = computed(() => store.tutorById(props.tutorId))
const thread = computed(() => (activeTutor.value ? store.messagesForTutor(activeTutor.value.id) : []))
const draft = ref('')

function send() {
  if (!draft.value.trim() || !activeTutor.value) return
  store.sendMessage(activeTutor.value.id, draft.value.trim(), 'me')
  draft.value = ''
}

function formatTime(iso: string) {
  return new Date(iso).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>
