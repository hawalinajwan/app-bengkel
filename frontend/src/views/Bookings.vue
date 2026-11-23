<script setup>
import { onMounted, reactive, ref } from 'vue'
import api from '@/api/client'
import StatusBadge from '@/components/StatusBadge.vue'
import ConfirmModal from '@/components/ConfirmModal.vue'
import { rupiah } from '@/utils/rupiah'

const rows = ref([])
const loading = ref(true)
const error = ref('')
const filters = reactive({ status: 'menunggu', date: '' })
const busy = ref(null)
const rejectRow = ref(null)
const rejectNote = ref('')
const cancelRow = ref(null)
const dpRow = ref(null)
const dpMethod = ref('transfer')
const dpProof = ref(null)
const dpPreview = ref('')

const today = new Date().toLocaleDateString('sv-SE')
function canCheckIn(row) {
  return row.booking_date === today
}
function canMarkNoShow(row) {
  return row.booking_date < today
}
function dpUnpaid(row) {
  return row.dp_amount > 0 && !row.dp_paid_at
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    rows.value = (await api.get('/bookings', { params: filters })).data.data
  } catch (e) {
    error.value = e.response?.data?.message || 'Booking gagal dimuat.'
  } finally {
    loading.value = false
  }
}

async function action(row, type) {
  if (type === 'reject') {
    rejectRow.value = row
    rejectNote.value = ''
    return
  }
  if (type === 'cancel') {
    cancelRow.value = row
    return
  }
  if (type === 'dp') {
    dpRow.value = row
    dpMethod.value = row.dp_method === 'qris' ? 'qris' : 'transfer'
    dpProof.value = null
    dpPreview.value = ''
    return
  }
  busy.value = row.id
  error.value = ''
  try {
    const r = await api.post(`/bookings/${row.id}/${type}`)
    if (type === 'check-in') {
      location.href = `/work-orders/${r.data.data.id}/ticket?code=${r.data.data.tracking_code}`
      return
    }
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || 'Aksi gagal.'
  } finally {
    busy.value = null
  }
}

async function confirmDp() {
  if (!dpProof.value && !dpRow.value.dp_proof) {
    error.value = 'Belum ada bukti transfer dari pelanggan.'
    return
  }
  busy.value = dpRow.value.id
  error.value = ''
  const body = new FormData()
  body.append('method', dpMethod.value)
  if (dpProof.value) body.append('proof', dpProof.value)
  try {
    await api.post(`/bookings/${dpRow.value.id}/dp`, body)
    dpRow.value = null
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || 'DP gagal dicatat.'
  } finally {
    busy.value = null
  }
}

function onProof(event) {
  const file = event.target.files?.[0] || null
  dpProof.value = file
  if (file) error.value = ''
  if (dpPreview.value) URL.revokeObjectURL(dpPreview.value)
  dpPreview.value = file && file.type.startsWith('image/') ? URL.createObjectURL(file) : ''
}

async function openProof(row) {
  error.value = ''
  try {
    const res = await api.get(`/bookings/${row.id}/dp-proof`, { responseType: 'blob' })
    const url = URL.createObjectURL(res.data)
    window.open(url, '_blank', 'noopener')
    setTimeout(() => URL.revokeObjectURL(url), 60000)
  } catch {
    error.value = 'Bukti pembayaran gagal dibuka.'
  }
}

async function confirmCancel() {
  busy.value = cancelRow.value.id
  error.value = ''
  try {
    await api.post(`/bookings/${cancelRow.value.id}/cancel`)
    cancelRow.value = null
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || 'Pembatalan gagal.'
  } finally {
    busy.value = null
  }
}

async function confirmReject() {
  if (!rejectNote.value.trim()) {
    error.value = 'Catatan penolakan wajib diisi.'
    return
  }
  busy.value = rejectRow.value.id
  error.value = ''
  try {
    await api.post(`/bookings/${rejectRow.value.id}/reject`, { staff_note: rejectNote.value })
    rejectRow.value = null
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || 'Penolakan gagal.'
  } finally {
    busy.value = null
  }
}

onMounted(load)
</script>

