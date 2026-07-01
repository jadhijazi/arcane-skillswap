<template>
  <section class="panel">
    <div class="section-header">
      <div>
        <p class="eyebrow">Messages</p>
        <h2>{{ activeConv ? activeConv.otherUserName || `User #${activeConv.otherUserId}` : 'Conversations' }}</h2>
      </div>
    </div>

    <div style="display: grid; grid-template-columns: 260px 1fr; gap: 16px;">
      <!-- Sidebar: list of known conversations -->
      <div class="stack-sm">
        <p v-if="!messages.conversationList.length" class="faint" style="padding: 8px;">
          No conversations yet. Message a tutor from their profile.
        </p>
        <RouterLink
          v-for="conv in messages.conversationList"
          :key="conv.otherUserId"
          :to="`/messages/${conv.otherUserId}`"
          class="card"
          :class="{ 'card-raised': conv.otherUserId === activeUserId }"
          style="text-decoration: none; display: flex; justify-content: space-between; align-items: center;"
        >
          <div>
            <p style="font-weight: 600;">{{ conv.otherUserName || `User #${conv.otherUserId}` }}</p>
            <p class="faint">{{ conv.messages.at(-1)?.body.slice(0, 40) ?? 'No messages yet' }}</p>
          </div>
        </RouterLink>
      </div>

      <!-- Main thread area -->
      <div class="card" style="display: flex; flex-direction: column; min-height: 420px;">
        <template v-if="activeUserId">
          <div v-if="activeConv?.status === 'loading'" class="empty-state">
            <p class="muted">Loading…</p>
          </div>
          <div v-else style="flex: 1; display: flex; flex-direction: column; gap: 10px; overflow-y: auto; padding-bottom: 12px;">
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
          <div class="row-between" style="gap: 8px; margin-top: 12px;">
            <input v-model="draft" type="text" placeholder="Write a message…" style="flex: 1;" @keydown.enter="send" />
            <button class="button solid" :disabled="!draft.trim() || sending" @click="send">
              {{ sending ? '…' : 'Send' }}
            </button>
          </div>
        </template>
        <div v-else class="empty-state">
          <h3>Pick a conversation</h3>
          <p>Select a thread on the left to view or start a chat.</p>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useMessageStore } from '@/stores/useMessageStore'
import { useAuthStore } from '@/stores/useAuthStore'

const props = withDefaults(defineProps<{ tutorId?: string }>(), { tutorId: '' })
const messages = useMessageStore()
const auth = useAuthStore()

const draft = ref('')
const sending = ref(false)

const activeUserId = computed(() => (props.tutorId ? Number(props.tutorId) : null))
const activeConv = computed(() => (activeUserId.value ? messages.conversations[activeUserId.value] : null))
const thread = computed(() => (activeUserId.value ? messages.threadWith(activeUserId.value) : []))

async function loadConversation(userId: number) {
  if (!auth.user) return
  await messages.fetchConversation(auth.user.id, userId)
}

watch(() => props.tutorId, (id) => {
  if (id) loadConversation(Number(id))
}, { immediate: true })

async function send() {
  if (!draft.value.trim() || !activeUserId.value || !auth.user) return
  sending.value = true
  try {
    await messages.send(auth.user.id, activeUserId.value, draft.value.trim())
    draft.value = ''
  } finally {
    sending.value = false
  }
}

function formatTime(iso: string) {
  return new Date(iso).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

onMounted(() => messages.fetchUnreadCount())
</script>
