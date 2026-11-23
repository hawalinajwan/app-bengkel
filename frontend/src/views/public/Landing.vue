<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import StatusBadge from '@/components/StatusBadge.vue'
import client from '@/api/client'
import { rupiah } from '@/utils/rupiah'

const router = useRouter()
const services = ref([])
const slots = ref([])
const code = ref('')
const error = ref('')
const loaded = ref(false)

const localDate = (d) =>
  `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
const tomorrowDate = localDate(new Date(Date.now() + 86400000))
const tomorrowLabel = new Date(`${tomorrowDate}T00:00:00`).toLocaleDateString('id-ID', {
  weekday: 'long',
  day: 'numeric',
  month: 'long'
})

const openSlots = computed(() => slots.value.filter((s) => s.available))
const remaining = computed(() => openSlots.value.reduce((sum, s) => sum + s.remaining, 0))
const capacity = computed(() => slots.value.reduce((max, s) => Math.max(max, s.remaining), 0))

const stages = [
  { key: 'menunggu', label: 'Menunggu' },
  { key: 'dikerjakan', label: 'Dikerjakan' },
  { key: 'selesai', label: 'Selesai' },
  { key: 'dibayar', label: 'Dibayar' }
]

const steps = [
  {
    no: '01',
    title: 'Booking',
    text: 'Pilih tanggal, jam, dan jasa. Kode booking langsung terbit.'
  },
  { no: '02', title: 'Check-in', text: 'Tunjukkan kode di kasir. Mobil masuk antrian hari itu.' },
  {
    no: '03',
    title: 'Dikerjakan',
    text: 'Mekanik memegang order. Progres tampil di halaman lacak.'
  },
  {
    no: '04',
    title: 'Bayar & ambil',
    text: 'Rincian jasa dan sparepart muncul, lalu mobil siap dibawa.'
  }
]

const duration = (minutes) => {
  if (!minutes) return '—'
  if (minutes < 60) return `${minutes} menit`
  return minutes % 60 === 0
    ? `${minutes / 60} jam`
    : `${Math.floor(minutes / 60)} jam ${minutes % 60} menit`
}

function lookup() {
  const value = code.value.trim().toUpperCase()
  if (!value) return
  router.push(`/track/${value}`)
}

onMounted(async () => {
  try {
    const [serviceRes, slotRes] = await Promise.all([
      client.get('/public/services'),
      client.get('/public/booking-slots', { params: { date: tomorrowDate } })
    ])
    services.value = serviceRes.data.data
    slots.value = slotRes.data.data
  } catch (e) {
    error.value = e.response?.data?.message || 'Data layanan belum bisa dimuat.'
  } finally {
    loaded.value = true
  }
})
</script>

<template>
  <div class="grid gap-14">
    <section class="grid gap-10 lg:grid-cols-[1.05fr_0.95fr] lg:items-start">
      <div>
        <p class="font-mono text-xs font-semibold uppercase tracking-[0.18em] text-amber">
          Layanan servis mobil
        </p>
        <h1 class="mt-3 text-4xl font-bold leading-[1.05] tracking-tight text-forest sm:text-5xl">
          Servis mobil<br class="hidden sm:block" />
          tanpa antre panjang.
        </h1>
        <p class="mt-4 max-w-md text-muted">
          Booking jadwal dari rumah, datang saat slot Anda, lalu pantau pengerjaan mobil lewat kode
          pelacakan.
        </p>

        <div class="mt-7 flex flex-wrap gap-3">
          <RouterLink
            class="service-primary inline-flex items-center text-sm no-underline"
            to="/booking"
            >Booking servis</RouterLink
          >
          <RouterLink
            class="service-muted inline-flex items-center text-sm no-underline"
            to="/track"
            >Lacak servis</RouterLink
          >
        </div>

        <dl class="mt-9 grid grid-cols-3 border-t border-line">
          <div class="border-r border-line py-4 pr-3">
            <dt class="whitespace-nowrap font-mono text-[11px] uppercase tracking-wider text-muted">
              Jam buka
            </dt>
            <dd class="mt-1 text-lg font-semibold text-ink">08–16</dd>
            <dd class="text-xs text-muted">Senin–Sabtu</dd>
          </div>
          <div class="border-r border-line px-3 py-4">
            <dt class="whitespace-nowrap font-mono text-[11px] uppercase tracking-wider text-muted">
              Slot/hari
            </dt>
            <dd class="mt-1 text-lg font-semibold text-ink">{{ slots.length || 8 }}</dd>
            <dd class="text-xs text-muted">3 mobil/jam</dd>
          </div>
          <div class="py-4 pl-3">
            <dt class="whitespace-nowrap font-mono text-[11px] uppercase tracking-wider text-muted">
              Jenis jasa
            </dt>
            <dd class="mt-1 text-lg font-semibold text-ink">
              {{ loaded ? services.length : '—' }}
            </dd>
            <dd class="text-xs text-muted">harga jasa</dd>
          </div>
        </dl>
      </div>

      <article
        class="rounded-service border border-line bg-white shadow-sm"
        aria-label="Contoh tiket antrian"
      >
        <div
          class="flex items-center justify-between gap-3 rounded-t-service bg-forest px-4 py-2.5 text-white"
        >
          <span class="font-mono text-[11px] uppercase tracking-[0.16em]"
            >Tiket antrian · contoh</span
          >
          <span class="font-mono text-[11px] text-white/70">MOBIL</span>
        </div>

        <div class="grid gap-4 p-5">
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="font-mono text-[11px] uppercase tracking-wider text-muted">Nomor antrian</p>
              <p class="font-mono text-6xl font-bold leading-none text-forest">014</p>
            </div>
            <StatusBadge status="dikerjakan" />
          </div>

          <dl
            class="grid grid-cols-2 gap-x-4 gap-y-3 border-y border-dashed border-line py-3 text-sm"
          >
            <div>
              <dt class="text-xs text-muted">Plat</dt>
              <dd class="font-mono font-semibold text-ink">B 1234 ABC</dd>
            </div>
            <div>
              <dt class="text-xs text-muted">Mobil</dt>
              <dd class="text-ink">Toyota Avanza</dd>
            </div>
            <div>
              <dt class="text-xs text-muted">Mekanik</dt>
              <dd class="text-ink">Budi Santoso</dd>
            </div>
            <div>
              <dt class="text-xs text-muted">Jasa</dt>
              <dd class="text-ink">Ganti Oli Mesin</dd>
            </div>
          </dl>

          <div>
            <div class="grid grid-cols-4 gap-1" aria-hidden="true">
              <span
                v-for="(stage, i) in stages"
                :key="stage.key"
                class="h-1.5 rounded-sm"
                :class="i <= 1 ? 'bg-forest' : 'bg-line'"
              />
            </div>
            <div class="mt-2 grid grid-cols-4 gap-1 font-mono text-[10px] uppercase tracking-wide">
              <span
                v-for="(stage, i) in stages"
                :key="stage.key"
                :class="i <= 1 ? 'font-semibold text-forest' : 'text-muted'"
                >{{ stage.label }}</span
              >
            </div>
          </div>

          <div class="flex items-center justify-between gap-3 border-t border-line pt-3">
            <span class="text-xs text-muted">Kode pelacakan</span>
            <span class="font-mono text-sm font-semibold tracking-wide text-forest">23A5X5FW</span>
          </div>
        </div>

        <p class="border-t border-dashed border-line px-5 py-3 text-xs text-muted">
          Tiket asli memuat QR. Pindai atau masukkan kodenya untuk memantau dari HP.
        </p>
      </article>
    </section>

    <section class="grid gap-8 border-t border-line pt-10 lg:grid-cols-[1.6fr_1fr] lg:gap-12">
      <div>
        <div class="flex flex-wrap items-baseline justify-between gap-2">
          <div>
            <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-amber">
              Slot terdekat
            </p>
            <h2 class="mt-1 text-xl font-bold text-forest">
              Besok, <span class="capitalize">{{ tomorrowLabel }}</span>
            </h2>
          </div>
          <p v-if="loaded && !error && slots.length" class="font-mono text-xs text-muted">
            sisa {{ remaining }} slot dari {{ slots.length * (capacity || 3) }}
          </p>
        </div>

        <div
          v-if="error"
          class="mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800"
        >
          {{ error }}
        </div>
        <p v-else-if="!loaded" class="mt-4 text-sm text-muted">Memuat slot…</p>
        <p v-else-if="!slots.length" class="mt-4 text-sm text-muted">
          Bengkel tutup pada tanggal ini. Pilih tanggal lain saat booking.
        </p>
        <ul v-else class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
          <li v-for="slot in slots" :key="slot.time">
            <RouterLink
              v-if="slot.available"
              :to="{ path: '/booking', query: { date: tomorrowDate, slot: slot.time } }"
              class="group flex h-full flex-col justify-between gap-2 rounded-lg border border-line bg-white px-3 py-2.5 no-underline transition hover:border-forest hover:bg-forest-soft"
            >
              <span class="font-mono text-base font-semibold text-ink">{{ slot.time }}</span>
              <span class="font-mono text-[11px] uppercase tracking-wide text-muted"
                >sisa {{ slot.remaining }}</span
              >
            </RouterLink>
            <div
              v-else
              class="flex h-full flex-col justify-between gap-2 rounded-lg border border-dashed border-line px-3 py-2.5"
            >
              <span class="font-mono text-base font-semibold text-muted line-through">{{
                slot.time
              }}</span>
              <span class="font-mono text-[11px] uppercase tracking-wide text-muted">penuh</span>
            </div>
          </li>
        </ul>
        <p class="mt-3 text-xs text-muted">
          Ketersediaan dibaca saat halaman dibuka. Sisa slot bisa berubah sampai booking dikirim.
        </p>
      </div>

      <div class="rounded-service border border-line bg-white p-5">
        <h2 class="text-base font-semibold text-ink">Sudah punya kode?</h2>
        <p class="mt-1 text-sm text-muted">
          Masukkan kode dari tiket atau email untuk melihat progres.
        </p>
        <form class="mt-4 grid gap-2" @submit.prevent="lookup">
          <label class="grid gap-1">
            <span class="sr-only">Kode pelacakan</span>
            <input
              v-model="code"
              class="service-input font-mono uppercase"
              placeholder="23A5X5FW"
              autocomplete="off"
            />
          </label>
          <button class="service-primary text-sm" type="submit" :disabled="!code.trim()">
            Cek status
          </button>
        </form>
      </div>
    </section>

    <section aria-labelledby="layanan-judul">
      <div class="flex flex-wrap items-end justify-between gap-2 border-b border-line pb-3">
        <div>
          <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-amber">Daftar kerja</p>
          <h2 id="layanan-judul" class="mt-1 text-xl font-bold text-forest">Layanan &amp; tarif</h2>
        </div>
        <p class="text-xs text-muted">Harga jasa, belum termasuk sparepart.</p>
      </div>

      <p v-if="!loaded" class="py-6 text-sm text-muted">Memuat layanan…</p>
      <ul v-else class="grid sm:grid-cols-2 sm:gap-x-10">
        <li
          v-for="service in services"
          :key="service.id"
          class="flex items-baseline justify-between gap-4 border-b border-line py-3"
        >
          <span class="min-w-0">
            <span class="block truncate text-sm font-semibold text-ink">{{ service.name }}</span>
            <span class="font-mono text-xs text-muted">± {{ duration(service.est_minutes) }}</span>
          </span>
          <span class="whitespace-nowrap font-mono text-sm font-semibold text-forest">{{
            rupiah(service.price)
          }}</span>
        </li>
      </ul>
    </section>

    <section aria-labelledby="alur-judul">
      <div class="border-b border-line pb-3">
        <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-amber">Alur servis</p>
        <h2 id="alur-judul" class="mt-1 text-xl font-bold text-forest">
          Dari booking sampai mobil diambil
        </h2>
      </div>
      <ol class="mt-5 grid grid-cols-2 gap-2 sm:grid-cols-4">
        <li
          v-for="(step, i) in steps"
          :key="step.no"
          class="grid gap-2 border-t-2 border-forest/25 pt-3"
          :class="i === 0 ? 'border-forest' : ''"
        >
          <span class="font-mono text-xs font-bold text-amber">{{ step.no }}</span>
          <p class="text-sm font-semibold text-ink">{{ step.title }}</p>
          <p class="text-sm text-muted">{{ step.text }}</p>
        </li>
      </ol>
    </section>

    <section
      class="grid gap-3 rounded-service border border-forest/25 bg-forest px-5 py-6 text-white sm:flex sm:items-center sm:justify-between"
    >
      <div>
        <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-white/60">Siap mulai?</p>
        <p class="mt-1 text-lg font-semibold">Pilih slot besok sebelum penuh.</p>
      </div>
      <RouterLink
        class="inline-flex min-h-11 items-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-forest no-underline hover:bg-white/90"
        to="/booking"
        >Booking sekarang</RouterLink
      >
    </section>
  </div>
</template>
