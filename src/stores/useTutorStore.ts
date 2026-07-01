import { defineStore } from 'pinia'
import { skillApi, tutorApi, userSkillApi, type BackendSkill, type BackendTutorSearchResult } from '@/lib/api'
import { ApiError } from '@/lib/http'
import type { Skill, Tutor, TutorFilters } from '@/types'

interface TutorState {
  skills: Skill[]
  tutors: Tutor[]
  status: 'idle' | 'loading' | 'ready' | 'error'
  error: string | null
}

function toTutor(row: BackendTutorSearchResult, skillId: number, skillName: string): Tutor {
  return {
    id: `${row.id}:${skillId}`,
    userId: row.id,
    userSkillId: 0, // filled in by hydrateUserSkillIds() — needed to create a booking
    name: `${row.first_name} ${row.last_name}`.trim(),
    faculty: row.faculty ?? '',
    avgRating: row.avg_rating,
    totalSessions: row.total_sessions,
    rate: row.hourly_rate,
    skillId,
    skillName,
  }
}

export const useTutorStore = defineStore('tutor', {
  state: (): TutorState => ({
    skills: [],
    tutors: [],
    status: 'idle',
    error: null,
  }),

  getters: {
    categories: (state): string[] => [...new Set(state.skills.map((s) => s.category).filter(Boolean))],
    skillsInCategory: (state) => (category: string): Skill[] =>
      category && category !== 'All'
        ? state.skills.filter((s) => s.category === category)
        : state.skills,
    tutorByCardId: (state) => (id: string): Tutor | null => state.tutors.find((t) => t.id === id) ?? null,
  },

  actions: {
    async loadSkills() {
      try {
        const { skills } = await skillApi.list(1, 200)
        this.skills = skills.map((s: BackendSkill) => ({ id: s.id, name: s.name, category: s.category ?? 'Other' }))
      } catch (e) {
        this.error = e instanceof ApiError ? e.message : 'Could not load skills.'
      }
    },

    // skill_id is required by the backend's /tutors/search — there's no
    // "browse everything" endpoint, so callers must resolve a skill first
    // (TutorsView does this via the category/skill dropdowns).
    async searchTutors(filters: TutorFilters) {
      if (!filters.skillId) {
        this.tutors = []
        return
      }
      this.status = 'loading'
      this.error = null
      try {
        const skill = this.skills.find((s) => s.id === filters.skillId)
        const { tutors } = await tutorApi.search({
          skill_id: filters.skillId,
          faculty: filters.faculty && filters.faculty !== 'All faculties' ? filters.faculty : undefined,
          min_rating: filters.minRating || undefined,
          max_rate: filters.maxPrice || undefined,
          sort: 'rating',
        })
        let rows = tutors.map((row) => toTutor(row, filters.skillId!, skill?.name ?? ''))

        if (filters.query) {
          const q = filters.query.toLowerCase()
          rows = rows.filter((t) => `${t.name} ${t.skillName}`.toLowerCase().includes(q))
        }

        await this.hydrateUserSkillIds(rows)
        this.tutors = rows
        this.status = 'ready'
      } catch (e) {
        this.status = 'error'
        this.error = e instanceof ApiError ? e.message : 'Could not load tutors.'
      }
    },

    // Booking requires a user_skill_id, but /tutors/search only returns the
    // tutor's user id. Resolve it from each tutor's skill listing.
    async hydrateUserSkillIds(rows: Tutor[]) {
      await Promise.all(
        rows.map(async (t) => {
          try {
            const { user_skills } = await userSkillApi.getByUser(t.userId)
            const match = user_skills.find((us) => us.skill_id === t.skillId)
            if (match) t.userSkillId = match.id
          } catch {
            // leave userSkillId as 0; BookingView will show an error if the
            // learner tries to book this row
          }
        }),
      )
    },
  },
})
