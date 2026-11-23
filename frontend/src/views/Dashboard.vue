<script setup>
import { computed, onMounted, ref } from 'vue'
import client from '@/api/client'
import { rupiah } from '@/utils/rupiah'

const data = ref(null)
const loading = ref(true)
const error = ref('')

const todayLabel = new Date().toLocaleDateString('id-ID', {
  weekday: 'long',
  day: 'numeric',
  month: 'long',
  year: 'numeric'
})
const statusRows = [
  { key: 'menunggu', label: 'Menunggu' },
  { key: 'dikerjakan', label: 'Dikerjakan' },
  { key: 'selesai', label: 'Selesai' },
  { key: 'dibayar', label: 'Dibayar' },
  { key: 'batal', label: 'Batal' }
]
const methodLabel = { tunai: 'Tunai', transfer: 'Transfer', qris: 'QRIS', dp: 'DP booking' }
const methods = computed(() =>
  ['tunai', 'transfer', 'qris', 'dp'].map((key) => ({
    key,
    label: methodLabel[key],
    total: Number(data.value?.payments?.by_method?.[key] || 0)
  }))
)
const active = computed(
  () => Number(data.value?.counts?.menunggu || 0) + Number(data.value?.counts?.dikerjakan || 0)
)
const time = (value) =>
  value ? new Date(value).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '—'
const todayKey = new Date().toLocaleDateString('sv-SE')
const shortDate = (value) =>
  value
    ? new Date(`${value}T00:00:00`).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
    : ''

