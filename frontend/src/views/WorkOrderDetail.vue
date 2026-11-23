<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import client from '@/api/client'
import ConfirmModal from '@/components/ConfirmModal.vue'
import QrCode from '@/components/QrCode.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import StatusTimeline from '@/components/StatusTimeline.vue'
import { rupiah } from '@/utils/rupiah'

const route = useRoute()
const item = ref(null)
const mechanics = ref([])
const services = ref([])
const spareparts = ref([])
const error = ref('')
const loading = ref(false)
const mechanicId = ref('')
const serviceId = ref('')
const partId = ref('')
const qty = ref(1)
const discount = ref(0)
const method = ref('tunai')
const amount = ref(0)
const note = ref('')
const cancelConfirm = ref(false)
const copied = ref(false)
const id = () => route.params.id

const trackingUrl = computed(() =>
  item.value ? `${window.location.origin}/track/${item.value.tracking_code}` : ''
)
const timeline = computed(() =>
  (item.value?.status_logs || []).map((log) => ({ status: log.to_status, at: log.at }))
)
const whatsapp = computed(() => {
  const phone = item.value?.vehicle?.customer?.phone
  if (!phone) return ''
  const digits = phone.replace(/\D/g, '').replace(/^0/, '62')
  return `https://wa.me/${digits}?text=${encodeURIComponent(`Servis ${item.value.queue_number_display} selesai. Status: ${trackingUrl.value}`)}`
})

async function copyLink() {
  try {
    await navigator.clipboard.writeText(trackingUrl.value)
    copied.value = true
  } catch {
    error.value = 'Tautan gagal disalin. Salin manual dari kolom kode.'
  }
}

const steps = ['menunggu', 'dikerjakan', 'selesai', 'dibayar']
const stepIndex = computed(() => steps.indexOf(item.value?.status))
const servicesTotal = computed(() =>
  (item.value?.services || []).reduce((s, l) => s + Number(l.price), 0)
)
const partsTotal = computed(() =>
  (item.value?.parts || []).reduce((s, l) => s + Number(l.price) * l.qty, 0)
)
const subtotal = computed(() => servicesTotal.value + partsTotal.value)
const paid = computed(() => (item.value?.payments || []).reduce((s, p) => s + Number(p.amount), 0))
const total = computed(() => Number(item.value?.total ?? subtotal.value))
const remaining = computed(() => Math.max(total.value - paid.value, 0))
const methodLabel = {
  tunai: 'Tunai',
  transfer: 'Transfer',
  qris: 'QRIS',
  debit: 'Kartu debit',
  dp: 'DP booking'
}
const paidAt = computed(() => {
  const last = item.value?.payments?.at(-1)?.paid_at
  return last
    ? new Date(last).toLocaleString('id-ID', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
      })
    : ''
})

watch(
  remaining,
  (value) => {
    amount.value = value
  },
  { immediate: true }
)

async function load() {
  loading.value = true
  copied.value = false
  try {
    const [order, m, s, p] = await Promise.all([
      client.get(`/work-orders/${id()}`),
      client.get('/mechanics', { params: { per_page: 100 } }),
      client.get('/services', { params: { per_page: 100 } }),
      client.get('/spareparts', { params: { per_page: 100 } })
    ])
    item.value = order.data.data
    mechanics.value = m.data.data
    services.value = s.data.data
    spareparts.value = p.data.data
  } catch (e) {
    error.value = e.response?.data?.message || 'Data gagal dimuat.'
  } finally {
    loading.value = false
  }
}
async function act(url, data = {}, verb = 'post') {
  error.value = ''
  try {
    await client[verb](url, data)
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || 'Aksi ditolak.'
  }
}
function start() {
  return act(`/work-orders/${id()}/start`, { mechanic_id: mechanicId.value })
}
function addService() {
  return act(`/work-orders/${id()}/services`, { service_id: serviceId.value })
}
function addPart() {
  return act(`/work-orders/${id()}/parts`, { sparepart_id: partId.value, qty: qty.value })
}
function finish() {
  return act(`/work-orders/${id()}/finish`, { discount: discount.value })
}
function pay() {
  return act(`/work-orders/${id()}/pay`, { method: method.value, amount: amount.value })
}
function removeService(line) {
  return act(`/work-orders/${id()}/services/${line.service_id}`, {}, 'delete')
}
function updateQty(line, value) {
  const next = Number(value)
  if (!next || next < 1) {
    load()
    return
  }
  return act(`/work-orders/${id()}/parts/${line.sparepart_id}`, { qty: next }, 'patch')
}
function removePart(line) {
  return act(`/work-orders/${id()}/parts/${line.sparepart_id}`, {}, 'delete')
}
function cancelOrder() {
  cancelConfirm.value = false
  return act(`/work-orders/${id()}/cancel`)
}
function saveNote() {
  return act(`/work-orders/${id()}/public-note`, { public_note: note.value }, 'patch')
}
onMounted(load)
</script>

