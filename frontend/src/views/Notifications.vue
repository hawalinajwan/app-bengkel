<script setup>
import { onMounted, ref } from 'vue'
import api from '@/api/client'

const items = ref([])
const loading = ref(true)
const error = ref('')

const labels = {
  order_selesai: 'Order selesai',
  booking_dibuat: 'Booking dibuat',
  booking_dikonfirmasi: 'Booking dikonfirmasi',
  booking_ditolak: 'Booking ditolak',
  booking_dibatalkan: 'Booking dibatalkan'
}

function label(type) {
  return labels[type] || type
}

function waktu(value) {
  if (!value) return '—'
  return new Date(value).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })
}

onMounted(async () => {
  try {
    items.value = (await api.get('/notification-logs')).data.data
  } catch (e) {
    error.value = e.response?.data?.message || 'Notifikasi belum dapat dimuat.'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <section class="grid gap-6">
    <header>
      <p class="text-sm text-muted">Operasional</p>
      <h1 class="text-2xl font-bold text-forest">Log notifikasi</h1>
      <p class="mt-1 text-sm text-muted">Catatan email yang dikirim sistem ke pelanggan.</p>
    </header>

    <div class="rounded-service border border-line bg-white shadow-sm">
      <p v-if="loading" class="p-5 text-sm text-muted">Memuat…</p>
      <p
        v-else-if="!items.length"
        class="p-5 text-sm"
        :class="error ? 'text-red-800' : 'text-muted'"
      >
        {{ error || 'Belum ada notifikasi terkirim.' }}
      </p>
      <ul v-else class="divide-y divide-line">
        <li
          v-for="item in items"
          :key="item.id"
          class="grid gap-1 px-5 py-4 text-sm sm:grid-cols-[1fr_auto] sm:items-center"
        >
          <div class="min-w-0">
            <p class="font-semibold text-ink">{{ label(item.type) }}</p>
            <p class="truncate font-mono text-xs text-muted">
              {{ item.recipient || 'tanpa email' }} · {{ item.channel }}
            </p>
            <p v-if="item.error" class="mt-1 text-xs text-red-800">{{ item.error }}</p>
          </div>
          <div class="flex items-center gap-3 sm:justify-end">
            <span class="whitespace-nowrap font-mono text-xs text-muted">{{
              waktu(item.sent_at || item.created_at)
            }}</span>
            <span
              class="whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-semibold"
              :class="
                item.status === 'terkirim'
                  ? 'bg-green-100 text-green-800'
                  : item.status === 'gagal'
                    ? 'bg-red-100 text-red-800'
                    : 'bg-forest-soft text-forest'
              "
              >{{ item.status }}</span
            >
          </div>
        </li>
      </ul>
    </div>
  </section>
</template>
