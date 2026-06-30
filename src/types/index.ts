export type Role = 'Learner' | 'Tutor'

export interface AuthUser {
  id: string
  name: string
  faculty: string
  roles: Role[]
  activeRole: Role
  avatar: string
}

export interface Tutor {
  id: string
  name: string
  faculty: string
  year: string
  verified: boolean
  rating: number
  reviews: number
  rate: number
  category: string
  skills: string[]
  bio: string
}

export type BookingStatus = 'pending' | 'confirmed' | 'declined'

export interface Booking {
  id: string
  tutorId: string
  learnerName: string
  skill: string
  date: string
  time: string
  duration: number
  status: BookingStatus
}

export interface Message {
  id: string
  tutorId: string
  from: 'me' | 'them'
  body: string
  sentAt: string
}

export interface PriceBreakdown {
  subtotal: number
  fee: number
  total: number
}

export interface WalletSummary {
  totalEarned: number
  availableToWithdraw: number
  pendingClearance: number
  platformCommission: number
  sessionsCompleted: number
}

export interface TutorFilters {
  query?: string
  category?: string
  faculty?: string
  minRating?: number
  maxPrice?: number
}
