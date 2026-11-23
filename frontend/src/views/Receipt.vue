<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import client from '@/api/client'
import QrCode from '@/components/QrCode.vue'
import { rupiah } from '@/utils/rupiah'

const route = useRoute()
const item = ref(null)
const error = ref('')

const payments = computed(() => item.value?.payments || [])
const servicesTotal = computed(() =>
  (item.value?.services || []).reduce((sum, line) => sum + line.price, 0)
)
const partsTotal = computed(() =>
  (item.value?.parts || []).reduce((sum, line) => sum + line.price * line.qty, 0)
)
const paid = computed(() => payments.value.reduce((sum, p) => sum + p.amount, 0))
const remaining = computed(() => Math.max((item.value?.total || 0) - paid.value, 0))

const methods = {
  tunai: 'Tunai',
  transfer: 'Transfer',
  qris: 'QRIS',
  debit: 'Kartu debit',
  dp: 'DP booking'
}
function methodLabel(method) {
  return methods[method] || method
}

const trackingUrl = () => `${window.location.origin}/track/${item.value?.tracking_code || ''}`
const whatsapp = () =>
  item.value?.vehicle?.customer?.phone
    ? `https://wa.me/${item.value.vehicle.customer.phone.replace(/^0/, '62').replace(/\D/g, '')}?text=${encodeURIComponent(`Pesanan ${item.value.queue_number_display} siap dilihat: ${trackingUrl()}`)}`
    : ''

onMounted(async () => {
  try {
    item.value = (await client.get(`/work-orders/${route.params.id}`)).data.data
  } catch (e) {
    error.value = e.response?.data?.message || 'Struk gagal dimuat.'
  }
})

function print() {
  window.print()
}
</script>

<template>
  <main class="mx-auto grid max-w-md gap-6 print:block print:max-w-md">
    <p
      v-if="error"
      class="rounded-service border border-red-200 bg-red-50 p-4 text-sm text-red-800"
    >
      {{ error }}
    </p>

    <article
      v-if="item"
      class="rounded-service border border-line bg-white p-6 shadow-sm print:rounded-none print:border-0 print:shadow-none"
    >
      <header class="border-b border-dashed border-line pb-4 text-center">
        <h1 class="text-lg font-bold text-forest">Struk Bengkel</h1>
        <p class="font-mono text-3xl font-bold text-ink">{{ item.queue_number_display }}</p>
        <p class="text-sm text-muted">{{ item.queue_date }}</p>
      </header>

      <dl class="grid gap-2 border-b border-dashed border-line py-4 text-sm">
        <div class="flex justify-between gap-4">
          <dt class="text-muted">Pelanggan</dt>
          <dd>{{ item.vehicle?.customer?.name }}</dd>
        </div>
        <div class="flex justify-between gap-4">
          <dt class="text-muted">Kendaraan</dt>
          <dd class="font-mono">{{ item.vehicle?.plate_number }}</dd>
        </div>
      </dl>

      <section v-if="item.services?.length" class="border-b border-dashed border-line py-3 text-sm">
        <h2 class="font-mono text-[10px] font-semibold uppercase tracking-wider text-muted">
          Jasa
        </h2>
        <dl class="mt-2 grid gap-1">
          <div
            v-for="line in item.services"
            :key="'s' + line.service_id"
            class="flex items-baseline gap-2"
          >
            <dt>{{ line.name }}</dt>
            <span class="min-w-4 flex-1 border-b border-dotted border-line" aria-hidden="true" />
            <dd class="font-mono">{{ rupiah(line.price) }}</dd>
          </div>
        </dl>
      </section>

      <section v-if="item.parts?.length" class="border-b border-dashed border-line py-3 text-sm">
        <h2 class="font-mono text-[10px] font-semibold uppercase tracking-wider text-muted">
          Sparepart
        </h2>
        <dl class="mt-2 grid gap-1">
          <div
            v-for="line in item.parts"
            :key="'p' + line.sparepart_id"
            class="flex items-baseline gap-2"
          >
            <dt>{{ line.name }} × {{ line.qty }}</dt>
            <span class="min-w-4 flex-1 border-b border-dotted border-line" aria-hidden="true" />
            <dd class="font-mono">{{ rupiah(line.price * line.qty) }}</dd>
          </div>
        </dl>
      </section>

      <dl class="grid gap-1.5 border-b border-dashed border-line py-3 text-sm">
        <div class="flex items-baseline gap-2">
          <dt class="text-muted">Subtotal jasa</dt>
          <span class="min-w-4 flex-1 border-b border-dotted border-line" aria-hidden="true" />
          <dd class="font-mono">{{ rupiah(servicesTotal) }}</dd>
        </div>
        <div class="flex items-baseline gap-2">
          <dt class="text-muted">Subtotal sparepart</dt>
          <span class="min-w-4 flex-1 border-b border-dotted border-line" aria-hidden="true" />
          <dd class="font-mono">{{ rupiah(partsTotal) }}</dd>
        </div>
        <div v-if="item.discount > 0" class="flex items-baseline gap-2">
          <dt class="text-muted">Diskon</dt>
          <span class="min-w-4 flex-1 border-b border-dotted border-line" aria-hidden="true" />
          <dd class="font-mono">− {{ rupiah(item.discount) }}</dd>
        </div>
        <div
          class="mt-1 flex justify-between border-t border-line pt-2 text-base font-bold text-forest"
        >
          <dt>Total</dt>
          <dd class="font-mono">{{ rupiah(item.total || 0) }}</dd>
        </div>
      </dl>

      <dl
        v-if="item.total !== null"
        class="grid gap-1 border-t border-dashed border-line py-3 text-sm"
      >
        <div v-if="!payments.length" class="flex justify-between gap-4 text-red-800">
          <dt>Belum dibayar</dt>
          <dd class="font-mono">{{ rupiah(remaining) }}</dd>
        </div>
        <div v-for="p in payments" :key="p.id" class="flex justify-between gap-4">
          <dt class="text-muted">Dibayar · {{ methodLabel(p.method) }}</dt>
          <dd class="font-mono">{{ rupiah(p.amount) }}</dd>
        </div>
        <div
          v-if="payments.length && remaining > 0"
          class="flex justify-between gap-4 text-red-800"
        >
          <dt>Sisa tagihan</dt>
          <dd class="font-mono">{{ rupiah(remaining) }}</dd>
        </div>
        <div
          v-if="paid > (item.total || 0) && item.total > 0"
          class="flex justify-between gap-4 text-forest"
        >
          <dt>Kembalian</dt>
          <dd class="font-mono">{{ rupiah(paid - item.total) }}</dd>
        </div>
      </dl>

      <div class="grid place-items-center gap-2 border-t border-dashed border-line pt-4">
        <QrCode :value="trackingUrl()" />
        <p class="font-mono text-xs text-muted">{{ item.tracking_code }}</p>
      </div>

      <div class="mt-5 flex flex-wrap gap-2 print:hidden">
        <a
          v-if="whatsapp()"
          class="service-primary no-underline"
          :href="whatsapp()"
          target="_blank"
          rel="noreferrer"
          >WhatsApp</a
        >
        <button class="service-muted" type="button" @click="print">Cetak</button>
      </div>
    </article>
  </main>
</template>
