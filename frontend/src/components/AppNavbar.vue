<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const open = ref(false)
const groups = [
  {
    label: 'Operasional',
    items: [
      { to: '/dashboard', label: 'Dashboard' },
      { to: '/work-orders', label: 'Antrian' },
      { to: '/work-orders/new', label: 'Daftar order' },
      { to: '/bookings', label: 'Booking' }
    ]
  },
  {
    label: 'Data',
    items: [
      { to: '/customers', label: 'Pelanggan' },
      { to: '/vehicles', label: 'Kendaraan' },
      { to: '/mechanics', label: 'Mekanik', roles: ['admin'] },
      { to: '/services', label: 'Jasa', roles: ['admin'] },
      { to: '/spareparts', label: 'Sparepart', roles: ['admin'] }
    ]
  },
  {
    label: 'Laporan',
    items: [
      { to: '/reports/daily', label: 'Laporan harian', roles: ['admin'] },
      { to: '/notifications', label: 'Notifikasi', roles: ['admin'] }
    ]
  }
]
const visibleGroups = computed(() =>
  groups
    .map((group) => ({
      ...group,
      items: group.items.filter((item) => !item.roles || item.roles.includes(auth.user?.role))
    }))
    .filter((group) => group.items.length)
)

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}
function closeMenu() {
  open.value = false
}
</script>

<template>
  <header
    class="app-print-hide sticky top-0 z-30 flex items-center justify-between bg-forest px-4 py-3 text-white lg:hidden"
  >
    <RouterLink class="font-semibold text-white no-underline" to="/dashboard" @click="closeMenu"
      >Antrian Bengkel</RouterLink
    >
    <button
      class="rounded-lg border border-white/40 px-3 py-2 text-white"
      type="button"
      aria-label="Buka menu navigasi"
      :aria-expanded="open"
      aria-controls="sidebar-nav"
      @click="open = !open"
    >
      Menu
    </button>
  </header>
  <div
    v-if="open"
    class="fixed inset-0 z-30 bg-ink/40 lg:hidden"
    aria-hidden="true"
    @click="closeMenu"
  />
  <aside
    id="sidebar-nav"
    class="app-print-hide fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-forest text-white transition-transform lg:translate-x-0"
    :class="open ? 'translate-x-0' : '-translate-x-full'"
    aria-label="Navigasi utama"
  >
    <div class="hidden border-b border-white/10 px-6 py-5 lg:block">
      <RouterLink
        class="text-lg font-bold text-white no-underline"
        to="/dashboard"
        @click="closeMenu"
        >Antrian Bengkel</RouterLink
      >
    </div>
    <nav class="flex-1 overflow-y-auto px-3 py-4">
      <div v-for="group in visibleGroups" :key="group.label" class="mb-5">
        <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wide text-white/60">
          {{ group.label }}
        </p>
        <ul class="flex flex-col gap-1">
          <li v-for="item in group.items" :key="item.to">
            <RouterLink
              class="block rounded-lg px-3 py-2 text-white no-underline hover:bg-white/10"
              :to="item.to"
              @click="closeMenu"
              >{{ item.label }}</RouterLink
            >
          </li>
        </ul>
      </div>
    </nav>
    <div class="flex items-center justify-between gap-3 border-t border-white/10 px-6 py-4 text-sm">
      <span
        >{{ auth.user?.name }} <span class="opacity-75">({{ auth.user?.role }})</span></span
      >
      <button
        class="rounded-lg border border-white px-3 py-2 text-white hover:bg-white/10"
        type="button"
        @click="logout"
      >
        Keluar
      </button>
    </div>
  </aside>
</template>
