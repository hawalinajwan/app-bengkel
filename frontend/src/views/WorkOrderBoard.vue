<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import StatusBadge from '@/components/StatusBadge.vue'
import client from '@/api/client'

const statuses = [
  { key: 'menunggu', label: 'Menunggu' },
  { key: 'dikerjakan', label: 'Dikerjakan' },
  { key: 'selesai', label: 'Selesai' },
  { key: 'dibayar', label: 'Dibayar' }
]
const localDate = (d) =>
  `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
const route = useRoute()
const router = useRouter()
const date = ref(
  typeof route.query.date === 'string' && route.query.date
    ? route.query.date
    : localDate(new Date())
)
const board = ref({})
const carryOver = ref([])
const error = ref('')
const now = ref(Date.now())
let timer, tick

async function load() {
  try {
    const res = (await client.get('/work-orders/board', { params: { date: date.value } })).data
    board.value = res.data || {}
    carryOver.value = res.meta?.carry_over || []
    error.value = ''
  } catch (e) {
    error.value = e.response?.data?.message || 'Papan gagal dimuat.'
  }
}

function items(key) {
  const list = [...(board.value[key] || [])]
  if (key === 'dikerjakan')
    return list.sort((a, b) => new Date(a.started_at) - new Date(b.started_at))
  return list
}

function elapsed(item) {
  if (!item.started_at) return ''
  const minutes = Math.max(0, Math.floor((now.value - new Date(item.started_at)) / 60000))
  return minutes < 60 ? `${minutes} menit` : `${Math.floor(minutes / 60)} jam ${minutes % 60} menit`
}

const total = computed(() =>
  statuses.reduce((sum, s) => sum + (board.value[s.key]?.length || 0), 0)
)
const isToday = computed(() => date.value === localDate(new Date()))
const dateLabel = computed(() =>
  isToday.value
    ? 'Hari ini'
    : new Date(`${date.value}T00:00:00`).toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
      })
)
const carryOverLabel = (value) =>
  new Date(`${value}T00:00:00`).toLocaleDateString('id-ID', {
    weekday: 'short',
    day: 'numeric',
    month: 'short'
  })

function shiftDate(days) {
  const next = new Date(`${date.value}T00:00:00`)
  next.setDate(next.getDate() + days)
  date.value = localDate(next)
}

watch(date, (value) => {
  router.replace({ query: isToday.value ? {} : { date: value } })
  load()
})

onMounted(() => {
  load()
  timer = setInterval(load, 10000)
  tick = setInterval(() => {
    now.value = Date.now()
  }, 30000)
})
onUnmounted(() => {
  clearInterval(timer)
  clearInterval(tick)
})
</script>

<template>
  <main class="grid gap-6">
    <header class="grid gap-3">
      <div class="flex flex-wrap items-end justify-between gap-2">
        <div>
          <p class="text-sm text-muted">{{ dateLabel }} · {{ total }} order</p>
          <h1 class="text-2xl font-bold text-forest">Papan Antrian</h1>
        </div>
        <p class="text-xs text-muted">Diperbarui otomatis tiap 10 detik</p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <button class="service-muted text-sm" type="button" @click="shiftDate(-1)">
          ← Sebelumnya
        </button>
        <input
          v-model="date"
          type="date"
          class="service-input service-input-sm w-auto"
          aria-label="Tanggal papan antrian"
        />
        <button class="service-muted text-sm" type="button" @click="shiftDate(1)">
          Berikutnya →
        </button>
        <button
          v-if="!isToday"
          class="service-muted text-sm"
          type="button"
          @click="date = localDate(new Date())"
        >
          Kembali ke hari ini
        </button>
      </div>
    </header>
    <div v-if="error" class="service-alert">{{ error }}</div>

    <section
      v-if="carryOver.length"
      class="rounded-service border border-amber/50 bg-[#fbf1dd] p-4"
    >
      <h2 class="text-sm font-semibold text-[#7c5713]">
        Masih di bengkel dari tanggal sebelumnya ({{ carryOver.length }})
      </h2>
      <p class="mt-1 text-xs text-[#7c5713]">
        Mobil yang belum selesai atau belum dibayar tetap tampil di sini, termasuk yang menginap.
      </p>
      <ul class="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
        <li v-for="item in carryOver" :key="item.id">
          <RouterLink
            :to="`/work-orders/${item.id}`"
            class="flex items-center justify-between gap-3 rounded-lg border border-amber/40 bg-white p-3 no-underline hover:border-forest"
          >
            <span class="min-w-0">
              <span class="font-mono text-lg font-bold text-forest">{{
                item.queue_number_display
              }}</span>
              <span class="ml-2 truncate text-sm text-ink"
                >{{ item.vehicle?.plate_number }} · {{ item.vehicle?.brand }}</span
              >
              <span class="mt-0.5 block truncate text-xs text-muted"
                >{{ carryOverLabel(item.queue_date)
                }}<template v-if="item.mechanic"> · {{ item.mechanic.name }}</template></span
              >
            </span>
            <StatusBadge :status="item.status" />
          </RouterLink>
        </li>
      </ul>
    </section>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <section
        v-for="status in statuses"
        :key="status.key"
        class="min-w-0 rounded-service border border-line bg-white/60 p-3"
      >
        <h2 class="mb-3 flex items-center justify-between text-sm font-semibold text-ink">
          {{ status.label }}
          <span class="rounded-full bg-forest-soft px-2 py-0.5 text-xs text-forest">{{
            items(status.key).length
          }}</span>
        </h2>
        <div class="grid gap-2">
          <RouterLink
            v-for="item in items(status.key)"
            :key="item.id"
            :to="`/work-orders/${item.id}`"
            class="block rounded-lg border border-line bg-white p-3 no-underline shadow-sm hover:border-forest"
          >
            <p class="font-mono text-3xl font-bold leading-none text-forest">
              {{ item.queue_number_display }}
            </p>
            <p class="mt-2 truncate text-sm text-ink">
              {{ item.vehicle?.plate_number }} · {{ item.vehicle?.brand }}
            </p>
            <p v-if="item.mechanic" class="mt-1 truncate text-xs text-muted">
              Mekanik: {{ item.mechanic.name }}
            </p>
            <p v-if="status.key === 'dikerjakan'" class="mt-1 text-xs font-semibold text-amber-800">
              {{ elapsed(item) }}
            </p>
          </RouterLink>
          <p v-if="!items(status.key).length" class="px-1 py-4 text-sm text-muted">
            Tidak ada order.
          </p>
        </div>
      </section>
    </div>
  </main>
</template>
