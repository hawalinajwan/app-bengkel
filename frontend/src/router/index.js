import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/track',
      component: () => import('@/views/public/TrackLookup.vue'),
      meta: { public: true }
    },
    {
      path: '/track/:code',
      component: () => import('@/views/public/TrackDetail.vue'),
      meta: { public: true }
    },
    {
      path: '/booking',
      component: () => import('@/views/public/BookingCreate.vue'),
      meta: { public: true }
    },
    {
      path: '/booking/:code',
      component: () => import('@/views/public/BookingStatus.vue'),
      meta: { public: true }
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/Login.vue'),
      meta: { public: true }
    },
    {
      path: '/',
      name: 'landing',
      component: () => import('@/views/public/Landing.vue'),
      meta: { public: true, wide: true }
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('@/views/Dashboard.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/work-orders',
      component: () => import('@/views/WorkOrderBoard.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/work-orders/new',
      component: () => import('@/views/WorkOrderCreate.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/work-orders/:id/ticket',
      component: () => import('@/views/Ticket.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/work-orders/:id/receipt',
      component: () => import('@/views/Receipt.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/work-orders/:id',
      component: () => import('@/views/WorkOrderDetail.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/vehicles/:id/history',
      component: () => import('@/views/VehicleHistory.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/bookings',
      component: () => import('@/views/Bookings.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/notifications',
      component: () => import('@/views/Notifications.vue'),
      meta: { requiresAuth: true, roles: ['admin'] }
    },
    ...['customers', 'vehicles', 'mechanics', 'services', 'spareparts'].map((name) => ({
      path: `/${name}`,
      component: () => import(`@/views/${name[0].toUpperCase() + name.slice(1)}.vue`),
      meta: {
        requiresAuth: true,
        ...(['mechanics', 'services', 'spareparts'].includes(name) ? { roles: ['admin'] } : {})
      }
    })),
    {
      path: '/reports/daily',
      component: () => import('@/views/ReportDaily.vue'),
      meta: { requiresAuth: true, roles: ['admin'] }
    },
    {
      path: '/:pathMatch(.*)*',
      component: () => import('@/views/NotFound.vue'),
      meta: { public: true }
    }
  ]
})
router.beforeEach(async (to) => {
  const auth = useAuthStore()
  if (to.meta.requiresAuth) {
    if (!auth.token) return { name: 'login', query: { redirect: to.fullPath } }
    if (!auth.user) await auth.fetchMe().catch(() => null)
    if (!auth.user) return { name: 'login' }
    if (to.meta.roles && !to.meta.roles.includes(auth.user.role)) return { name: 'dashboard' }
  }
  if (to.name === 'login' && auth.token && auth.user) return { name: 'dashboard' }
})
export default router
