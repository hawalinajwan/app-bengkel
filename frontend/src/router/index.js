import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
  { path: '/track', name: 'track', component: () => import('@/views/public/TrackLookup.vue'), meta: { public: true } },
  { path: '/track/:code', name: 'track-detail', component: () => import('@/views/public/TrackDetail.vue'), meta: { public: true } },
  { path: '/booking', name: 'booking', component: () => import('@/views/public/BookingCreate.vue'), meta: { public: true } },
  { path: '/booking/:code', name: 'booking-status', component: () => import('@/views/public/BookingStatus.vue'), meta: { public: true } },
  { path: '/bookings', name: 'bookings', component: () => import('@/views/Bookings.vue'), meta: { requiresAuth: true } },
  { path: '/work-orders/:id/ticket', name: 'ticket', component: () => import('@/views/Ticket.vue'), meta: { requiresAuth: true } },
  { path: '/notifications', name: 'notifications', component: () => import('@/views/Notifications.vue'), meta: { requiresAuth: true } },
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
