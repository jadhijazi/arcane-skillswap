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
