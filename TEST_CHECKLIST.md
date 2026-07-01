# Local test checklist

Run the mock server and Vite together with one command:
```
pnpm dev:mock
```

Then open http://localhost:5173 and walk through this checklist.

---

## Auth
- [ ] Visit any protected page (e.g. /tutors) → redirected to /login
- [ ] Register a new account → redirected to home, nav shows name
- [ ] Log out → redirected to /login
- [ ] Log in with `test@example.com` / `password123` → lands on home
- [ ] Refresh the page while logged in → session restored, no re-login

## Tutor discovery
- [ ] Go to /tutors → "Select a skill to search" shown
- [ ] Pick a subject (e.g. Programming) → skill dropdown populates
- [ ] Pick a skill (e.g. Vue.js) → tutor cards appear
- [ ] Filter by max price → cards update
- [ ] Type in search box → cards filter by name
- [ ] Click "Message" → goes to /messages/{userId}
- [ ] Click "Book session" → goes to /booking/{tutorCardId}

## Booking
- [ ] Pick a date, start time, and duration
- [ ] Pricing summary shows correct subtotal + 10% fee
- [ ] Click "Confirm booking" → success message, redirect to home after ~1s

## Messages
- [ ] /messages without a tutorId → "Pick a conversation" shown
- [ ] Navigating from a tutor card → conversation thread loads
- [ ] Type a message and press Enter or click Send → message appears in thread
- [ ] Sidebar shows the conversation

## Dashboard (Tutor role)
- [ ] Log in as `tutor@example.com` / `password123`
- [ ] Go to /dashboard → pending booking from learner visible
- [ ] Click Accept → status changes to "Accepted"
- [ ] Click Decline → status changes to "Declined"
- [ ] Wallet stats reflect the bookings

## Profile
- [ ] Edit bio and click Save → "Saved!" confirmation
- [ ] Add a skill offering (as a Learner account) → "You're now a tutor" message

---

## Switching to the real backend
1. Edit `.env.local`:
   ```
   VITE_API_BASE_URL=https://YOUR-APP.up.railway.app/api
   ```
2. Stop the mock server (Ctrl+C), run just `pnpm dev`
3. Register a real account through the UI
4. Repeat the checklist above
