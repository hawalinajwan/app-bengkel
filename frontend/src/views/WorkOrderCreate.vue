<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import client from '@/api/client'

const router = useRouter()
const vehicles = ref([])
const mode = ref('lama')
const search = ref('')
const vehicle_id = ref('')
const complaint = ref('')
const error = ref('')
const busy = ref(false)
const form = ref({
  name: '',
  phone: '',
  plate_number: '',
  brand: '',
  model: '',
  year: ''
})

const filtered = computed(() => {
  const q = search.value.trim().toUpperCase()
  if (!q) return vehicles.value
  return vehicles.value.filter((v) =>
    `${v.plate_number} ${v.brand} ${v.model} ${v.customer?.name || ''}`.toUpperCase().includes(q)
  )
})
const canSubmit = computed(() => {
  if (busy.value) return false
  if (!complaint.value.trim()) return false
  if (mode.value === 'lama') return Boolean(vehicle_id.value)
  return Boolean(
    form.value.name.trim() &&
    form.value.plate_number.trim() &&
    form.value.brand.trim() &&
    form.value.model.trim()
  )
})

function setMode(next) {
  mode.value = next
  error.value = ''
}

onMounted(async () => {
  try {
    vehicles.value = (await client.get('/vehicles', { params: { per_page: 100 } })).data.data
  } catch (e) {
    error.value = e.response?.data?.message || 'Daftar kendaraan gagal dimuat.'
  }
})

