import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/useAuthStore'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  scrollBehavior() {
    return { top: 0 }
  },
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/LoginView.vue'),
      meta: { public: true },
    },
    {
      path: '/',
      name: 'home',
      component: () => import('../views/HomeView.vue'),
    },
    {
      path: '/tutors',
      name: 'tutors',
      component: () => import('../views/TutorsView.vue'),
    },
    {
      path: '/booking/:tutorId?',
      name: 'booking',
      component: () => import('../views/BookingView.vue'),
      props: true,
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('../views/DashboardView.vue'),
      // Accessible to anyone with the Tutor role.
      // A Learner who registers a skill offering gains 'Tutor' via grantTutorRole()
      // and can then switch roles to reach this view.
      meta: { requiresRole: 'Tutor' },
    },
    {
      path: '/messages/:tutorId?',
      name: 'messages',
      component: () => import('../views/MessagesView.vue'),
      props: true,
    },
    {
      path: '/profile',
      name: 'profile',
      component: () => import('../views/ProfileView.vue'),
    },
  ],
})

router.beforeEach((to) => {
  const auth = useAuthStore()

  // The store starts as 'idle' while restoreSession() is still running.
  // Once it resolves (in main.ts), status is 'ready' or back to 'idle'.
  // We treat 'idle' + no user as unauthenticated.
  if (!to.meta.public && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.requiresRole && !auth.user?.roles.includes(to.meta.requiresRole as string)) {
    return { name: 'home' }
  }

  return true
})

export default router
