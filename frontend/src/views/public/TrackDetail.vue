<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/api/client'
import StatusBadge from '@/components/StatusBadge.vue'
import StatusTimeline from '@/components/StatusTimeline.vue'
import { rupiah } from '@/utils/rupiah'

const route = useRoute()
const data = ref(null)
const error = ref('')
const notifyAllowed = ref(
  typeof Notification !== 'undefined' && Notification.permission === 'granted'
)
const banner = ref(false)
let timer

function stopPolling(status) {
  if (['dibayar', 'batal'].includes(status)) clearInterval(timer)
}

async function load() {
  try {
    const next = (await api.get(`/public/track/${route.params.code}`)).data.data
    if (data.value && data.value.status !== 'selesai' && next.status === 'selesai') {
      banner.value = true
      if (notifyAllowed.value) {
        new Notification('Servis selesai', {
          body: `${next.vehicle.brand} ${next.vehicle.model} (${next.vehicle.plate_masked}) siap diambil.`
        })
      }
    }
    data.value = next
    stopPolling(next.status)
  } catch (e) {
    error.value = e.response?.data?.message || 'Data tidak ditemukan.'
    clearInterval(timer)
  }
}

async function enableNotifications() {
  if (typeof Notification === 'undefined') return
  const permission = await Notification.requestPermission()
  notifyAllowed.value = permission === 'granted'
}

function formatDateTime(value) {
  return new Date(value).toLocaleString('id-ID', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(() => {
  load()
  timer = setInterval(load, 15000)
})
onUnmounted(() => clearInterval(timer))
</script>

<template>
  <section class="mx-auto grid max-w-xl gap-6">
    <header>
      <p class="text-sm text-muted">Pelacakan servis</p>
      <h1 class="text-2xl font-bold text-forest">Status servis</h1>
      <p class="mt-1 font-mono text-sm text-muted">{{ String(route.params.code).toUpperCase() }}</p>
    </header>

    <div
      v-if="banner"
      class="flex flex-wrap items-center justify-between gap-3 rounded-service border border-amber bg-[#fbf1dd] p-4 text-sm text-[#7c5713]"
    >
      <p><strong>Servis selesai.</strong> Kendaraan Anda siap diambil.</p>
      <button class="font-semibold underline" type="button" @click="banner = false">Tutup</button>
    </div>

    <div
      v-if="error"
      class="grid gap-3 rounded-service border border-red-200 bg-red-50 p-4 text-sm text-red-800"
    >
      <p>{{ error }}</p>
      <RouterLink class="font-semibold text-forest no-underline" to="/track"
        >Coba kode lain</RouterLink
      >
    </div>
    <p v-else-if="!data" class="text-muted">Memuat data…</p>

    <article v-else class="overflow-hidden rounded-service border border-line bg-white shadow-sm">
      <div class="flex items-center justify-between gap-4 bg-forest px-5 py-4 text-white">
        <div>
          <p class="text-xs uppercase tracking-wide text-white/70">Nomor antrian</p>
          <p class="font-mono text-4xl font-bold leading-none">
            {{ String(data.queue_number).padStart(3, '0') }}
          </p>
        </div>
        <StatusBadge :status="data.status" />
      </div>
      <div class="border-b border-dashed border-line px-5 py-4 text-sm">
        <p class="font-semibold text-ink">{{ data.vehicle.brand }} {{ data.vehicle.model }}</p>
        <p class="font-mono text-muted">{{ data.vehicle.plate_masked }}</p>
        <p v-if="data.public_note" class="mt-3 rounded-lg bg-forest-soft p-3 text-forest">
          {{ data.public_note }}
        </p>
      </div>

      <div class="grid gap-6 px-5 py-5">
        <section>
          <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-muted">Progres</h2>
          <StatusTimeline :items="data.timeline" />
        </section>
        <section>
          <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-muted">Jasa</h2>
          <ul class="grid gap-1 text-sm">
            <li v-for="name in data.services" :key="name">{{ name }}</li>
            <li v-if="!data.services?.length" class="text-muted">Belum ada jasa tercatat.</li>
          </ul>
        </section>
        <section v-if="data.parts?.length">
          <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-muted">Sparepart</h2>
          <ul class="grid gap-1 text-sm">
            <li v-for="part in data.parts" :key="part.name" class="flex justify-between gap-3">
              <span>{{ part.name }}</span>
              <span class="font-mono text-muted">× {{ part.qty }}</span>
            </li>
          </ul>
        </section>
        <p
          v-if="data.estimated_finish_at"
          class="flex flex-wrap justify-between gap-2 rounded-lg bg-[#fbf1dd] px-3 py-2 text-sm text-[#7c5713]"
        >
          <span>Estimasi selesai</span>
          <span class="font-mono font-semibold">{{
            formatDateTime(data.estimated_finish_at)
          }}</span>
        </p>
        <p
          v-if="data.total !== null"
          class="flex justify-between border-t border-dashed border-line pt-4 font-bold text-forest"
        >
          <span>Total</span>
          <span class="font-mono">{{ rupiah(data.total) }}</span>
        </p>
        <div
          v-if="!['dibayar', 'batal'].includes(data.status)"
          class="border-t border-dashed border-line pt-4 text-sm"
        >
          <button
            v-if="!notifyAllowed"
            class="service-muted w-fit"
            type="button"
            @click="enableNotifications"
          >
            Aktifkan notifikasi
          </button>
          <p class="text-muted" :class="{ 'mt-2': !notifyAllowed }">
            Notifikasi browser hanya berfungsi selama tab ini terbuka.
          </p>
        </div>
      </div>
    </article>
  </section>
</template>