async function save() {
  busy.value = true
  error.value = ''
  try {
    const payload =
      mode.value === 'lama'
        ? { vehicle_id: vehicle_id.value, complaint: complaint.value }
        : {
            customer: { name: form.value.name, phone: form.value.phone },
            vehicle: {
              plate_number: form.value.plate_number,
              brand: form.value.brand,
              model: form.value.model,
              year: form.value.year === '' ? null : form.value.year
            },
            complaint: complaint.value
          }
    const r = await client.post('/work-orders', payload)
    router.push(`/work-orders/${r.data.data.id}/ticket?code=${r.data.data.tracking_code}`)
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value =
      (errors && Object.values(errors)[0]?.[0]) || e.response?.data?.message || 'Pendaftaran gagal.'
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <main class="grid gap-6">
    <header class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <p class="font-mono text-[11px] font-semibold uppercase tracking-[0.18em] text-amber">
          Datang langsung
        </p>
        <h1 class="mt-1 text-2xl font-bold text-forest">Daftar order</h1>
        <p class="mt-1 text-sm text-muted">
          Pelanggan tanpa booking. Nomor antrian terbit untuk hari ini.
        </p>
      </div>
      <RouterLink
        class="service-muted inline-flex items-center text-sm no-underline"
        to="/work-orders"
        >← Papan antrian</RouterLink
      >
    </header>

    <p v-if="error" class="service-alert">{{ error }}</p>

    <div class="grid gap-6 lg:grid-cols-[1fr_280px] lg:items-start">
      <form
        class="grid gap-5 rounded-service border border-line bg-white p-5 shadow-sm"
        @submit.prevent="save"
      >
        <div>
          <p class="mb-2 text-sm font-semibold text-ink">Pelanggan</p>
          <div
            class="inline-flex rounded-lg border border-line p-1"
            role="group"
            aria-label="Jenis pelanggan"
          >
            <button
              type="button"
              class="min-h-9 rounded-md px-3 text-sm font-semibold"
              :class="mode === 'lama' ? 'bg-forest text-white' : 'text-muted hover:text-ink'"
              :aria-pressed="mode === 'lama'"
              @click="setMode('lama')"
            >
              Sudah pernah servis
            </button>
            <button
              type="button"
              class="min-h-9 rounded-md px-3 text-sm font-semibold"
              :class="mode === 'baru' ? 'bg-forest text-white' : 'text-muted hover:text-ink'"
              :aria-pressed="mode === 'baru'"
              @click="setMode('baru')"
            >
              Pelanggan baru
            </button>
          </div>
        </div>

        <template v-if="mode === 'lama'">
          <label class="grid gap-1 text-sm font-semibold text-ink">
            Cari kendaraan
            <input
              v-model="search"
              class="service-input"
              placeholder="Plat, merek, atau nama pelanggan"
              autocomplete="off"
            />
          </label>
          <label class="grid gap-1 text-sm font-semibold text-ink">
            Kendaraan
            <select v-model="vehicle_id" class="service-input" required>
              <option value="">Pilih kendaraan</option>
              <option v-for="v in filtered" :key="v.id" :value="v.id">
                {{ v.plate_number }} — {{ v.brand }} {{ v.model }} · {{ v.customer?.name }}
              </option>
            </select>
          </label>
          <p v-if="search && !filtered.length" class="text-sm text-muted">
            Tidak ada kendaraan yang cocok. Ganti ke “Pelanggan baru” bila mobil belum terdaftar.
          </p>
        </template>

        <template v-else>
          <div class="grid gap-4 sm:grid-cols-2">
            <label class="grid gap-1 text-sm font-semibold text-ink">
              Nama pelanggan
              <input v-model="form.name" class="service-input" required autocomplete="name" />
            </label>
            <label class="grid gap-1 text-sm font-semibold text-ink">
              Telepon
              <input
                v-model="form.phone"
                class="service-input font-mono"
                inputmode="tel"
                autocomplete="tel"
                placeholder="0812…"
              />
            </label>
          </div>
          <div class="grid gap-4 sm:grid-cols-3">
            <label class="grid gap-1 text-sm font-semibold text-ink">
              Plat nomor
              <input
                v-model="form.plate_number"
                class="service-input font-mono uppercase"
                required
                autocomplete="off"
                placeholder="B 1234 ABC"
              />
            </label>
            <label class="grid gap-1 text-sm font-semibold text-ink">
              Merek
              <input
                v-model="form.brand"
                class="service-input"
                required
                autocomplete="off"
                placeholder="Toyota"
              />
            </label>
            <label class="grid gap-1 text-sm font-semibold text-ink">
              Model
              <input
                v-model="form.model"
                class="service-input"
                required
                autocomplete="off"
                placeholder="Avanza"
              />
            </label>
          </div>
          <label class="grid max-w-32 gap-1 text-sm font-semibold text-ink">
            Tahun
            <input
              v-model="form.year"
              class="service-input font-mono"
              type="number"
              min="1900"
              :max="new Date().getFullYear() + 1"
              placeholder="2018"
            />
          </label>
          <p class="text-xs text-muted">
            Telepon yang sama dengan pelanggan lama otomatis memakai data pelanggan itu, jadi mobil
            kedua tidak membuat data ganda.
          </p>
        </template>

        <label class="grid gap-1 text-sm font-semibold text-ink">
          Keluhan
          <textarea
            v-model="complaint"
            class="service-input"
            rows="3"
            required
            placeholder="Contoh: bunyi kasar saat rem, AC tidak dingin"
          ></textarea>
        </label>

        <div class="flex flex-wrap items-center gap-3 border-t border-line pt-4">
          <button class="service-primary" type="submit" :disabled="!canSubmit">
            {{ busy ? 'Mendaftarkan…' : 'Daftarkan & buat tiket' }}
          </button>
          <RouterLink class="text-sm text-muted no-underline" to="/work-orders">Batal</RouterLink>
        </div>
      </form>

      <aside class="grid gap-4">
        <section class="rounded-service border border-line bg-white p-4 shadow-sm">
          <p class="font-mono text-[11px] uppercase tracking-[0.16em] text-muted">
            Alur datang langsung
          </p>
          <ol class="mt-3 grid gap-3 text-sm">
            <li class="grid gap-0.5 border-l-2 border-forest pl-3">
              <span class="font-semibold text-ink">1. Data pelanggan</span>
              <span class="text-muted">Pelanggan lama atau isi nama dan telepon.</span>
            </li>
            <li class="grid gap-0.5 border-l-2 border-line pl-3">
              <span class="font-semibold text-ink">2. Kendaraan</span>
              <span class="text-muted">Pilih mobil terdaftar atau daftarkan plat baru.</span>
            </li>
            <li class="grid gap-0.5 border-l-2 border-line pl-3">
              <span class="font-semibold text-ink">3. Tiket</span>
              <span class="text-muted">Nomor antrian dan QR pelacakan langsung terbit.</span>
            </li>
          </ol>
        </section>
        <p class="rounded-service border border-dashed border-line px-4 py-3 text-xs text-muted">
          Booking yang datang hari ini tetap masuk lewat menu
          <RouterLink class="font-semibold text-forest" to="/bookings">Booking</RouterLink> →
          check-in.
        </p>
      </aside>
    </div>
  </main>
</template>
