<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/api/client'
import { rupiah } from '@/utils/rupiah'
const route = useRoute()
const form = reactive({
  customer_name: '',
  phone: '',
  email: '',
  plate_number: '',
  vehicle_type: 'mobil',
  brand: '',
  model: '',
  service_id: '',
  complaint: '',
  booking_date: '',
  slot_time: ''
})
const services = ref([])
const slots = ref([])
const dpAmount = ref(0)
const error = ref('')
const busy = ref(false)
const code = ref('')
const localDate = (d) =>
  `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
const today = localDate(new Date())
const tomorrow = () => {
  const d = new Date()
  d.setDate(d.getDate() + 1)
  return localDate(d)
}
const maxDate = localDate(new Date(Date.now() + 30 * 86400000))
const prefillDate =
  typeof route.query.date === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(route.query.date)
    ? route.query.date
    : ''
const prefillSlot = typeof route.query.slot === 'string' ? route.query.slot : ''
form.booking_date =
  prefillDate && prefillDate >= today && prefillDate <= maxDate ? prefillDate : tomorrow()
const loadSlots = async () => {
  if (!form.booking_date) return
  try {
    const res = await api.get('/public/booking-slots', { params: { date: form.booking_date } })
    slots.value = res.data.data
    dpAmount.value = Number(res.data.meta?.dp_amount || 0)
    error.value = ''
    if (
      prefillSlot &&
      !form.slot_time &&
      slots.value.some((s) => s.time === prefillSlot && s.available)
    )
      form.slot_time = prefillSlot
  } catch (e) {
    slots.value = []
    error.value = e.response?.data?.message || 'Tanggal tidak dapat dipakai.'
  }
}
watch(() => form.booking_date, loadSlots)
onMounted(async () => {
  services.value = (await api.get('/public/services')).data.data
  loadSlots()
})
async function submit() {
  busy.value = true
  error.value = ''
  try {
    code.value = (await api.post('/public/bookings', form)).data.data.booking_code
  } catch (e) {
    error.value = e.response?.data?.message || 'Booking gagal.'
    loadSlots()
  } finally {
    busy.value = false
  }
}
const selected = computed(() => slots.value.find((s) => s.time === form.slot_time))
</script>

<template>
  <section class="mx-auto grid max-w-3xl gap-6">
    <header>
      <p class="text-sm text-muted">Layanan mobil</p>
      <h1 class="text-2xl font-bold text-forest">Booking servis</h1>
    </header>

    <div v-if="code" class="rounded-service border border-forest bg-white p-6 shadow-sm">
      <p class="text-sm font-semibold uppercase tracking-wider text-amber">Booking berhasil</p>
      <p class="mt-1 text-sm text-muted">Simpan kode ini untuk melacak dan membatalkan booking.</p>
      <p class="mt-3 font-mono text-3xl font-bold tracking-wide text-forest">{{ code }}</p>
      <dl class="mt-4 grid gap-1 border-t border-line pt-4 text-sm sm:grid-cols-[8rem_1fr]">
        <dt class="text-muted">Jadwal</dt>
        <dd>{{ form.booking_date }} pukul {{ form.slot_time }}</dd>
        <dt class="text-muted">Mobil</dt>
        <dd class="font-mono">{{ form.plate_number }} · {{ form.brand }} {{ form.model }}</dd>
        <dt class="text-muted">Layanan</dt>
        <dd>{{ services.find((s) => Number(s.id) === Number(form.service_id))?.name || '-' }}</dd>
        <dt class="text-muted">DP</dt>
        <dd>{{ dpAmount ? `${rupiah(dpAmount)} · transfer/QRIS, unggah bukti` : 'Tanpa DP' }}</dd>
      </dl>
      <p
        v-if="dpAmount"
        class="mt-4 rounded-lg border border-amber/40 bg-ivory px-3 py-2 text-sm text-ink"
      >
        Booking dikonfirmasi setelah DP
        <span class="font-mono font-semibold">{{ rupiah(dpAmount) }}</span> diverifikasi. Buka
        halaman status booking untuk mengunggah bukti transfer/QRIS.
      </p>
      <div class="mt-5 flex flex-wrap gap-2">
        <RouterLink class="service-primary text-sm no-underline" :to="`/booking/${code}`"
          >Lihat status booking</RouterLink
        >
      </div>
    </div>

    <form v-else class="grid gap-6" @submit.prevent="submit">
      <section class="grid gap-3 rounded-service border border-line bg-white p-5 shadow-sm">
        <h2 class="font-semibold text-ink">1. Pilih tanggal dan jam</h2>
        <label class="grid max-w-xs gap-1 text-sm font-semibold text-ink">
          Tanggal
          <input
            v-model="form.booking_date"
            type="date"
            class="service-input"
            :min="today"
            :max="maxDate"
            required
          />
        </label>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="slot in slots"
            :key="slot.time"
            type="button"
            class="min-h-11 rounded-lg px-4 py-2 font-semibold disabled:cursor-not-allowed disabled:opacity-50"
            :class="
              form.slot_time === slot.time
                ? 'bg-forest text-white'
                : 'border border-forest text-forest hover:bg-forest-soft'
            "
            :disabled="!slot.available"
            @click="form.slot_time = slot.time"
          >
            {{ slot.time }} <span class="text-xs font-normal">(sisa {{ slot.remaining }})</span>
          </button>
          <p v-if="!slots.length" class="text-sm text-muted">
            Tidak ada jam tersedia pada tanggal ini.
          </p>
        </div>
        <p
          v-if="dpAmount"
          class="rounded-lg border border-amber/40 bg-ivory px-3 py-2 text-sm text-ink"
        >
          Booking dikonfirmasi setelah DP
          <span class="font-mono font-semibold">{{ rupiah(dpAmount) }}</span> diterima via transfer
          atau QRIS. Bukti transfer diunggah di halaman status booking.
        </p>
      </section>

      <section class="grid gap-4 rounded-service border border-line bg-white p-5 shadow-sm">
        <h2 class="font-semibold text-ink">2. Data pelanggan</h2>
        <div class="grid gap-4 sm:grid-cols-2">
          <label class="grid gap-1 text-sm font-semibold text-ink"
            >Nama<input v-model="form.customer_name" class="service-input" required
          /></label>
          <label class="grid gap-1 text-sm font-semibold text-ink"
            >Telepon<input v-model="form.phone" class="service-input" required
          /></label>
          <label class="grid gap-1 text-sm font-semibold text-ink sm:col-span-2"
            >Email<input v-model="form.email" type="email" class="service-input"
          /></label>
        </div>
      </section>

      <section class="grid gap-4 rounded-service border border-line bg-white p-5 shadow-sm">
        <h2 class="font-semibold text-ink">3. Data mobil</h2>
        <div class="grid gap-4 sm:grid-cols-2">
          <label class="grid gap-1 text-sm font-semibold text-ink sm:col-span-2"
            >Plat nomor<input
              v-model="form.plate_number"
              class="service-input font-mono uppercase"
              required
          /></label>
          <label class="grid gap-1 text-sm font-semibold text-ink"
            >Merek<input v-model="form.brand" class="service-input" required
          /></label>
          <label class="grid gap-1 text-sm font-semibold text-ink"
            >Model<input v-model="form.model" class="service-input" required
          /></label>
        </div>
      </section>

      <section class="grid gap-4 rounded-service border border-line bg-white p-5 shadow-sm">
        <h2 class="font-semibold text-ink">4. Keperluan servis</h2>
        <label class="grid gap-1 text-sm font-semibold text-ink">
          Jasa
          <select v-model="form.service_id" class="service-input" required>
            <option value="">Pilih jasa</option>
            <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </label>
        <label class="grid gap-1 text-sm font-semibold text-ink"
          >Keluhan<textarea v-model="form.complaint" class="service-input" rows="3"></textarea>
        </label>
      </section>

      <div class="flex flex-wrap items-center gap-4">
        <button class="service-primary" type="submit" :disabled="busy || !selected">
          {{ busy ? 'Mengirim…' : selected ? `Buat booking ${form.slot_time}` : 'Buat booking' }}
        </button>
        <p v-if="selected" class="text-sm text-muted">
          {{ form.booking_date }} pukul {{ form.slot_time }} · sisa {{ selected.remaining }} slot
        </p>
        <p v-else class="text-sm text-muted">Pilih jam terlebih dahulu.</p>
        <p v-if="error" class="text-sm text-red-800">{{ error }}</p>
      </div>
    </form>
  </section>
</template>
