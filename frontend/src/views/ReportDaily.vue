<script setup>
import { computed, ref } from 'vue'
import client from '@/api/client'
import { rupiah } from '@/utils/rupiah'

const date = ref(new Date().toISOString().slice(0, 10))
const report = ref(null)
const error = ref('')
const loading = ref(false)

const statusOrder = [
  ['menunggu', 'Menunggu'],
  ['dikerjakan', 'Dikerjakan'],
  ['selesai', 'Selesai'],
  ['dibayar', 'Dibayar'],
  ['batal', 'Batal']
]

const totalOrders = computed(() =>
  Object.values(report.value?.counts || {}).reduce((sum, n) => sum + Number(n), 0)
)
const rows = computed(() =>
  statusOrder.map(([key, label]) => ({
    key,
    label,
    count: Number(report.value?.counts?.[key] ?? 0)
  }))
)
const dateLabel = computed(() =>
  date.value
    ? new Date(`${date.value}T00:00:00`).toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
      })
    : '-'
)

async function load() {
  loading.value = true
  error.value = ''
  try {
    report.value = (await client.get('/reports/daily', { params: { date: date.value } })).data.data
  } catch (e) {
    error.value = e.response?.data?.message || 'Laporan gagal dimuat.'
  } finally {
    loading.value = false
  }
}
load()
</script>

<template>
  <main class="grid gap-6">
    <header class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <p class="text-sm text-muted">Laporan</p>
        <h1 class="text-2xl font-bold text-forest">Laporan harian</h1>
        <p class="mt-1 text-sm text-muted">{{ dateLabel }}</p>
      </div>
      <form class="flex flex-wrap items-end gap-2" @submit.prevent="load">
        <label class="grid gap-1 text-sm font-semibold text-ink">
          Tanggal
          <input v-model="date" type="date" class="service-input font-mono" required />
        </label>
        <button class="service-primary" :disabled="loading">
          {{ loading ? 'Memuat…' : 'Muat' }}
        </button>
      </form>
    </header>

    <p
      v-if="error"
      class="rounded-service border border-red-200 bg-red-50 p-4 text-sm text-red-800"
    >
      {{ error }}
    </p>

    <template v-if="report">
      <section class="grid gap-4 md:grid-cols-3">
        <div
          class="rounded-service border border-forest bg-forest p-5 text-white shadow-sm md:col-span-2"
        >
          <p class="text-xs font-semibold uppercase tracking-wider text-white/70">
            Pendapatan dibayar
          </p>
          <p class="mt-2 font-mono text-4xl font-bold">{{ rupiah(report.revenue) }}</p>
          <p class="mt-2 text-sm text-white/70">
            {{ rows.find((r) => r.key === 'dibayar').count }} order dibayar dari
            {{ totalOrders }} order hari ini.
          </p>
        </div>
        <div class="grid gap-3 rounded-service border border-line bg-white p-5 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-wider text-muted">Total order</p>
          <p class="font-mono text-4xl font-bold text-ink">{{ totalOrders }}</p>
          <p class="text-sm text-muted">Seluruh status pada tanggal ini.</p>
        </div>
      </section>

      <section class="rounded-service border border-line bg-white shadow-sm">
        <table class="w-full text-left text-sm">
          <caption class="border-b border-line px-5 py-3 text-left text-sm font-semibold text-ink">
            Rincian per status
          </caption>
          <thead class="bg-ivory text-xs uppercase tracking-wide text-muted">
            <tr>
              <th scope="col" class="px-5 py-2 font-semibold">Status</th>
              <th scope="col" class="px-5 py-2 text-right font-semibold">Jumlah order</th>
              <th scope="col" class="px-5 py-2 text-right font-semibold">Porsi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-line">
            <tr v-for="row in rows" :key="row.key">
              <td class="px-5 py-3 text-ink">{{ row.label }}</td>
              <td class="px-5 py-3 text-right font-mono text-ink">{{ row.count }}</td>
              <td class="px-5 py-3 text-right font-mono text-muted">
                {{ totalOrders ? Math.round((row.count / totalOrders) * 100) : 0 }}%
              </td>
            </tr>
          </tbody>
        </table>
      </section>
    </template>
  </main>
</template>
