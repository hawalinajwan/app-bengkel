<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const open = ref(false)
const items = [
  { to: '/', label: 'Dashboard' },
  { to: '/reports/daily', label: 'Laporan', roles: ['admin'] },
  { to: '/customers', label: 'Pelanggan' },
  { to: '/vehicles', label: 'Kendaraan' },
  { to: '/mechanics', label: 'Mekanik', roles: ['admin'] },
  { to: '/services', label: 'Jasa', roles: ['admin'] },
  { to: '/spareparts', label: 'Sparepart', roles: ['admin'] },
  { to: '/work-orders', label: 'Antrian' },
  { to: '/work-orders/new', label: 'Daftar Order' },
  { to: '/bookings', label: 'Booking' },
]
const visibleItems = computed(() => items.filter((item) => !item.roles || item.roles.includes(auth.user?.role)))
async function logout() { await auth.logout(); router.push({ name: 'login' }) }
function closeMenu() { open.value = false }
</script>

<template>
  <header class="navbar-service">
    <nav class="navbar navbar-expand-lg" aria-label="Navigasi utama">
      <div class="container-fluid px-3 px-lg-4">
        <RouterLink class="navbar-brand fw-semibold" to="/" @click="closeMenu">Antrian Bengkel</RouterLink>
        <button class="navbar-toggler border-light" type="button" aria-label="Buka menu navigasi" :aria-expanded="open" @click="open = !open">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" :class="{ show: open }">
          <ul class="navbar-nav me-auto">
            <li v-for="item in visibleItems" :key="item.to" class="nav-item"><RouterLink class="nav-link px-3" :to="item.to" @click="closeMenu">{{ item.label }}</RouterLink></li>
          </ul>
          <div class="d-flex align-items-center gap-3 py-2 py-lg-0">
            <span class="small">{{ auth.user?.name }} <span class="opacity-75">({{ auth.user?.role }})</span></span>
            <button class="btn btn-outline-light" type="button" @click="logout">Keluar</button>
          </div>
        </div>
      </div>
    </nav>
  </header>
</template>
