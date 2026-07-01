#!/usr/bin/env node
// Mock API server for local frontend testing.
// Mirrors the real PHP backend's envelope format and endpoint shapes exactly.
// Requires Node 18+ (built-in fetch/http). No npm install needed.
//
// Usage:
//   node mock-server.mjs
//
// Then set in .env.local:
//   VITE_API_BASE_URL=http://localhost:3001/api

import { createServer } from 'node:http'

const PORT = 3001

// ─── Seed data ──────────────────────────────────────────────────────────────

const SKILLS = [
  { id: 1, name: 'Vue.js', category: 'Programming' },
  { id: 2, name: 'Laravel', category: 'Programming' },
  { id: 3, name: 'Python', category: 'Programming' },
  { id: 4, name: 'Algorithms & Data Structures', category: 'Programming' },
  { id: 5, name: 'Java / OOP', category: 'Programming' },
  { id: 6, name: 'Calculus', category: 'Mathematics' },
  { id: 7, name: 'Linear Algebra', category: 'Mathematics' },
  { id: 8, name: 'Public Speaking', category: 'Soft skills' },
  { id: 9, name: 'UI / Figma', category: 'Design' },
]

const USERS = [
  { id: 1, email: 'test@example.com', password: 'password123', first_name: 'Alex', last_name: 'Tan', faculty: 'Computing', year: '3', bio: 'Test user', profile_photo: null, is_active: 1 },
  { id: 2, email: 'tutor@example.com', password: 'password123', first_name: 'Faiz', last_name: 'Amer', faculty: 'Computing', year: '3', bio: 'Vue & Laravel specialist.', profile_photo: null, is_active: 1 },
  { id: 3, email: 'rina@example.com', password: 'password123', first_name: 'Nur', last_name: 'Rina', faculty: 'Computing', year: '4', bio: 'Algorithms & Python.', profile_photo: null, is_active: 1 },
  { id: 4, email: 'sara@example.com', password: 'password123', first_name: 'Sara', last_name: 'Lim', faculty: 'Engineering', year: '3', bio: 'Calculus tutor.', profile_photo: null, is_active: 1 },
]

// user_skills: links a user to a skill they can teach
const USER_SKILLS = [
  { id: 1, user_id: 2, skill_id: 1, hourly_rate: 25, experience_level: 'Advanced', description: 'Vue.js from scratch to production.' },
  { id: 2, user_id: 2, skill_id: 2, hourly_rate: 25, experience_level: 'Advanced', description: 'Laravel REST APIs.' },
  { id: 3, user_id: 3, skill_id: 3, hourly_rate: 20, experience_level: 'Advanced', description: 'Python & algorithms.' },
  { id: 4, user_id: 3, skill_id: 4, hourly_rate: 20, experience_level: 'Intermediate', description: 'DSA for interviews.' },
  { id: 5, user_id: 4, skill_id: 6, hourly_rate: 30, experience_level: 'Advanced', description: 'Calculus for engineering.' },
]

let bookings = [
  { id: 1, learner_id: 1, tutor_id: 2, user_skill_id: 1, start_time: '2026-07-10 11:00:00', end_time: '2026-07-10 12:30:00', status: 'pending', amount: 37.50 },
]

let messages = [
  { id: 1, sender_id: 1, recipient_id: 2, content: 'Hi! Can you help me with Vuex?', is_read: true, created_at: '2026-06-18T09:12:00' },
  { id: 2, sender_id: 2, recipient_id: 1, content: 'Sure, bring your repo!', is_read: true, created_at: '2026-06-18T09:30:00' },
]

let userRoles = {
  1: ['Learner'],
  2: ['Tutor'],
  3: ['Tutor'],
  4: ['Tutor'],
}

let nextId = { booking: 2, message: 3, user: 5, user_skill: 6 }

// ─── Auth state (in-memory) ──────────────────────────────────────────────────

const tokens = new Map() // access_token → userId
const refreshTokens = new Map() // refresh_token → userId

function issueTokens(user) {
  const access = `mock-access-${user.id}-${Date.now()}`
  const refresh = `mock-refresh-${user.id}-${Date.now()}`
  tokens.set(access, user.id)
  refreshTokens.set(refresh, user.id)
  return { access_token: access, refresh_token: refresh }
}

