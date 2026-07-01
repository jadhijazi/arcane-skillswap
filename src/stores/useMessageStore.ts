import { defineStore } from 'pinia'
import { messageApi, userApi, type BackendMessage } from '@/lib/api'
import { ApiError } from '@/lib/http'
import type { Message } from '@/types'

interface Conversation {
  otherUserId: number
  otherUserName: string
  messages: Message[]
  status: 'idle' | 'loading' | 'ready' | 'error'
}

interface MessageState {
  conversations: Record<number, Conversation>
  unreadCount: number
}

function toMessage(m: BackendMessage, myUserId: number): Message {
  return {
    id: String(m.id),
    fromUserId: m.sender_id,
    toUserId: m.recipient_id,
    from: m.sender_id === myUserId ? 'me' : 'them',
    body: m.content,
    sentAt: m.created_at,
  }
}

export const useMessageStore = defineStore('message', {
  state: (): MessageState => ({
    conversations: {},
    unreadCount: 0,
  }),

  getters: {
    conversationList: (state): Conversation[] => Object.values(state.conversations),
    threadWith: (state) => (userId: number): Message[] =>
      state.conversations[userId]?.messages ?? [],
  },

  actions: {
    async fetchConversation(myUserId: number, otherUserId: number) {
      if (!this.conversations[otherUserId]) {
        this.conversations[otherUserId] = {
          otherUserId,
          otherUserName: '',
          messages: [],
          status: 'idle',
        }
      }
      const conv = this.conversations[otherUserId]!
      conv.status = 'loading'
      try {
        const [{ messages }, profileResult] = await Promise.all([
          messageApi.conversation(otherUserId),
          userApi.getProfile(otherUserId).catch(() => null),
        ])
        if (profileResult) {
          const u = profileResult.user
          conv.otherUserName = `${u.first_name} ${u.last_name}`.trim() || String(otherUserId)
        }
        conv.messages = messages
          .map((m) => toMessage(m, myUserId))
          .sort((a, b) => a.sentAt.localeCompare(b.sentAt))
        conv.status = 'ready'
        // mark as read now that we've opened the thread
        await messageApi.markConversationRead(otherUserId).catch(() => {})
        this.unreadCount = Math.max(0, this.unreadCount - 1)
      } catch (e) {
        conv.status = 'error'
        throw e
      }
    },

    async send(myUserId: number, recipientId: number, content: string) {
      const { message } = await messageApi.send(recipientId, content)
      const mapped = toMessage(message, myUserId)
      if (!this.conversations[recipientId]) {
        this.conversations[recipientId] = { otherUserId: recipientId, otherUserName: '', messages: [], status: 'ready' }
      }
      this.conversations[recipientId]!.messages.push(mapped)
    },

    async fetchUnreadCount() {
      try {
        const { unread_count } = await messageApi.unreadCount()
        this.unreadCount = unread_count
      } catch {
        // non-fatal
      }
    },
  },
})
