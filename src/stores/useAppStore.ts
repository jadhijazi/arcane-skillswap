import { defineStore } from 'pinia'
import type {
  Tutor,
  Booking,
  Message,
  PriceBreakdown,
  WalletSummary,
  TutorFilters,
  BookingStatus,
} from '@/types'

const COMMISSION_RATE = 0.1

let bookingSeq = 100

interface AppState {
  skillCategories: string[]
  faculties: string[]
  tutors: Tutor[]
  bookings: Booking[]
  messages: Message[]
}

interface CreateBookingPayload {
  tutorId: string
  learnerName: string
  skill: string
  date: string
  time: string
  duration: number
}

export const useAppStore = defineStore('app', {
  state: (): AppState => ({
    skillCategories: ['Programming', 'Mathematics', 'Languages', 'Design', 'Soft skills'],
    faculties: ['All faculties', 'Computing', 'Engineering', 'Business'],

    tutors: [
      {
        id: 't-01', name: 'Faiz Amer', faculty: 'Computing', year: 'Year 3 · FSKTM',
        verified: true, rating: 4.9, reviews: 31, rate: 25,
        category: 'Programming', skills: ['Vue.js', 'Laravel', 'REST API', 'MySQL'],
        bio: 'Specialises in Vue.js, Laravel, and REST API design. 2 years industry internship.',
      },
      {
        id: 't-02', name: 'Nur Rina', faculty: 'Computing', year: 'Year 4 · FSKTM',
        verified: false, rating: 4.7, reviews: 18, rate: 20,
        category: 'Programming', skills: ['Python', 'Algorithms', 'Data structures'],
        bio: "Algorithms, Data Structures, and Python. Dean's list 3 consecutive semesters.",
      },
      {
        id: 't-03', name: 'Ahmad Khairul', faculty: 'Computing', year: 'Year 2 · FC',
        verified: true, rating: 4.5, reviews: 9, rate: 15,
        category: 'Programming', skills: ['Java', 'C++', 'OOP'],
        bio: 'OOP in Java and C++. Competed in ACM-ICPC regionals 2024.',
      },
      {
        id: 't-04', name: 'Mia Wong', faculty: 'Business', year: 'Year 3 · FAB',
        verified: true, rating: 4.8, reviews: 22, rate: 22,
        category: 'Soft skills', skills: ['Public speaking', 'Pitching', 'Presentation'],
        bio: 'Toastmasters club president. Helps with pitches, vivas, and presentations.',
      },
      {
        id: 't-05', name: 'Han Lee', faculty: 'Engineering', year: 'Year 4 · FKM',
        verified: false, rating: 4.8, reviews: 14, rate: 28,
        category: 'Design', skills: ['Photoshop', 'UI design', 'Figma'],
        bio: 'Freelance designer on the side. Portfolio reviews and software walkthroughs.',
      },
      {
        id: 't-06', name: 'Sara Lim', faculty: 'Engineering', year: 'Year 3 · FKE',
        verified: true, rating: 4.9, reviews: 27, rate: 30,
        category: 'Mathematics', skills: ['Calculus', 'Linear algebra', 'OOP'],
        bio: 'Calculus and OOP tutoring with a focus on exam technique, not just answers.',
      },
    ],

    // bookings shared across the demo "learner" and "tutor" views
    bookings: [
      {
        id: 'bk-01', tutorId: 't-01', learnerName: 'Nur Rina', skill: 'Vue.js',
        date: '2026-06-20', time: '11:00', duration: 1.5, status: 'confirmed',
      },
      {
        id: 'bk-02', tutorId: 't-01', learnerName: 'Hafiz Zulkifli', skill: 'Laravel REST API',
        date: '2026-06-22', time: '14:00', duration: 2, status: 'pending',
      },
    ],

    messages: [
      { id: 'm-01', tutorId: 't-01', from: 'them', body: 'Hi! Quick q before I book — do you cover Vuex + component communication?', sentAt: '2026-06-18T09:12:00' },
      { id: 'm-02', tutorId: 't-01', from: 'me', body: 'Yep, happy to cover that in the session. Bring your repo if you have one.', sentAt: '2026-06-18T09:30:00' },
    ],
  }),

  getters: {
    commissionRate: (): number => COMMISSION_RATE,

    tutorById: (state) => (id: string): Tutor | null =>
      state.tutors.find((t) => t.id === id) ?? null,

    filteredTutors: (state) => (filters: TutorFilters): Tutor[] => {
      const { category, faculty, query, minRating, maxPrice } = filters
      return state.tutors.filter((t) => {
        if (category && category !== 'All' && t.category !== category) return false
        if (faculty && faculty !== 'All faculties' && t.faculty !== faculty) return false
        if (minRating && t.rating < minRating) return false
        if (maxPrice && t.rate > maxPrice) return false
        if (query) {
          const q = query.toLowerCase()
          const hay = `${t.name} ${t.skills.join(' ')} ${t.bio}`.toLowerCase()
          if (!hay.includes(q)) return false
        }
        return true
      })
    },

    bookingsForTutor: (state) => (tutorId: string): Booking[] =>
      state.bookings.filter((b) => b.tutorId === tutorId),

    walletForTutor: (state) => (tutorId: string): WalletSummary => {
      const confirmed = state.bookings.filter((b) => b.tutorId === tutorId && b.status === 'confirmed')
      const pending = state.bookings.filter((b) => b.tutorId === tutorId && b.status === 'pending')
      const tutor = state.tutors.find((t) => t.id === tutorId)
      const rate = tutor?.rate ?? 0

      const grossOf = (list: Booking[]) => list.reduce((sum, b) => sum + b.duration * rate, 0)
      const grossConfirmed = grossOf(confirmed)
      const grossPending = grossOf(pending)
      const commission = grossConfirmed * COMMISSION_RATE

      return {
        totalEarned: grossConfirmed,
        availableToWithdraw: grossConfirmed - commission,
        pendingClearance: grossPending,
        platformCommission: commission,
        sessionsCompleted: confirmed.length,
      }
    },

    messagesForTutor: (state) => (tutorId: string): Message[] =>
      state.messages
        .filter((m) => m.tutorId === tutorId)
        .sort((a, b) => a.sentAt.localeCompare(b.sentAt)),
  },

  actions: {
    priceBreakdown(rate: number, duration: number): PriceBreakdown {
      const subtotal = rate * duration
      const fee = subtotal * COMMISSION_RATE
      return { subtotal, fee, total: subtotal + fee }
    },

    createBooking(payload: CreateBookingPayload): Booking {
      bookingSeq += 1
      const booking: Booking = {
        id: `bk-${bookingSeq}`,
        ...payload,
        status: 'pending',
      }
      this.bookings.unshift(booking)
      return booking
    },

    setBookingStatus(bookingId: string, status: BookingStatus) {
      const booking = this.bookings.find((b) => b.id === bookingId)
      if (booking) booking.status = status
    },

    sendMessage(tutorId: string, body: string, from: 'me' | 'them' = 'me') {
      this.messages.push({
        id: `m-${Date.now()}`,
        tutorId,
        body,
        from,
        sentAt: new Date().toISOString(),
      })
    },
  },
})