function getUserFromReq(req) {
  const auth = req.headers['authorization'] ?? ''
  const token = auth.replace('Bearer ', '')
  const userId = tokens.get(token)
  return userId ? USERS.find(u => u.id === userId) : null
}

// ─── HTTP helpers ────────────────────────────────────────────────────────────

function ok(res, data, status = 200, message = 'OK') {
  const body = JSON.stringify({ success: true, message, data })
  res.writeHead(status, { 'Content-Type': 'application/json', 'Access-Control-Allow-Origin': '*' })
  res.end(body)
}

function err(res, message, status = 400, errors = null) {
  const body = JSON.stringify({ success: false, message, errors })
  res.writeHead(status, { 'Content-Type': 'application/json', 'Access-Control-Allow-Origin': '*' })
  res.end(body)
}

function readBody(req) {
  return new Promise((resolve) => {
    let data = ''
    req.on('data', c => (data += c))
    req.on('end', () => {
      try { resolve(JSON.parse(data || '{}')) } catch { resolve({}) }
    })
  })
}

function stripPassword(u) {
  const { password, ...rest } = u
  return rest
}

// ─── Router ─────────────────────────────────────────────────────────────────

async function handle(req, res) {
  const url = new URL(req.url, `http://localhost:${PORT}`)
  const path = url.pathname.replace(/^\/api/, '')
  const method = req.method

  // CORS preflight
  if (method === 'OPTIONS') {
    res.writeHead(204, {
      'Access-Control-Allow-Origin': '*',
      'Access-Control-Allow-Methods': 'GET, POST, PATCH, DELETE, OPTIONS',
      'Access-Control-Allow-Headers': 'Content-Type, Authorization',
    })
    return res.end()
  }

  const body = ['POST', 'PATCH', 'PUT'].includes(method) ? await readBody(req) : {}

  console.log(`${method} ${path}`)

  // ── Auth ──────────────────────────────────────────────────────────────────

  if (method === 'POST' && path === '/auth/register') {
    if (USERS.find(u => u.email === body.email)) return err(res, 'Email already registered')
    const user = {
      id: nextId.user++,
      email: body.email,
      password: body.password,
      first_name: body.first_name,
      last_name: body.last_name,
      faculty: body.faculty ?? null,
      year: body.year ?? null,
      bio: null,
      profile_photo: null,
      is_active: 1,
    }
    USERS.push(user)
    userRoles[user.id] = ['Learner']
    const t = issueTokens(user)
    return ok(res, { user: stripPassword(user), ...t, roles: userRoles[user.id] }, 201, 'Registration successful')
  }

  if (method === 'POST' && path === '/auth/login') {
    const user = USERS.find(u => u.email === body.email && u.password === body.password)
    if (!user) return err(res, 'Invalid credentials', 401)
    const t = issueTokens(user)
    return ok(res, { user: stripPassword(user), ...t, roles: userRoles[user.id] ?? ['Learner'] }, 200, 'Login successful')
  }

  if (method === 'POST' && path === '/auth/logout') {
    if (body.refresh_token) refreshTokens.delete(body.refresh_token)
    return ok(res, {}, 200, 'Logged out')
  }

  if (method === 'POST' && path === '/auth/refresh') {
    const userId = refreshTokens.get(body.refresh_token)
    if (!userId) return err(res, 'Invalid refresh token', 401)
    const user = USERS.find(u => u.id === userId)
    const access = `mock-access-${userId}-${Date.now()}`
    tokens.set(access, userId)
    return ok(res, { access_token: access }, 200, 'Token refreshed')
  }

  // ── Users ─────────────────────────────────────────────────────────────────

  if (method === 'GET' && path === '/users/me') {
    const user = getUserFromReq(req)
    if (!user) return err(res, 'Unauthorized', 401)
    return ok(res, { user: stripPassword(user), average_rating: 4.7, total_reviews: 12 }, 200, 'User profile retrieved')
  }

  const userMatch = path.match(/^\/users\/(\d+)$/)
  if (method === 'GET' && userMatch) {
    const user = USERS.find(u => u.id === Number(userMatch[1]))
    if (!user) return err(res, 'User not found', 404)
    return ok(res, { user: stripPassword(user), average_rating: 4.8, total_reviews: 20 }, 200, 'User profile retrieved')
  }

  if (method === 'PATCH' && path === '/users/me') {
    const user = getUserFromReq(req)
    if (!user) return err(res, 'Unauthorized', 401)
    Object.assign(user, body)
    return ok(res, { user: stripPassword(user) }, 200, 'Profile updated')
  }

  // ── Skills ────────────────────────────────────────────────────────────────

  if (method === 'GET' && path === '/skills') {
    return ok(res, { skills: SKILLS, total: SKILLS.length }, 200, 'Skills retrieved')
  }

  if (method === 'GET' && path === '/skills/search') {
    const q = (url.searchParams.get('q') ?? '').toLowerCase()
    const found = SKILLS.filter(s => s.name.toLowerCase().includes(q))
    return ok(res, { skills: found }, 200, 'Skills found')
  }

  // ── User Skills ───────────────────────────────────────────────────────────

  if (method === 'POST' && path === '/user-skills') {
    const user = getUserFromReq(req)
    if (!user) return err(res, 'Unauthorized', 401)
    const us = { id: nextId.user_skill++, user_id: user.id, ...body }
    USER_SKILLS.push(us)
    if (!userRoles[user.id]) userRoles[user.id] = ['Learner']
    if (!userRoles[user.id].includes('Tutor')) userRoles[user.id].push('Tutor')
    return ok(res, { user_skill: us }, 201, 'Skill offering created')
  }

  const userSkillsMatch = path.match(/^\/users\/(\d+)\/skills$/)
  if (method === 'GET' && userSkillsMatch) {
    const userId = Number(userSkillsMatch[1])
    const found = USER_SKILLS.filter(us => us.user_id === userId)
    return ok(res, { user_skills: found }, 200, 'User skills retrieved')
  }

  // ── Tutor Discovery ───────────────────────────────────────────────────────

  if (method === 'GET' && path === '/tutors/search') {
    const skillId = Number(url.searchParams.get('skill_id'))
    const faculty = url.searchParams.get('faculty')
    const minRating = parseFloat(url.searchParams.get('min_rating') ?? '0') || 0
    const maxRate = parseFloat(url.searchParams.get('max_rate') ?? '9999') || 9999

    const matchingUserSkills = USER_SKILLS.filter(us => us.skill_id === skillId)
    const skill = SKILLS.find(s => s.id === skillId)

    const tutors = matchingUserSkills
      .map(us => {
        const u = USERS.find(u => u.id === us.user_id)
        if (!u) return null
        return {
          id: u.id,
          first_name: u.first_name,
          last_name: u.last_name,
          profile_photo: u.profile_photo,
          faculty: u.faculty,
          avg_rating: 4.5 + Math.random() * 0.5,
          total_sessions: Math.floor(Math.random() * 40) + 5,
          hourly_rate: us.hourly_rate,
          skill_name: skill?.name ?? '',
        }
      })
      .filter(Boolean)
      .filter(t => (!faculty || t.faculty === faculty) && t.avg_rating >= minRating && t.hourly_rate <= maxRate)

    return ok(res, { tutors, total: tutors.length, page: 1, per_page: 50, pages: 1, sort: 'rating' }, 200, 'Tutors found')
  }

  // ── Bookings ──────────────────────────────────────────────────────────────

  if (method === 'POST' && path === '/bookings') {
    const user = getUserFromReq(req)
    if (!user) return err(res, 'Unauthorized', 401)
    const us = USER_SKILLS.find(us => us.id === body.user_skill_id)
    if (!us) return err(res, 'Skill offering not found', 404)
    const start = new Date(body.start_time)
    const end = new Date(body.end_time)
    const hours = (end - start) / (1000 * 60 * 60)
    const booking = {
      id: nextId.booking++,
      learner_id: user.id,
      tutor_id: us.user_id,
      user_skill_id: us.id,
      start_time: body.start_time,
      end_time: body.end_time,
      status: 'pending',
      amount: +(us.hourly_rate * hours).toFixed(2),
    }
    bookings.push(booking)
    return ok(res, { booking }, 201, 'Booking requested')
  }

  if (method === 'GET' && path === '/bookings/learner') {
    const user = getUserFromReq(req)
    if (!user) return err(res, 'Unauthorized', 401)
    const found = bookings.filter(b => b.learner_id === user.id)
    return ok(res, { bookings: found, page: 1, per_page: 50 }, 200, 'Bookings retrieved')
  }

  if (method === 'GET' && path === '/bookings/tutor') {
    const user = getUserFromReq(req)
    if (!user) return err(res, 'Unauthorized', 401)
    const found = bookings.filter(b => b.tutor_id === user.id)
    return ok(res, { bookings: found, page: 1, per_page: 50 }, 200, 'Bookings retrieved')
  }

  const bookingActionMatch = path.match(/^\/bookings\/(\d+)\/(accept|decline|confirm|complete|cancel)$/)
  if (method === 'PATCH' && bookingActionMatch) {
    const booking = bookings.find(b => b.id === Number(bookingActionMatch[1]))
    if (!booking) return err(res, 'Booking not found', 404)
    const actionToStatus = { accept: 'accepted', decline: 'declined', confirm: 'confirmed', complete: 'completed', cancel: 'cancelled' }
    booking.status = actionToStatus[bookingActionMatch[2]]
    return ok(res, { booking }, 200, `Booking ${bookingActionMatch[2]}ed`)
  }

  // ── Messages ──────────────────────────────────────────────────────────────

  if (method === 'POST' && path === '/messages') {
    const user = getUserFromReq(req)
    if (!user) return err(res, 'Unauthorized', 401)
    const msg = {
      id: nextId.message++,
      sender_id: user.id,
      recipient_id: Number(body.recipient_id),
      content: body.content,
      is_read: false,
      created_at: new Date().toISOString(),
    }
    messages.push(msg)
    return ok(res, { message: msg }, 201, 'Message sent')
  }

  const convMatch = path.match(/^\/conversations\/(\d+)$/)
  if (method === 'GET' && convMatch) {
    const user = getUserFromReq(req)
    if (!user) return err(res, 'Unauthorized', 401)
    const otherId = Number(convMatch[1])
    const thread = messages.filter(
      m => (m.sender_id === user.id && m.recipient_id === otherId) ||
           (m.sender_id === otherId && m.recipient_id === user.id)
    )
    return ok(res, { messages: thread, other_user_id: otherId, page: 1, per_page: 50 }, 200, 'Conversation retrieved')
  }

  const convReadMatch = path.match(/^\/conversations\/(\d+)\/read$/)
  if (method === 'PATCH' && convReadMatch) {
    const user = getUserFromReq(req)
    const senderId = Number(convReadMatch[1])
    messages.filter(m => m.sender_id === senderId && m.recipient_id === user?.id).forEach(m => (m.is_read = true))
    return ok(res, {}, 200, 'Conversation marked as read')
  }

  if (method === 'GET' && path === '/messages/unread-count') {
    const user = getUserFromReq(req)
    if (!user) return err(res, 'Unauthorized', 401)
    const count = messages.filter(m => m.recipient_id === user.id && !m.is_read).length
    return ok(res, { unread_count: count }, 200, 'Unread count retrieved')
  }

  // ── Wallet ────────────────────────────────────────────────────────────────

  if (method === 'GET' && path === '/wallet') {
    const user = getUserFromReq(req)
    if (!user) return err(res, 'Unauthorized', 401)
    return ok(res, { wallet: { id: 1, user_id: user.id, balance: 125.00, currency: 'MYR' }, balance: 125.00 }, 200, 'Wallet retrieved')
  }

  // ── 404 ───────────────────────────────────────────────────────────────────

  return err(res, `No mock route for ${method} ${path}`, 404)
}

createServer(handle).listen(PORT, () => {
  console.log(`\n🟢  Mock API running at http://localhost:${PORT}/api`)
  console.log(`\nTest accounts:`)
  console.log(`  Learner  →  test@example.com   / password123`)
  console.log(`  Tutor    →  tutor@example.com  / password123`)
  console.log(`\nAdd this to .env.local:`)
  console.log(`  VITE_API_BASE_URL=http://localhost:${PORT}/api\n`)
})
