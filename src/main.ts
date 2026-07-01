import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import { useAuthStore } from '@/stores/useAuthStore'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

// Restore session from stored token before the first route renders.
// The router guard (router/index.ts) waits for this before redirecting.
const auth = useAuthStore()
auth.restoreSession().finally(() => app.mount('#app'))
