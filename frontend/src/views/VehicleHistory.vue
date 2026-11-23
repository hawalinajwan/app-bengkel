<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import client from '@/api/client'
import AlertMessage from '@/components/AlertMessage.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { rupiah } from '@/utils/rupiah'

const route = useRoute()
const vehicle = ref(null)
const orders = ref([])
const loading = ref(true)
const error = ref('')

onMounted(async () => {
  try {
    const { data } = await client.get(`/vehicles/${route.params.id}/history`)
    vehicle.value = data.data.vehicle
    orders.value = data.data.orders
  } catch (e) {
    error.value = e.response?.data?.message || 'Gagal memuat riwayat.'
  } finally {
    loading.value = false
  }
})

function formatDate(value) {
  return value
    ? new Date(value).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
      })
    : '-'
}
</script>

<template>
  <div class="grid gap-6">
    <header>
      <p class="text-sm text-muted">Kendaraan</p>
      <h1 class="text-2xl font-bold text-forest">Riwayat servis</h1>
      <p v-if="vehicle" class="mt-1 text-sm text-muted">
        <span class="font-mono text-ink">{{ vehicle.plate_number }}</span> · {{ vehicle.brand }}
        {{ vehicle.model }} · {{ vehicle.customer?.name }}
      </p>
    </header>
    <AlertMessage :message="error" />
    <p v-if="loading" class="text-muted">Memuat…</p>
    <p
      v-else-if="!orders.length"
      class="rounded-service border border-dashed border-line bg-white p-6 text-sm text-muted"
    >
      Belum ada riwayat servis untuk kendaraan ini.
    </p>

    <ol v-else class="grid gap-4 border-l-2 border-line pl-6">
      <li
        v-for="order in orders"
        :key="order.id"
        class="relative rounded-service border border-line bg-white p-5 shadow-sm"
      >
        <span
          class="absolute -left-[33px] top-6 h-3 w-3 rounded-full border-2 border-forest bg-white"
          aria-hidden="true"
        />
        <div class="flex flex-wrap items-center justify-between gap-2">
          <p class="font-mono text-lg font-bold text-forest">Antrian {{ order.queue_number }}</p>
          <div class="flex items-center gap-2">
            <StatusBadge :status="order.status" />
            <span class="text-sm text-muted">{{ formatDate(order.queue_date) }}</span>
          </div>
        </div>
        <dl
          class="mt-3 grid gap-2 border-t border-dashed border-line pt-3 text-sm sm:grid-cols-[7rem_1fr]"
        >
          <dt class="text-muted">Keluhan</dt>
          <dd>{{ order.complaint || '-' }}</dd>
          <dt class="text-muted">Jasa</dt>
          <dd>{{ order.services.join(', ') || '-' }}</dd>
          <dt class="text-muted">Sparepart</dt>
          <dd>{{ order.parts.map((p) => `${p.name} (${p.qty})`).join(', ') || '-' }}</dd>
          <template v-if="order.finished_at">
            <dt class="text-muted">Selesai</dt>
            <dd>{{ formatDate(order.finished_at) }}</dd>
          </template>
          <template v-if="order.total !== null">
            <dt class="text-muted">Total</dt>
            <dd class="font-mono font-semibold text-forest">{{ rupiah(order.total) }}</dd>
          </template>
        </dl>
      </li>
    </ol>
  </div>
</template>
