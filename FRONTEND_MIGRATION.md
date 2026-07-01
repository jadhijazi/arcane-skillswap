# Frontend → Real API migration

## What changed

### New files
| File | Purpose |
|---|---|
| `src/lib/http.ts` | Fetch wrapper — base URL, Bearer token, auto refresh-on-401, envelope unwrap |
| `src/lib/api.ts` | One typed function per backend endpoint, field names match API docs verbatim |
| `src/lib/constants.ts` | `COMMISSION_RATE` display constant (backend computes the real value) |
| `src/stores/useTutorStore.ts` | Skills list + `/tutors/search` + hydrating `user_skill_id` for booking |
| `src/stores/useBookingStore.ts` | Learner & tutor bookings, wallet summary, all booking action endpoints |
| `src/stores/useMessageStore.ts` | Per-conversation threads, send, unread count |
| `.env.example` | Documents `VITE_API_BASE_URL` |

### Replaced files
| File | Change |
|---|---|
| `src/stores/useAuthStore.ts` | Real `register()`, `login()`, `logout()`, `restoreSession()` with JWT + localStorage |
| `src/stores/useAppStore.ts` | Emptied — stub only, safe to delete once all view imports are gone |
| `src/types/index.ts` | Types updated to match real backend shapes (snake_case → camelCase adapters in stores) |
| `src/views/LoginView.vue` | Real email/password form with register mode |
| `src/views/TutorsView.vue` | Category → skill drill-down, calls `/tutors/search?skill_id=…` |
| `src/views/BookingView.vue` | Posts to `/bookings` with `user_skill_id` + ISO datetimes |
| `src/views/DashboardView.vue` | Loads from `/bookings/tutor`, accept/decline call real endpoints |
| `src/views/MessagesView.vue` | Per-user conversations via `/conversations/{other_user_id}` |
| `src/views/ProfileView.vue` | Saves bio via `PATCH /users/me`, creates skill offerings via `POST /user-skills` |
| `src/main.ts` | Calls `restoreSession()` before mounting so page refreshes don't force re-login |
| `src/router/index.ts` | Role guard checks `user.roles[]` instead of `activeRole` |

## Before you deploy

1. **Set the env var** — copy `.env.example` to `.env.local` and fill in your Railway URL:
   ```
   VITE_API_BASE_URL=https://your-app.up.railway.app/api
   ```
   For Capacitor, also set `server.url` in `capacitor.config.ts` (or use the `VITE_API_BASE_URL` var at build time).

2. **CORS** — the backend's `CorsMiddleware.php` must allow your frontend origin. For local dev that's `http://localhost:5173`; for production it's your deployed Vite URL. Update `CorsMiddleware.php` if needed.

3. **Tutor search requires a `skill_id`** — the backend's `/tutors/search` endpoint has `skill_id` as a required param. `TutorsView` now guides the user through category → skill to satisfy this. If you later add a "browse all" endpoint, update `tutorApi.search` and `TutorsView` accordingly.

4. **Booking uses `user_skill_id`**, not `tutor_id` — a tutor's listing is keyed by `user_skills.id`. `useTutorStore.hydrateUserSkillIds()` fetches this after search. The composite card ID `"{userId}:{skillId}"` is what gets passed as `tutorId` in the `/booking/:tutorId` route.

5. **Dashboard role guard** — `/dashboard` is only reachable when `auth.user.roles` includes `'Tutor'`. A user gains this role either at registration (if the backend assigns it) or when they create their first skill offering via `POST /user-skills` — `auth.grantTutorRole()` is called client-side immediately after so the role switcher and nav update without a page refresh.

6. **Remove `useAppStore`** — it's now an empty stub. Delete `src/stores/useAppStore.ts` once you've confirmed no other files import it.

## Token storage

Access token → `localStorage['skillswap.access_token']`
Refresh token → `localStorage['skillswap.refresh_token']`

The `http.ts` wrapper automatically calls `POST /auth/refresh` on the first 401 and retries the original request once. If the refresh also fails, both tokens are cleared and the user is treated as logged out. The router guard then redirects them to `/login`.
