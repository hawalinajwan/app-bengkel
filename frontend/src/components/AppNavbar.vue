<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const items = [
  { to: '/', label: 'Dashboard' },
  { to: '/customers', label: 'Pelanggan' },
  { to: '/vehicles', label: 'Kendaraan' },
  { to: '/mechanics', label: 'Mekanik', roles: ['admin'] },
  { to: '/services', label: 'Jasa', roles: ['admin'] },
  { to: '/spareparts', label: 'Sparepart', roles: ['admin'] },
  { to: '/bookings', label: 'Booking' },
]

const visibleItems = computed(() =>
  items.filter((item) => !item.roles || item.roles.includes(auth.user?.role)),
)

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <nav class="navbar navbar-expand-lg bg-dark navbar-dark">
    <div class="container-fluid">
      <RouterLink class="navbar-brand" to="/">Antrian Bengkel</RouterLink>

      <ul class="navbar-nav me-auto">
        <li v-for="item in visibleItems" :key="item.to" class="nav-item">
          <RouterLink class="nav-link" :to="item.to">{{ item.label }}</RouterLink>
        </li>
      </ul>

      <span class="navbar-text me-3">
        {{ auth.user?.name }}
        <span class="badge text-bg-secondary ms-1">{{ auth.user?.role }}</span>
      </span>
      <button class="btn btn-outline-light btn-sm" type="button" @click="logout">Keluar</button>
    </div>
  </nav>
</template>
