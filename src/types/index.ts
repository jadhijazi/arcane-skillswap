export type Role = 'Learner' | 'Tutor' | 'Admin'

export interface AuthUser {
  id: number
  email: string
  name: string
  faculty: string
  year: string
  bio: string
  roles: Role[]
  activeRole: Role
  avatar: string
}

export interface Skill {
  id: number
  name: string
  category: string
}

// One row per (tutor, skill) — this is what /tutors/search actually returns,
// since the backend is skill-centric: a tutor's rate/availability is per skill,
// not a single flat profile like the old mock assumed.
export interface Tutor {
  id: string // `${userId}:${skillId}` — unique per card, stable for routing
  userId: number
  userSkillId: number
  name: string
  faculty: string
  avgRating: number
  totalSessions: number
  rate: number
  skillId: number
  skillName: string
}

export type BookingStatus = 'pending' | 'accepted' | 'declined' | 'confirmed' | 'completed' | 'cancelled'

export interface Booking {
  id: string
  tutorId: number
  learnerId: number
  learnerName: string
  tutorName: string
  skillName: string
  date: string
  time: string
  duration: number
  amount: number
  status: BookingStatus
}

export interface Message {
  id: string
  fromUserId: number
  toUserId: number
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
  skillId?: number
  faculty?: string
  minRating?: number
  maxPrice?: number
}
