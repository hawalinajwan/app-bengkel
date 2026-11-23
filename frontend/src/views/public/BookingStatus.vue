<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/api/client'
import ConfirmModal from '@/components/ConfirmModal.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { rupiah } from '@/utils/rupiah'
const route = useRoute()
const data = ref(null)
const error = ref('')
const confirm = ref(false)
const busy = ref(false)
const dpMethod = ref('transfer')
const dpProof = ref(null)
const dpPreview = ref('')
const dpError = ref('')
const dpBusy = ref(false)
const dpInputKey = ref(0)
const methodLabel = { transfer: 'Transfer', qris: 'QRIS' }
async function load() {
  try {
    data.value = (await api.get(`/public/bookings/${route.params.code}`)).data.data
    if (data.value?.dp_method) dpMethod.value = data.value.dp_method
  } catch (e) {
    error.value = e.response?.data?.message || 'Booking tidak ditemukan.'
  }
}
async function cancel() {
  busy.value = true
  try {
    data.value = (await api.post(`/public/bookings/${route.params.code}/cancel`)).data.data
    confirm.value = false
  } catch (e) {
    error.value = e.response?.data?.message || 'Pembatalan gagal.'
  } finally {
    busy.value = false
  }
}
function onProof(event) {
  const file = event.target.files?.[0] || null
  dpProof.value = file
  if (file) dpError.value = ''
  if (dpPreview.value) URL.revokeObjectURL(dpPreview.value)
  dpPreview.value = file && file.type.startsWith('image/') ? URL.createObjectURL(file) : ''
}
async function sendDp() {
  if (!dpProof.value) {
    dpError.value = 'Pilih file bukti transfer atau QRIS.'
    return
  }
  dpBusy.value = true
  dpError.value = ''
  const body = new FormData()
  body.append('method', dpMethod.value)
  body.append('proof', dpProof.value)
  try {
    data.value = (await api.post(`/public/bookings/${route.params.code}/dp`, body)).data.data
    dpProof.value = null
    dpInputKey.value += 1
    if (dpPreview.value) {
      URL.revokeObjectURL(dpPreview.value)
      dpPreview.value = ''
    }
    dpMethod.value = data.value.dp_method || dpMethod.value
  } catch (e) {
    dpError.value = e.response?.data?.message || 'Bukti gagal dikirim.'
  } finally {
    dpBusy.value = false
  }
}
onMounted(load)
</script>

