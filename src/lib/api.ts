// Typed wrappers around each backend endpoint used by the frontend.
// Field names intentionally mirror server/API_DOCUMENTATION.md verbatim
// (snake_case) so this file stays a faithful map of the real contract;
// the Pinia stores are responsible for adapting these into UI-friendly shapes.
import { http } from './http'

// ---------- shared backend shapes ----------

export interface BackendUser {
  id: number
  email: string
  first_name: string
  last_name: string
  bio?: string | null
  profile_photo?: string | null
  faculty?: string | null
  year?: string | null
  is_active?: number
}

export interface BackendSkill {
  id: number
  name: string
  category: string | null
}

export interface BackendTutorSearchResult {
  id: number
  first_name: string
  last_name: string
  profile_photo?: string | null
  faculty?: string | null
  avg_rating: number
  total_sessions: number
  hourly_rate: number
}

export interface BackendUserSkill {
  id: number
  user_id: number
  skill_id: number
  hourly_rate: number
  experience_level?: string | null
  description?: string | null
}

export type BackendBookingStatus =
  | 'pending'
  | 'accepted'
  | 'declined'
  | 'confirmed'
  | 'completed'
  | 'cancelled'

export interface BackendBooking {
  id: number
  learner_id: number
  tutor_id: number
  user_skill_id: number
  start_time: string
  end_time: string
  status: BackendBookingStatus
  amount: number
  learner_name?: string
  tutor_name?: string
  skill_name?: string
}

export interface BackendMessage {
  id: number
  sender_id: number
  recipient_id: number
  content: string
  is_read: boolean
  created_at: string
}

// ---------- AUTH ----------

export interface RegisterPayload {
  email: string
  password: string
  first_name: string
  last_name: string
  faculty?: string
  year?: string
}

export interface AuthResult {
  user: BackendUser
  access_token: string
  refresh_token: string
  roles: ('Learner' | 'Tutor' | 'Admin')[]
}

export const authApi = {
  register: (payload: RegisterPayload) => http.post<AuthResult>('/auth/register', payload, false),
  login: (email: string, password: string) =>
    http.post<AuthResult>('/auth/login', { email, password }, false),
  logout: (refreshToken: string) => http.post<object>('/auth/logout', { refresh_token: refreshToken }, false),
  forgotPassword: (email: string) =>
    http.post<{ message: string; reset_token?: string }>('/auth/forgot-password', { email }, false),
  resetPassword: (resetToken: string, newPassword: string) =>
    http.post<object>('/auth/reset-password', { reset_token: resetToken, new_password: newPassword }, false),
}

// ---------- USERS ----------

export const userApi = {
  me: () => http.get<{ user: BackendUser; average_rating: number; total_reviews: number }>('/users/me'),
  getProfile: (id: number) =>
    http.get<{ user: BackendUser; average_rating: number; total_reviews: number }>(`/users/${id}`, undefined, false),
  update: (payload: Partial<Pick<BackendUser, 'first_name' | 'last_name' | 'bio' | 'faculty' | 'year' | 'profile_photo'>>) =>
    http.patch<{ user: BackendUser }>('/users/me', payload),
  changePassword: (oldPassword: string, newPassword: string) =>
    http.post<object>('/users/change-password', { old_password: oldPassword, new_password: newPassword }),
}

// ---------- SKILLS ----------

export const skillApi = {
  list: (page = 1, perPage = 100) =>
    http.get<{ skills: BackendSkill[]; total: number }>('/skills', { page, per_page: perPage }, false),
  search: (q: string) => http.get<{ skills: BackendSkill[] }>('/skills/search', { q }, false),
}

// ---------- USER SKILLS (an account becomes a Tutor by creating one of these) ----------

