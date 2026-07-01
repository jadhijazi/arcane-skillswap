import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/useAuthStore'

import LoginView from '../views/LoginView.vue'
import HomeView from '../views/HomeView.vue'
import TutorsView from '../views/TutorsView.vue'
import BookingView from '../views/BookingView.vue'
import DashboardView from '../views/DashboardView.vue'
import ProfileView from '../views/ProfileView.vue'
import MessagesView from '../views/MessagesView.vue'

const routes = [
  { path: '/login', name: 'login', component: LoginView, meta: { public: true } },
  { path: '/', name: 'home', component: HomeView },
  { path: '/tutors', name: 'tutors', component: TutorsView },
  { path: '/booking/:tutorId?', name: 'booking', component: BookingView, props: true },
  { path: '/dashboard', name: 'dashboard', component: DashboardView, meta: { requiresRole: 'Tutor' } },
  { path: '/messages/:tutorId?', name: 'messages', component: MessagesView, props: true },
  { path: '/profile', name: 'profile', component: ProfileView },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 }
  },
})

router.beforeEach((to) => {
  const auth = useAuthStore()

  if (!to.meta.public && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.requiresRole && auth.activeRole !== to.meta.requiresRole) {
    // e.g. a Learner trying to open the Tutor dashboard
    return { name: 'home' }
  }

  return true
})

export default router