<template>
  <section class="mx-auto grid max-w-xl gap-6">
    <header>
      <p class="text-sm text-muted">Booking servis</p>
      <h1 class="text-2xl font-bold text-forest">Status booking</h1>
    </header>
    <p
      v-if="error"
      class="rounded-service border border-red-200 bg-red-50 p-4 text-sm text-red-800"
    >
      {{ error }}
    </p>

    <article
      v-else-if="data"
      class="overflow-hidden rounded-service border border-line bg-white shadow-sm"
    >
      <div class="flex items-center justify-between gap-4 bg-forest px-5 py-4 text-white">
        <p class="font-mono text-2xl font-bold">{{ data.booking_code }}</p>
        <StatusBadge :status="data.status" />
      </div>
      <dl class="grid gap-3 px-5 py-5 text-sm sm:grid-cols-2">
        <div>
          <dt class="text-muted">Jadwal</dt>
          <dd class="font-semibold">{{ data.booking_date }} pukul {{ data.slot_time }}</dd>
        </div>
        <div>
          <dt class="text-muted">Mobil</dt>
          <dd class="font-semibold">{{ data.brand }} {{ data.model }}</dd>
          <dd class="font-mono text-muted">{{ data.plate_number }}</dd>
        </div>
        <div>
          <dt class="text-muted">DP</dt>
          <dd v-if="data.dp_amount" class="font-semibold">
            {{ rupiah(data.dp_amount) }}
            <span v-if="data.dp_paid" class="text-forest"
              >· sudah dibayar{{
                data.dp_method ? ` (${methodLabel[data.dp_method] || data.dp_method})` : ''
              }}</span
            >
            <span v-else-if="data.dp_proof" class="text-forest">· bukti terkirim</span>
            <span v-else class="text-amber">· belum dibayar</span>
          </dd>
          <dd v-else class="text-muted">Tanpa DP</dd>
        </div>
      </dl>

      <section
        v-if="data.dp_amount && !data.dp_paid && ['menunggu', 'dikonfirmasi'].includes(data.status)"
        class="grid gap-3 border-t border-dashed border-line px-5 py-5"
      >
        <header class="flex flex-wrap items-baseline justify-between gap-2">
          <h2 class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-muted">
            Bayar DP {{ rupiah(data.dp_amount) }}
          </h2>
          <span class="text-xs" :class="data.dp_proof ? 'text-forest' : 'text-amber'">{{
            data.dp_proof
              ? 'Bukti terkirim · menunggu verifikasi bengkel'
              : 'Transfer atau QRIS, lalu unggah bukti'
          }}</span>
        </header>
        <div class="grid grid-cols-2 gap-2">
          <label
            v-for="m in [
              ['transfer', 'Transfer'],
              ['qris', 'QRIS']
            ]"
            :key="m[0]"
            class="flex min-h-11 cursor-pointer items-center justify-center rounded-lg border px-2 text-sm font-semibold"
            :class="
              dpMethod === m[0]
                ? 'border-forest bg-forest-soft text-forest'
                : 'border-line bg-white text-muted hover:bg-ivory'
            "
          >
            <input
              v-model="dpMethod"
              type="radio"
              :value="m[0]"
              name="public-dp-method"
              class="sr-only"
            />
            {{ m[1] }}
          </label>
        </div>
        <label class="grid gap-1 text-sm font-semibold text-ink">
          Bukti transfer / QRIS
          <input
            :key="dpInputKey"
            type="file"
            accept="image/jpeg,image/png,image/webp,application/pdf"
            class="service-input py-1.5 text-sm file:mr-3 file:rounded file:border-0 file:bg-forest-soft file:px-2 file:py-1 file:text-xs file:font-semibold file:text-forest"
            @change="onProof"
          />
          <span class="text-xs font-normal text-muted">JPG/PNG/WEBP atau PDF, maksimal 4 MB.</span>
        </label>
        <img
          v-if="dpPreview"
          :src="dpPreview"
          alt="Pratinjau bukti DP"
          class="max-h-36 w-full rounded-lg border border-line bg-ivory object-contain"
        />
        <p v-if="dpError" class="text-sm text-red-800">{{ dpError }}</p>
        <button
          class="service-primary"
          type="button"
          :disabled="dpBusy || !dpProof"
          @click="sendDp"
        >
          {{ dpBusy ? 'Mengirim…' : data.dp_proof ? 'Kirim ulang bukti' : 'Kirim bukti DP' }}
        </button>
      </section>
      <div class="flex flex-wrap gap-3 border-t border-dashed border-line px-5 py-4">
        <RouterLink
          v-if="data.tracking_code"
          :to="`/track/${data.tracking_code}`"
          class="service-primary no-underline"
          >Lacak servis</RouterLink
        >
        <button
          v-if="['menunggu', 'dikonfirmasi'].includes(data.status) && !data.dp_paid"
          class="service-danger"
          @click="confirm = true"
        >
          Batalkan booking
        </button>
        <p
          v-if="['menunggu', 'dikonfirmasi'].includes(data.status) && data.dp_paid"
          class="text-xs text-muted"
        >
          DP sudah dibayar. Pembatalan lewat bengkel agar DP bisa dikembalikan.
        </p>
      </div>
    </article>

    <ConfirmModal
      :show="confirm"
      message="Batalkan booking ini?"
      :busy="busy"
      confirm-label="Batalkan"
      @confirm="cancel"
      @cancel="confirm = false"
    />
  </section>
</template>
