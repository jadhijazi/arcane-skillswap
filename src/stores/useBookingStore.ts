import { defineStore } from 'pinia'
import { bookingApi, type BackendBooking } from '@/lib/api'
import { ApiError } from '@/lib/http'
import type { Booking, BookingStatus } from '@/types'

interface BookingState {
  learnerBookings: Booking[]
  tutorBookings: Booking[]
  status: 'idle' | 'loading' | 'ready' | 'error'
  error: string | null
}

function isoToDateParts(iso: string): { date: string; time: string; duration: number } {
  const start = new Date(iso)
  return {
    date: start.toISOString().slice(0, 10),
    time: start.toTimeString().slice(0, 5),
    duration: 1, // backend stores start+end times; duration is derived by views if needed
  }
}

function toBooking(b: BackendBooking, myUserId: number): Booking {
  const parts = isoToDateParts(b.start_time)
  const endParts = isoToDateParts(b.end_time)
  const startMs = new Date(b.start_time).getTime()
  const endMs = new Date(b.end_time).getTime()
  const hours = (endMs - startMs) / (1000 * 60 * 60)
  return {
    id: String(b.id),
    tutorId: b.tutor_id,
    learnerId: b.learner_id,
    learnerName: '',   // resolved later from profile if needed
    tutorName: '',     // resolved later from profile if needed
    skillName: '',     // backend doesn't embed skill name in booking rows
    date: parts.date,
    time: parts.time,
    duration: Math.round(hours * 2) / 2, // round to nearest 0.5hr
    amount: b.amount,
    status: b.status as BookingStatus,
  }
}

const COMMISSION_RATE = 0.1

export const useBookingStore = defineStore('booking', {
  state: (): BookingState => ({
    learnerBookings: [],
    tutorBookings: [],
    status: 'idle',
    error: null,
  }),

  getters: {
    commissionRate: (): number => COMMISSION_RATE,

    walletSummary: (state) => {
      const confirmed = state.tutorBookings.filter((b) => b.status === 'confirmed' || b.status === 'completed')
      const pending = state.tutorBookings.filter((b) => b.status === 'pending' || b.status === 'accepted')
      const totalEarned = confirmed.reduce((s, b) => s + b.amount, 0)
      const pendingClearance = pending.reduce((s, b) => s + b.amount, 0)
      const commission = totalEarned * COMMISSION_RATE
      return {
        totalEarned,
        availableToWithdraw: totalEarned - commission,
        pendingClearance,
        platformCommission: commission,
        sessionsCompleted: confirmed.length,
      }
    },
  },

  actions: {
    priceBreakdown(rate: number, durationHours: number) {
      const subtotal = rate * durationHours
      const fee = subtotal * COMMISSION_RATE
      return { subtotal, fee, total: subtotal + fee }
    },

    async fetchLearnerBookings(myUserId: number) {
      this.status = 'loading'
      this.error = null
      try {
        const { bookings } = await bookingApi.learnerBookings()
        this.learnerBookings = bookings.map((b) => toBooking(b, myUserId))
        this.status = 'ready'
      } catch (e) {
        this.status = 'error'
        this.error = e instanceof ApiError ? e.message : 'Could not load your bookings.'
      }
    },

    async fetchTutorBookings(myUserId: number) {
      this.status = 'loading'
      this.error = null
      try {
        const { bookings } = await bookingApi.tutorBookings()
        this.tutorBookings = bookings.map((b) => toBooking(b, myUserId))
        this.status = 'ready'
      } catch (e) {
        this.status = 'error'
        this.error = e instanceof ApiError ? e.message : 'Could not load your sessions.'
      }
    },

    // Called from BookingView. start_time / end_time are built from the date,
    // time, and duration the learner picked in the form.
    async createBooking(payload: {
      userSkillId: number
      date: string
      time: string
      durationHours: number
    }): Promise<Booking> {
      const start = new Date(`${payload.date}T${payload.time}:00`)
      const end = new Date(start.getTime() + payload.durationHours * 60 * 60 * 1000)
      const fmt = (d: Date) => d.toISOString().slice(0, 19).replace('T', ' ')

      const { booking } = await bookingApi.create({
        user_skill_id: payload.userSkillId,
        start_time: fmt(start),
        end_time: fmt(end),
      })
      const mapped = toBooking(booking, 0)
      this.learnerBookings.unshift(mapped)
      return mapped
    },

    async updateStatus(bookingId: string, action: 'accept' | 'decline' | 'confirm' | 'complete' | 'cancel') {
      const id = Number(bookingId)
      const apiCalls = {
        accept: () => bookingApi.accept(id),
        decline: () => bookingApi.decline(id),
        confirm: () => bookingApi.confirm(id),
        complete: () => bookingApi.complete(id),
        cancel: () => bookingApi.cancel(id),
      }
      const { booking } = await apiCalls[action]()
      const mapped = toBooking(booking, 0)
      // update whichever list has it
      for (const list of [this.learnerBookings, this.tutorBookings]) {
        const idx = list.findIndex((b) => b.id === bookingId)
        if (idx !== -1) list[idx] = { ...list[idx], status: mapped.status }
      }
    },
  },
})