<template>
  <section class="grid gap-6">
    <header class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <p class="text-sm text-muted">Operasional</p>
        <h1 class="text-2xl font-bold text-forest">Booking</h1>
      </div>
      <div class="flex flex-wrap items-end gap-2">
        <label class="grid gap-1 text-sm font-semibold text-ink">
          Status
          <select v-model="filters.status" class="service-input w-auto" @change="load">
            <option value="">Semua status</option>
            <option value="menunggu">Menunggu</option>
            <option value="dikonfirmasi">Dikonfirmasi</option>
            <option value="check_in">Check-in</option>
            <option value="ditolak">Ditolak</option>
            <option value="dibatalkan">Dibatalkan</option>
            <option value="tidak_hadir">Tidak hadir</option>
          </select>
        </label>
        <label class="grid gap-1 text-sm font-semibold text-ink">
          Tanggal
          <input v-model="filters.date" type="date" class="service-input w-auto" @change="load" />
        </label>
      </div>
    </header>

    <p
      v-if="error"
      class="rounded-service border border-red-200 bg-red-50 p-4 text-sm text-red-800"
    >
      {{ error }}
    </p>

    <div class="overflow-x-auto rounded-service border border-line bg-white shadow-sm">
      <table class="service-table">
        <thead>
          <tr>
            <th>Kode</th>
            <th>Pelanggan</th>
            <th>Jadwal</th>
            <th>DP</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="6" class="py-8 text-center text-muted">Memuat…</td>
          </tr>
          <tr v-else-if="!rows.length">
            <td colspan="6" class="py-8 text-center text-muted">
              Tidak ada booking pada filter ini.
            </td>
          </tr>
          <tr v-for="row in rows" v-else :key="row.id">
            <td class="font-mono font-semibold">{{ row.booking_code }}</td>
            <td>
              {{ row.customer_name }}<br /><span class="font-mono text-xs text-muted">{{
                row.plate_number
              }}</span>
            </td>
            <td class="whitespace-nowrap">
              {{ row.booking_date }} <span class="text-muted">{{ row.slot_time }}</span>
            </td>
            <td>
              <template v-if="row.dp_amount > 0">
                <span class="flex flex-wrap items-center gap-x-2">
                  <span class="font-mono text-sm">{{ rupiah(row.dp_amount) }}</span>
                  <span
                    class="text-xs"
                    :class="
                      row.dp_paid_at ? 'text-forest' : row.dp_proof ? 'text-forest' : 'text-amber'
                    "
                    >{{
                      row.dp_paid_at
                        ? (row.dp_method === 'qris' ? 'QRIS' : 'Transfer') + ' · Lunas'
                        : row.dp_proof
                          ? 'Bukti masuk'
                          : 'Belum'
                    }}</span
                  >
                  <button
                    v-if="row.dp_proof"
                    type="button"
                    class="text-xs font-semibold text-forest underline"
                    @click="openProof(row)"
                  >
                    Bukti
                  </button>
                </span>
              </template>
              <span v-else class="text-xs text-muted">—</span>
            </td>
            <td><StatusBadge :status="row.status" /></td>
            <td>
              <div class="flex flex-wrap items-center gap-1">
                <button
                  v-if="['menunggu', 'dikonfirmasi'].includes(row.status) && dpUnpaid(row)"
                  class="service-action border-amber bg-white text-amber hover:bg-ivory"
                  :disabled="busy === row.id"
                  @click="action(row, 'dp')"
                >
                  {{ row.dp_proof ? 'Verifikasi DP' : 'Catat DP' }}
                </button>
                <button
                  v-if="row.status === 'menunggu'"
                  class="service-action border-forest bg-white text-forest hover:bg-forest-soft"
                  :disabled="busy === row.id || dpUnpaid(row)"
                  :title="dpUnpaid(row) ? 'Catat DP terlebih dahulu' : ''"
                  @click="action(row, 'confirm')"
                >
                  Konfirmasi
                </button>
                <button
                  v-if="row.status === 'menunggu'"
                  class="service-action border-red-300 bg-white text-red-800 hover:bg-red-50"
                  :disabled="busy === row.id"
                  @click="action(row, 'reject')"
                >
                  Tolak
                </button>
                <button
                  v-if="row.status === 'dikonfirmasi' && canCheckIn(row)"
                  class="service-action border-forest bg-forest text-white hover:bg-forest/90"
                  :disabled="busy === row.id"
                  @click="action(row, 'check-in')"
                >
                  Check-in
                </button>
                <button
                  v-if="row.status === 'dikonfirmasi' && canMarkNoShow(row)"
                  class="service-action border-line bg-white text-ink hover:bg-forest-soft"
                  :disabled="busy === row.id"
                  @click="action(row, 'no-show')"
                >
                  Tidak hadir
                </button>
                <button
                  v-if="['menunggu', 'dikonfirmasi'].includes(row.status)"
                  class="service-action border-line bg-white text-muted hover:bg-forest-soft"
                  :disabled="busy === row.id"
                  @click="action(row, 'cancel')"
                >
                  Batal
                </button>
                <span
                  v-if="row.status === 'dikonfirmasi' && !canCheckIn(row) && !canMarkNoShow(row)"
                  class="text-xs text-muted"
                  >Check-in pada {{ row.booking_date }}</span
                >
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <Teleport to="body">
      <div
        v-if="rejectRow"
        class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="reject-title"
      >
        <div class="w-full max-w-md rounded-service border border-line bg-white p-6 shadow-lg">
          <h2 id="reject-title" class="text-lg font-bold text-forest">
            Tolak booking {{ rejectRow.booking_code }}
          </h2>
          <p class="mt-1 text-sm text-muted">Catatan ini dikirim ke pelanggan lewat email.</p>
          <label class="mt-4 grid gap-1 text-sm font-semibold text-ink">
            Catatan penolakan
            <textarea
              v-model="rejectNote"
              class="service-input"
              rows="3"
              placeholder="Contoh: jadwal penuh, silakan pilih tanggal lain"
            ></textarea>
          </label>
          <div class="mt-4 flex flex-wrap justify-end gap-2">
            <button class="service-muted" type="button" :disabled="busy" @click="rejectRow = null">
              Batal
            </button>
            <button class="service-danger" type="button" :disabled="busy" @click="confirmReject">
              {{ busy ? 'Memproses…' : 'Tolak booking' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div
        v-if="dpRow"
        class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="dp-title"
      >
        <div
          class="w-full max-w-sm overflow-hidden rounded-service border border-line bg-white shadow-lg"
        >
          <div class="border-b border-line px-5 py-4">
            <p
              id="dp-title"
              class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-muted"
            >
              DP booking · {{ dpRow.booking_code }}
            </p>
            <p class="mt-1 font-mono text-3xl font-bold leading-none text-ink">
              {{ rupiah(dpRow.dp_amount) }}
            </p>
            <p class="mt-1 text-sm text-muted">
              {{ dpRow.customer_name }} · <span class="font-mono">{{ dpRow.plate_number }}</span>
            </p>
          </div>
          <div class="grid gap-4 p-5">
            <div
              class="grid gap-1 rounded-lg px-3 py-2 text-sm"
              :class="
                dpRow.dp_proof
                  ? 'border border-forest/30 bg-forest-soft text-forest'
                  : 'border border-amber/40 bg-ivory text-ink'
              "
            >
              <p class="font-semibold">
                {{
                  dpRow.dp_proof
                    ? 'Bukti dari pelanggan sudah masuk'
                    : 'Pelanggan belum mengirim bukti'
                }}
              </p>
              <p class="text-xs">
                {{
                  dpRow.dp_proof
                    ? 'Periksa mutasi rekening, lalu verifikasi DP.'
                    : 'Minta pelanggan mengunggah bukti di halaman status booking.'
                }}
              </p>
              <button
                v-if="dpRow.dp_proof"
                type="button"
                class="mt-1 w-fit text-xs font-semibold underline"
                @click="openProof(dpRow)"
              >
                Lihat bukti
              </button>
            </div>
            <fieldset class="grid gap-2">
              <legend
                class="mb-1 font-mono text-[10px] font-semibold uppercase tracking-wider text-muted"
              >
                Metode DP
              </legend>
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
                    name="dp-method"
                    class="sr-only"
                  />
                  {{ m[1] }}
                </label>
              </div>
            </fieldset>
            <label class="grid gap-1 text-sm font-semibold text-ink">
              {{ dpRow.dp_proof ? 'Ganti bukti (opsional)' : 'Bukti transfer / QRIS' }}
              <input
                type="file"
                accept="image/jpeg,image/png,image/webp,application/pdf"
                class="service-input py-1.5 text-sm file:mr-3 file:rounded file:border-0 file:bg-forest-soft file:px-2 file:py-1 file:text-xs file:font-semibold file:text-forest"
                @change="onProof"
              />
              <span class="text-xs font-normal text-muted"
                >JPG/PNG/WEBP atau PDF, maksimal 4 MB. Tanpa bukti, DP tidak bisa
                diverifikasi.</span
              >
            </label>
            <img
              v-if="dpPreview"
              :src="dpPreview"
              alt="Pratinjau bukti transfer"
              class="max-h-36 w-full rounded-lg border border-line bg-ivory object-contain"
            />
            <div class="grid gap-1 rounded-lg bg-ivory px-3 py-2 text-sm">
              <div class="flex items-baseline justify-between gap-2">
                <span class="text-muted">DP diterima</span>
                <span class="font-mono font-semibold text-ink">{{ rupiah(dpRow.dp_amount) }}</span>
              </div>
              <p class="text-xs text-muted">Dipotong dari tagihan saat check-in.</p>
            </div>
            <div class="flex gap-2">
              <button
                class="service-muted flex-1"
                type="button"
                :disabled="busy"
                @click="dpRow = null"
              >
                Batal
              </button>
              <button
                class="service-primary flex-1"
                type="button"
                :disabled="busy"
                @click="confirmDp"
              >
                {{
                  busy ? 'Memproses…' : dpRow.dp_proof || dpProof ? 'Verifikasi DP' : 'Tandai DP'
                }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <ConfirmModal
      :show="!!cancelRow"
      title="Batalkan booking"
      :message="
        cancelRow
          ? `Booking ${cancelRow.booking_code} atas nama ${cancelRow.customer_name} akan dibatalkan dan pelanggan diberi tahu lewat email.`
          : ''
      "
      :busy="!!busy"
      confirm-label="Batalkan booking"
      @cancel="cancelRow = null"
      @confirm="confirmCancel"
    />
  </section>
</template>