onMounted(async () => {
  try {
    data.value = (await client.get('/dashboard')).data.data
  } catch (e) {
    error.value = e.response?.data?.message || 'Dashboard gagal dimuat.'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <main class="grid gap-5">
    <header class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <p class="font-mono text-[11px] font-semibold uppercase tracking-[0.18em] text-amber">
          {{ todayLabel }}
        </p>
        <h1 class="mt-1 text-2xl font-bold text-forest">Dashboard</h1>
      </div>
      <div class="flex flex-wrap gap-2">
        <RouterLink
          class="service-primary inline-flex items-center text-sm no-underline"
          to="/work-orders/new"
          >Daftar order</RouterLink
        >
        <RouterLink
          class="service-muted inline-flex items-center text-sm no-underline"
          to="/work-orders"
          >Papan antrian</RouterLink
        >
      </div>
    </header>

    <div v-if="loading" class="text-muted">Memuat dashboard…</div>
    <div v-else-if="error" class="service-alert">{{ error }}</div>

    <div v-else class="grid gap-4 lg:grid-cols-6">
      <section
        class="overflow-hidden rounded-service border border-line bg-white shadow-sm lg:col-span-4"
        aria-labelledby="kas-judul"
      >
        <div class="flex items-center justify-between gap-3 bg-forest px-5 py-2.5 text-white">
          <h2
            id="kas-judul"
            class="font-mono text-[11px] font-semibold uppercase tracking-[0.16em]"
          >
            Kas hari ini
          </h2>
          <span
            class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-2.5 py-0.5 font-mono text-[11px]"
          >
            <span class="h-1.5 w-1.5 rounded-full bg-amber" aria-hidden="true" />
            {{ data.payments.count }} transaksi
          </span>
        </div>

        <div class="grid gap-4 p-5">
          <div>
            <p class="font-mono text-[11px] uppercase tracking-wider text-muted">Diterima</p>
            <p class="font-mono text-4xl font-bold leading-none text-ink">
              {{ rupiah(data.payments.total) }}
            </p>
          </div>

          <ol
            class="flex items-center gap-2 font-mono text-[11px] uppercase tracking-wide"
            aria-label="Alur pembayaran hari ini"
          >
            <li class="flex items-center gap-1.5 text-muted">
              <span
                class="grid h-4 w-4 place-items-center rounded-full bg-line text-[9px] text-ink"
                >{{ data.counts.selesai || 0 }}</span
              >
              Selesai
            </li>
            <span class="h-px flex-1 bg-line" aria-hidden="true" />
            <li class="flex items-center gap-1.5 font-semibold text-forest">
              <span
                class="grid h-4 w-4 place-items-center rounded-full bg-forest text-[9px] text-white"
                >✓</span
              >
              Dibayar {{ data.counts.dibayar || 0 }}
            </li>
          </ol>

          <dl class="grid gap-1 sm:grid-cols-4 sm:gap-2">
            <div
              v-for="m in methods"
              :key="m.key"
              class="flex items-baseline justify-between gap-2 rounded-lg border border-line px-3 py-2 sm:block"
            >
              <dt class="font-mono text-[10px] uppercase tracking-wider text-muted">
                {{ m.label }}
              </dt>
              <dd class="mt-0.5 truncate font-mono text-sm font-semibold text-ink">
                {{ rupiah(m.total) }}
              </dd>
            </div>
          </dl>

          <div class="border-t border-line pt-3">
            <div class="flex items-center justify-between">
              <p class="font-mono text-[11px] uppercase tracking-wider text-muted">
                Pembayaran terakhir
              </p>
              <RouterLink class="text-xs font-semibold text-forest no-underline" to="/work-orders"
                >Lihat antrian</RouterLink
              >
            </div>
            <p v-if="!data.payments.recent.length" class="mt-2 text-sm text-muted">
              Belum ada pembayaran masuk hari ini.
            </p>
            <ul v-else class="mt-1">
              <li
                v-for="p in data.payments.recent"
                :key="p.id"
                class="flex items-center justify-between gap-3 border-b border-line py-2 text-sm last:border-0"
              >
                <span class="flex min-w-0 items-baseline gap-2">
                  <span class="font-mono font-semibold text-forest">{{
                    p.queue_number_display
                  }}</span>
                  <span class="truncate text-ink">{{ p.plate_number }}</span>
                  <span class="hidden truncate text-xs text-muted sm:inline">{{ p.customer }}</span>
                </span>
                <span class="flex shrink-0 items-baseline gap-3">
                  <span class="font-mono text-[10px] uppercase tracking-wider text-muted"
                    >{{
                      p.queue_date && p.queue_date !== todayKey
                        ? shortDate(p.queue_date) + ' · '
                        : ''
                    }}{{ methodLabel[p.method] || p.method }} · {{ time(p.paid_at) }}</span
                  >
                  <span class="font-mono text-sm font-semibold text-ink">{{
                    rupiah(p.amount)
                  }}</span>
                </span>
              </li>
            </ul>
          </div>
        </div>
      </section>

      <section
        class="grid content-start gap-4 rounded-service border border-line bg-white p-5 shadow-sm lg:col-span-2"
        aria-labelledby="antrian-judul"
      >
        <div class="flex items-baseline justify-between gap-2">
          <h2 id="antrian-judul" class="font-semibold text-ink">Antrian hari ini</h2>
          <span class="font-mono text-xs text-muted"
            >{{ data.counts.menunggu || 0 }} + {{ data.counts.dikerjakan || 0 }} aktif</span
          >
        </div>
        <p class="font-mono text-5xl font-bold leading-none text-forest">{{ active }}</p>
        <ul class="grid gap-1 text-sm">
          <li
            v-for="row in statusRows"
            :key="row.key"
            class="flex items-center justify-between gap-2 border-b border-line py-1.5 last:border-0"
          >
            <span :class="row.key === 'batal' ? 'text-muted' : 'text-ink'">{{ row.label }}</span>
            <span
              class="font-mono font-semibold"
              :class="row.key === 'batal' ? 'text-muted' : 'text-forest'"
              >{{ data.counts[row.key] || 0 }}</span
            >
          </li>
        </ul>
        <RouterLink
          class="service-muted inline-flex items-center justify-center text-sm no-underline"
          to="/work-orders"
          >Buka papan</RouterLink
        >
      </section>

      <section
        class="rounded-service border border-line bg-white p-5 shadow-sm lg:col-span-2"
        aria-labelledby="tindakan-judul"
      >
        <h2 id="tindakan-judul" class="font-semibold text-ink">Perlu tindakan</h2>
        <ul class="mt-2 grid gap-1 text-sm">
          <li class="flex items-center justify-between gap-2 border-b border-line py-2">
            <RouterLink class="text-ink no-underline hover:text-forest" to="/bookings"
              >Booking menunggu</RouterLink
            >
            <span
              class="font-mono font-semibold"
              :class="data.action_items.pending_bookings ? 'text-amber' : 'text-muted'"
              >{{ data.action_items.pending_bookings }}</span
            >
          </li>
          <li class="flex items-center justify-between gap-2 border-b border-line py-2">
            <RouterLink class="text-ink no-underline hover:text-forest" to="/work-orders"
              >Belum ada mekanik</RouterLink
            >
            <span
              class="font-mono font-semibold"
              :class="data.action_items.unassigned_orders ? 'text-amber' : 'text-muted'"
              >{{ data.action_items.unassigned_orders }}</span
            >
          </li>
          <li class="flex items-center justify-between gap-2 py-2">
            <span class="text-ink">Selesai belum dibayar</span>
            <span
              class="font-mono font-semibold"
              :class="data.action_items.unpaid_orders ? 'text-amber' : 'text-muted'"
              >{{ data.action_items.unpaid_orders }}</span
            >
          </li>
        </ul>
      </section>

      <section
        class="rounded-service border border-line bg-white p-5 shadow-sm lg:col-span-2"
        aria-labelledby="tagihan-judul"
      >
        <div class="flex items-baseline justify-between gap-2">
          <h2 id="tagihan-judul" class="font-semibold text-ink">Menunggu bayar</h2>
          <span class="font-mono text-xs text-muted">{{ data.unpaid_orders.length }} order</span>
        </div>
        <p v-if="!data.unpaid_orders.length" class="mt-3 text-sm text-muted">
          Semua order selesai sudah dibayar.
        </p>
        <ul v-else class="mt-2 grid gap-1">
          <li
            v-for="o in data.unpaid_orders"
            :key="o.id"
            class="flex items-center justify-between gap-2 border-b border-line py-2 text-sm last:border-0"
          >
            <span class="min-w-0">
              <RouterLink
                class="font-mono font-semibold text-forest no-underline"
                :to="`/work-orders/${o.id}`"
                >{{ o.queue_number_display }}</RouterLink
              >
              <span class="ml-2 text-ink">{{ o.plate_number }}</span>
            </span>
            <span class="shrink-0 font-mono font-semibold text-ink">{{ rupiah(o.total) }}</span>
          </li>
        </ul>
      </section>

      <section
        class="rounded-service border border-line bg-white p-5 shadow-sm lg:col-span-2"
        aria-labelledby="stok-judul"
      >
        <div class="flex items-baseline justify-between gap-2">
          <h2 id="stok-judul" class="font-semibold text-ink">Stok menipis</h2>
          <span class="font-mono text-xs text-muted">{{ data.low_stock.length }} item</span>
        </div>
        <p v-if="!data.low_stock.length" class="mt-3 text-sm text-muted">
          Semua stok di atas minimum.
        </p>
        <ul v-else class="mt-2 grid gap-1">
          <li
            v-for="part in data.low_stock"
            :key="part.id"
            class="flex items-center justify-between gap-2 border-b border-line py-2 text-sm last:border-0"
          >
            <span class="truncate text-ink">{{ part.name }}</span>
            <span class="shrink-0 font-mono text-xs">
              <span class="font-semibold text-red-800">{{ part.stock }}</span>
              <span class="text-muted"> / min {{ part.min_stock }}</span>
            </span>
          </li>
        </ul>
      </section>
    </div>
  </main>
</template>