export const userSkillApi = {
  create: (payload: { skill_id: number; hourly_rate: number; experience_level?: string; description?: string }) =>
    http.post<{ user_skill: BackendUserSkill }>('/user-skills', payload),
  getByUser: (userId: number) =>
    http.get<{ user_skills: BackendUserSkill[] }>(`/users/${userId}/skills`, undefined, false),
  update: (id: number, payload: Partial<{ hourly_rate: number; experience_level: string; description: string }>) =>
    http.patch<{ user_skill: BackendUserSkill }>(`/user-skills/${id}`, payload),
  delete: (id: number) => http.delete<object>(`/user-skills/${id}`),
}

// ---------- TUTOR DISCOVERY ----------

export interface TutorSearchParams {
  skill_id?: number
  faculty?: string
  min_rating?: number
  max_rate?: number
  min_rate?: number
  experience_level?: string
  sort?: 'rating' | 'price' | 'popular'
  page?: number
  per_page?: number
}

export const tutorApi = {
  search: (params: TutorSearchParams) =>
    http.get<{ tutors: BackendTutorSearchResult[]; total: number; page: number; per_page: number; pages: number }>(
      '/tutors/search',
      params as Record<string, string | number | undefined>,
      false,
    ),
}

// ---------- BOOKINGS ----------

export const bookingApi = {
  create: (payload: { user_skill_id: number; start_time: string; end_time: string }) =>
    http.post<{ booking: BackendBooking }>('/bookings', payload),
  get: (id: number) => http.get<{ booking: BackendBooking }>(`/bookings/${id}`, undefined, false),
  learnerBookings: (page = 1, perPage = 50) =>
    http.get<{ bookings: BackendBooking[] }>('/bookings/learner', { page, per_page: perPage }),
  tutorBookings: (page = 1, perPage = 50) =>
    http.get<{ bookings: BackendBooking[] }>('/bookings/tutor', { page, per_page: perPage }),
  accept: (id: number) => http.patch<{ booking: BackendBooking }>(`/bookings/${id}/accept`),
  decline: (id: number) => http.patch<{ booking: BackendBooking }>(`/bookings/${id}/decline`),
  confirm: (id: number) => http.patch<{ booking: BackendBooking }>(`/bookings/${id}/confirm`),
  complete: (id: number) => http.patch<{ booking: BackendBooking }>(`/bookings/${id}/complete`),
  cancel: (id: number) => http.patch<{ booking: BackendBooking }>(`/bookings/${id}/cancel`),
}

// ---------- MESSAGES ----------

export const messageApi = {
  send: (recipientId: number, content: string) =>
    http.post<{ message: BackendMessage }>('/messages', { recipient_id: recipientId, content }),
  conversation: (otherUserId: number, page = 1, perPage = 50) =>
    http.get<{ messages: BackendMessage[]; other_user_id: number }>(`/conversations/${otherUserId}`, {
      page,
      per_page: perPage,
    }),
  unreadCount: () => http.get<{ unread_count: number }>('/messages/unread-count'),
  markConversationRead: (senderId: number) => http.patch<object>(`/conversations/${senderId}/read`),
}

// ---------- WALLET ----------

export const walletApi = {
  balance: () => http.get<{ wallet: { id: number; user_id: number; balance: number; currency: string }; balance: number }>('/wallet'),
  transactions: (page = 1, perPage = 50) =>
    http.get<{
      transactions: { id: number; amount: number; type: 'credit' | 'debit'; description: string; created_at: string }[]
      balance: number
    }>('/wallet/transactions', { page, per_page: perPage }),
}

// ---------- REVIEWS ----------

export const reviewApi = {
  create: (bookingId: number, rating: number, comment?: string) =>
    http.post<{ review: object }>('/reviews', { booking_id: bookingId, rating, comment }),
  tutorReviews: (tutorId: number, page = 1, perPage = 50) =>
    http.get<{ reviews: object[]; average_rating: number; total_reviews: number }>(
      `/tutors/${tutorId}/reviews`,
      { page, per_page: perPage },
      false,
    ),
}