<template>
  <main class="grid gap-6">
    <header>
      <RouterLink to="/work-orders" class="text-sm text-muted no-underline"
        >← Papan antrian</RouterLink
      >
      <h1 class="mt-1 font-mono text-4xl font-bold text-forest">
        {{ item?.queue_number_display }}
      </h1>
      <p v-if="item" class="text-sm text-muted">
        {{ item.vehicle?.plate_number }} · {{ item.vehicle?.brand }} {{ item.vehicle?.model }} ·
        {{ item.vehicle?.customer?.name }}
      </p>
    </header>
    <div v-if="error" class="service-alert">{{ error }}</div>
    <div v-if="loading" class="text-muted">Memuat...</div>

    <template v-if="item">
      <div
        v-if="item.status === 'batal'"
        class="rounded-service border border-red-200 bg-red-50 p-4 text-sm text-red-800"
      >
        Order ini dibatalkan. Sparepart yang sudah terpakai dikembalikan ke stok.
      </div>
      <ol v-else class="flex flex-wrap items-center gap-2 text-sm">
        <li v-for="(step, i) in steps" :key="step" class="flex items-center gap-2">
          <span
            class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold"
            :class="
              i <= stepIndex ? 'bg-forest text-white' : 'border border-line bg-white text-muted'
            "
            >{{ i + 1 }}</span
          >
          <span
            class="capitalize"
            :class="i === stepIndex ? 'font-semibold text-forest' : 'text-muted'"
            >{{ step }}</span
          >
          <span v-if="i < steps.length - 1" class="mx-1 h-px w-6 bg-line" />
        </li>
      </ol>

      <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
        <div class="grid content-start gap-4">
          <section
            v-if="item.status === 'selesai'"
            class="overflow-hidden rounded-service border border-line bg-white shadow-sm"
            aria-labelledby="bayar-judul"
          >
            <div class="flex items-center justify-between gap-2 border-b border-line px-5 py-3">
              <h2
                id="bayar-judul"
                class="font-mono text-[11px] font-semibold uppercase tracking-[0.16em] text-muted"
              >
                Invoice {{ item.queue_number_display }}
              </h2>
              <StatusBadge status="selesai" />
            </div>
            <div class="grid gap-5 p-5 sm:grid-cols-[1.1fr_1fr] sm:gap-8">
              <div class="grid content-start gap-3">
                <div class="grid gap-0.5">
                  <p class="truncate text-sm font-semibold text-ink">
                    {{ item.vehicle?.customer?.name }}
                  </p>
                  <p class="font-mono text-xs text-muted">
                    {{ item.vehicle?.plate_number }} · {{ item.vehicle?.brand }}
                    {{ item.vehicle?.model }}
                  </p>
                </div>
                <div>
                  <p class="font-mono text-4xl font-bold leading-none text-ink">
                    {{ rupiah(paid > 0 ? remaining : item.total) }}
                  </p>
                  <p class="mt-1 text-xs text-muted">
                    {{
                      paid > 0 ? `Sisa setelah dibayar ${rupiah(paid)}` : 'Total jasa dan sparepart'
                    }}
                  </p>
                </div>
                <dl class="grid gap-1.5 text-sm" aria-label="Rincian harga">
                  <div class="flex items-baseline gap-2">
                    <dt class="text-muted">Jasa</dt>
                    <span
                      class="min-w-6 flex-1 border-b border-dotted border-line"
                      aria-hidden="true"
                    />
                    <dd class="font-mono">{{ rupiah(servicesTotal) }}</dd>
                  </div>
                  <div class="flex items-baseline gap-2">
                    <dt class="text-muted">Sparepart</dt>
                    <span
                      class="min-w-6 flex-1 border-b border-dotted border-line"
                      aria-hidden="true"
                    />
                    <dd class="font-mono">{{ rupiah(partsTotal) }}</dd>
                  </div>
                  <div v-if="paid > 0" class="flex items-baseline gap-2">
                    <dt class="text-muted">Sudah dibayar</dt>
                    <span
                      class="min-w-6 flex-1 border-b border-dotted border-line"
                      aria-hidden="true"
                    />
                    <dd class="font-mono">− {{ rupiah(paid) }}</dd>
                  </div>
                  <div v-if="item.discount > 0" class="flex items-baseline gap-2">
                    <dt class="text-muted">Diskon</dt>
                    <span
                      class="min-w-6 flex-1 border-b border-dotted border-line"
                      aria-hidden="true"
                    />
                    <dd class="font-mono">− {{ rupiah(item.discount) }}</dd>
                  </div>
                </dl>
                <ol
                  class="flex items-center gap-2 font-mono text-[11px] uppercase tracking-wide"
                  aria-label="Status pembayaran"
                >
                  <li class="flex items-center gap-1.5 font-semibold text-forest">
                    <span
                      class="grid h-4 w-4 place-items-center rounded-full bg-forest text-[9px] text-white"
                      >✓</span
                    >
                    Selesai
                  </li>
                  <span class="h-px flex-1 bg-line" aria-hidden="true" />
                  <li class="flex items-center gap-1.5 text-muted">
                    <span
                      class="h-4 w-4 rounded-full border border-line bg-white"
                      aria-hidden="true"
                    />
                    Dibayar
                  </li>
                </ol>
              </div>

              <div class="grid content-start gap-3">
                <label class="grid gap-1 text-sm font-semibold text-ink">
                  Metode
                  <select v-model="method" class="service-input">
                    <option value="tunai">Tunai</option>
                    <option value="transfer">Transfer</option>
                    <option value="qris">QRIS</option>
                  </select>
                </label>
                <label class="grid gap-1 text-sm font-semibold text-ink">
                  Uang diterima
                  <input
                    v-model.number="amount"
                    type="number"
                    min="0"
                    :placeholder="`Minimal ${rupiah(remaining)}`"
                    class="service-input font-mono"
                  />
                </label>
                <p v-if="amount < remaining" class="text-sm text-red-800">
                  Kurang {{ rupiah(remaining - amount) }}.
                </p>
                <p v-else-if="amount > remaining" class="text-sm text-forest">
                  Kembalian {{ rupiah(amount - remaining) }}.
                </p>
                <button class="service-primary w-full" :disabled="amount < remaining" @click="pay">
                  Bayar {{ rupiah(Math.max(amount, remaining)) }}
                </button>
              </div>
            </div>
          </section>

          <section
            v-if="item.status === 'dibayar'"
            class="overflow-hidden rounded-service border border-line bg-white shadow-sm"
          >
            <div class="flex items-center justify-between gap-2 border-b border-line px-5 py-3">
              <h2
                class="font-mono text-[11px] font-semibold uppercase tracking-[0.16em] text-muted"
              >
                Invoice {{ item.queue_number_display }}
              </h2>
              <span
                class="inline-flex items-center gap-1.5 rounded-full bg-forest-soft px-2.5 py-0.5 font-mono text-[11px] font-semibold text-forest"
              >
                <span class="h-1.5 w-1.5 rounded-full bg-forest" aria-hidden="true" /> Lunas
              </span>
            </div>
            <div class="grid gap-5 p-5 sm:grid-cols-[1fr_1.2fr] sm:gap-8">
              <div class="grid content-start gap-1">
                <p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-muted">
                  Total dibayar
                </p>
                <p class="font-mono text-4xl font-bold leading-none text-forest">
                  {{ rupiah(paid) }}
                </p>
                <p v-if="paidAt" class="text-xs text-muted">Lunas {{ paidAt }}</p>
                <p v-if="item.vehicle" class="mt-2 font-mono text-xs text-muted">
                  {{ item.vehicle.plate_number }} · {{ item.vehicle.customer?.name }}
                </p>
              </div>
              <div class="grid gap-2 text-sm">
                <div class="flex items-baseline gap-2">
                  <span class="text-muted">Jasa</span
                  ><span
                    class="min-w-6 flex-1 border-b border-dotted border-line"
                    aria-hidden="true"
                  /><span class="font-mono">{{ rupiah(servicesTotal) }}</span>
                </div>
                <div class="flex items-baseline gap-2">
                  <span class="text-muted">Sparepart</span
                  ><span
                    class="min-w-6 flex-1 border-b border-dotted border-line"
                    aria-hidden="true"
                  /><span class="font-mono">{{ rupiah(partsTotal) }}</span>
                </div>
                <div v-if="item.discount > 0" class="flex items-baseline gap-2">
                  <span class="text-muted">Diskon</span
                  ><span
                    class="min-w-6 flex-1 border-b border-dotted border-line"
                    aria-hidden="true"
                  /><span class="font-mono">− {{ rupiah(item.discount) }}</span>
                </div>
                <div class="mt-1 flex justify-between border-t border-line pt-2">
                  <span class="text-muted">Total tagihan</span
                  ><span class="font-mono font-semibold">{{ rupiah(item.total) }}</span>
                </div>
                <div v-for="p in item.payments" :key="p.id" class="flex justify-between">
                  <span class="text-muted">{{ methodLabel[p.method] || p.method }}</span><span
                    class="font-mono"
                    >{{ rupiah(p.amount) }}</span
                  >
                </div>
                <div
                  v-if="paid > item.total"
                  class="flex justify-between border-t border-line pt-2 text-forest"
                >
                  <span class="text-muted">Kembalian</span
                  ><span class="font-mono font-semibold">{{ rupiah(paid - item.total) }}</span>
                </div>
              </div>
            </div>
          </section>

          <section
            v-if="item.status === 'menunggu'"
            class="rounded-service border border-line bg-white p-5 shadow-sm"
          >
            <h2 class="mb-3 font-semibold text-ink">Mulai pengerjaan</h2>
            <div class="flex flex-wrap gap-2">
              <select v-model="mechanicId" class="service-input sm:w-64">
                <option value="">Pilih mekanik</option>
                <option v-for="m in mechanics" :key="m.id" :value="m.id">{{ m.name }}</option>
              </select>
              <button class="service-primary" :disabled="!mechanicId" @click="start">Mulai</button>
            </div>
          </section>

          <section
            v-if="item.status === 'dikerjakan'"
            class="grid gap-4 rounded-service border border-line bg-white p-5 shadow-sm"
          >
            <div>
              <h2 class="mb-2 font-semibold text-ink">Jasa</h2>
              <div class="flex flex-wrap gap-2">
                <select v-model="serviceId" class="service-input sm:w-72">
                  <option value="">Pilih jasa</option>
                  <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
                <button class="service-muted" :disabled="!serviceId" @click="addService">
                  Tambah
                </button>
              </div>
            </div>
            <div>
              <h2 class="mb-2 font-semibold text-ink">Sparepart</h2>
              <div class="flex flex-wrap gap-2">
                <select v-model="partId" class="service-input sm:w-72">
                  <option value="">Pilih sparepart</option>
                  <option v-for="p in spareparts" :key="p.id" :value="p.id">
                    {{ p.name }} (stok {{ p.stock }})
                  </option>
                </select>
                <input
                  v-model.number="qty"
                  type="number"
                  min="1"
                  class="service-input w-24"
                  aria-label="Jumlah"
                />
                <button class="service-muted" :disabled="!partId" @click="addPart">Tambah</button>
              </div>
            </div>
          </section>

          <section class="rounded-service border border-line bg-white p-5 shadow-sm">
            <h2 class="mb-2 font-semibold text-ink">Keluhan</h2>
            <p class="text-sm text-ink">{{ item.complaint || '—' }}</p>
            <h2 class="mb-2 mt-4 font-semibold text-ink">Catatan publik</h2>
            <textarea
              v-model="note"
              class="service-input mb-3"
              :placeholder="item.public_note || 'Tulis catatan untuk pelanggan'"
            ></textarea>
            <button class="service-muted" @click="saveNote">Simpan catatan</button>
          </section>

          <section
            v-if="timeline.length"
            class="rounded-service border border-line bg-white p-5 shadow-sm"
          >
            <h2 class="mb-3 font-semibold text-ink">Linimasa status</h2>
            <StatusTimeline :items="timeline" />
          </section>
        </div>

        <aside class="grid content-start gap-4">
          <section class="grid gap-3 rounded-service border border-line bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-ink">Pelacakan pelanggan</h2>
            <p class="text-sm text-muted">
              Pelanggan memindai QR atau membuka tautan untuk memantau status.
            </p>
            <div class="grid place-items-center rounded-lg bg-ivory p-3">
              <QrCode v-if="trackingUrl" :value="trackingUrl" />
            </div>
            <p class="break-all font-mono text-sm font-semibold text-forest">{{ trackingUrl }}</p>
            <div class="flex flex-wrap gap-2">
              <button class="service-muted" type="button" @click="copyLink">
                {{ copied ? 'Tersalin' : 'Salin tautan' }}
              </button>
              <a
                v-if="whatsapp && ['selesai', 'dibayar'].includes(item.status)"
                class="service-primary no-underline"
                :href="whatsapp"
                target="_blank"
                rel="noreferrer"
                >Kirim WhatsApp</a
              >
            </div>
          </section>

          <section class="rounded-service border border-line bg-white p-5 shadow-sm">
            <h2 class="mb-3 font-semibold text-ink">Ringkasan</h2>
            <div v-if="servicesTotal" class="grid gap-2">
              <p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-muted">
                Jasa
              </p>
              <ul class="grid gap-2 text-sm">
                <li
                  v-for="line in item.services || []"
                  :key="'s' + line.service_id"
                  class="flex items-start justify-between gap-2"
                >
                  <span>{{ line.name }}</span>
                  <span class="flex items-center gap-2 whitespace-nowrap">
                    <span>{{ rupiah(line.price) }}</span>
                    <button
                      v-if="item.status === 'dikerjakan'"
                      type="button"
                      class="text-muted hover:text-red-800"
                      :aria-label="`Hapus jasa ${line.name}`"
                      @click="removeService(line)"
                    >
                      ×
                    </button>
                  </span>
                </li>
              </ul>
            </div>
            <div v-if="partsTotal" class="mt-3 grid gap-2">
              <p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-muted">
                Sparepart
              </p>
              <ul class="grid gap-2 text-sm">
                <li
                  v-for="line in item.parts || []"
                  :key="'p' + line.sparepart_id"
                  class="flex items-start justify-between gap-2"
                >
                  <span class="flex flex-wrap items-center gap-1">
                    {{ line.name }}
                    <input
                      v-if="item.status === 'dikerjakan'"
                      :value="line.qty"
                      type="number"
                      min="1"
                      class="w-14 rounded border border-line bg-white px-1 py-0.5 font-mono text-xs"
                      :aria-label="`Jumlah ${line.name}`"
                      @change="updateQty(line, $event.target.value)"
                    />
                    <span v-else class="text-muted">× {{ line.qty }}</span>
                  </span>
                  <span class="flex items-center gap-2 whitespace-nowrap">
                    <span>{{ rupiah(line.price * line.qty) }}</span>
                    <button
                      v-if="item.status === 'dikerjakan'"
                      type="button"
                      class="text-muted hover:text-red-800"
                      :aria-label="`Hapus sparepart ${line.name}`"
                      @click="removePart(line)"
                    >
                      ×
                    </button>
                  </span>
                </li>
              </ul>
            </div>
            <p v-if="!subtotal" class="text-sm text-muted">Belum ada jasa atau sparepart.</p>
            <div class="mt-4 grid gap-1.5 border-t border-line pt-3 text-sm">
              <div v-if="servicesTotal" class="flex items-baseline gap-2">
                <span class="text-muted">Subtotal jasa</span
                ><span
                  class="min-w-6 flex-1 border-b border-dotted border-line"
                  aria-hidden="true"
                /><span class="font-mono">{{ rupiah(servicesTotal) }}</span>
              </div>
              <div v-if="partsTotal" class="flex items-baseline gap-2">
                <span class="text-muted">Subtotal sparepart</span
                ><span
                  class="min-w-6 flex-1 border-b border-dotted border-line"
                  aria-hidden="true"
                /><span class="font-mono">{{ rupiah(partsTotal) }}</span>
              </div>
              <div v-if="item.discount > 0" class="flex items-baseline gap-2">
                <span class="text-muted">Diskon</span
                ><span
                  class="min-w-6 flex-1 border-b border-dotted border-line"
                  aria-hidden="true"
                /><span class="font-mono">− {{ rupiah(item.discount) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-muted">Dibayar</span
                ><span class="font-mono">{{ rupiah(paid) }}</span>
              </div>
              <div
                v-if="item.total !== null && ['selesai', 'dibayar'].includes(item.status)"
                class="flex justify-between text-base font-bold text-forest"
              >
                <span>Total</span><span class="font-mono">{{ rupiah(item.total) }}</span>
              </div>
              <div
                v-if="['selesai', 'dibayar'].includes(item.status) && paid > item.total"
                class="flex justify-between text-forest"
              >
                <span>Kembalian</span><span class="font-mono">{{ rupiah(paid - item.total) }}</span>
              </div>
            </div>
          </section>

          <section
            v-if="item.status === 'dikerjakan'"
            class="grid gap-3 rounded-service border border-line bg-white p-5 shadow-sm"
          >
            <h2 class="font-semibold text-ink">Selesaikan</h2>
            <label class="grid gap-1 text-sm font-semibold text-ink">
              Diskon (opsional)
              <input
                v-model.number="discount"
                type="number"
                min="0"
                placeholder="0"
                class="service-input font-mono"
              />
            </label>
            <button class="service-primary" @click="finish">Tandai selesai</button>
          </section>

          <div class="flex flex-wrap gap-2">
            <button
              v-if="!['dibayar', 'batal'].includes(item.status)"
              class="service-danger"
              @click="cancelConfirm = true"
            >
              Batalkan
            </button>
            <RouterLink
              v-if="item.status !== 'batal'"
              class="service-muted no-underline"
              :to="`/work-orders/${item.id}/receipt`"
              >Struk</RouterLink
            >
          </div>
        </aside>
      </div>

      <ConfirmModal
        :show="cancelConfirm"
        title="Batalkan order"
        :message="`Order ${item.queue_number_display} akan dibatalkan. Sparepart yang sudah dipakai dikembalikan ke stok.`"
        confirm-label="Batalkan order"
        @cancel="cancelConfirm = false"
        @confirm="cancelOrder"
      />
    </template>
  </main>
</template>
