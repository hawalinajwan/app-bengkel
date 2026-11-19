import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/Login.vue'),
      meta: { public: true },
    },
    {
      path: '/',
      name: 'dashboard',
      component: () => import('@/views/Dashboard.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/customers',
      name: 'customers',
      component: () => import('@/views/Customers.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/vehicles',
      name: 'vehicles',
      component: () => import('@/views/Vehicles.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/mechanics',
      name: 'mechanics',
      component: () => import('@/views/Mechanics.vue'),
      meta: { requiresAuth: true, role: 'admin' },
    },
    {
      path: '/services',
      name: 'services',
      component: () => import('@/views/Services.vue'),
      meta: { requiresAuth: true, role: 'admin' },
    },
    {
      path: '/spareparts',
      name: 'spareparts',
      component: () => import('@/views/Spareparts.vue'),
      meta: { requiresAuth: true, role: 'admin' },
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/views/NotFound.vue'),
      meta: { public: true },
    },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (to.meta.requiresAuth) {
    if (!auth.token) return { name: 'login', query: { redirect: to.fullPath } }
    if (!auth.user) {
      await auth.fetchMe().catch(() => null)
      if (!auth.user) return { name: 'login', query: { redirect: to.fullPath } }
    }
    if (to.meta.role && auth.user.role !== to.meta.role) return { name: 'dashboard' }
  }

  if (to.name === 'login' && auth.token && auth.user) return { name: 'dashboard' }
})

export default router
