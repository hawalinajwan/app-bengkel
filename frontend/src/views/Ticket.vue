<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import client from '@/api/client'
import QrCode from '@/components/QrCode.vue'

const route = useRoute()
const item = ref(null)
const code = ref(route.query.code || '')
const error = ref('')
const trackUrl = computed(() => `${window.location.origin}/track/${code.value}`)

onMounted(async () => {
  try {
    item.value = (await client.get(`/work-orders/${route.params.id}`)).data.data
    code.value = item.value.tracking_code
  } catch (e) {
    error.value = e.response?.data?.message || 'Tiket gagal dimuat.'
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
      class="overflow-hidden rounded-service border border-line bg-white shadow-sm print:rounded-none print:shadow-none"
    >
      <div class="bg-forest px-5 py-5 text-white">
        <p class="text-xs uppercase tracking-wide text-white/70">Nomor antrian</p>
        <p class="font-mono text-6xl font-bold leading-none">
          {{ item?.queue_number_display || '—' }}
        </p>
        <p v-if="item" class="mt-2 text-sm text-white/80">
          {{ item.queue_date }} · {{ item.vehicle?.plate_number }}
        </p>
      </div>
      <div class="grid gap-4 border-b border-dashed border-line px-5 py-5">
        <div v-if="item" class="text-sm">
          <p class="font-semibold text-ink">{{ item.vehicle?.brand }} {{ item.vehicle?.model }}</p>
          <p class="text-muted">{{ item.vehicle?.customer?.name }}</p>
        </div>
        <div>
          <p class="mb-2 text-xs uppercase tracking-wide text-muted">Kode pelacakan</p>
          <p class="font-mono text-lg font-semibold text-forest">{{ code }}</p>
        </div>
        <QrCode :value="trackUrl" />
      </div>
      <div class="flex flex-wrap gap-3 px-5 py-4 print:hidden">
        <button class="service-primary" type="button" @click="print">Cetak tiket</button>
        <RouterLink class="text-sm text-muted no-underline self-center" to="/work-orders"
          >Kembali ke papan antrian</RouterLink
        >
      </div>
    </article>
  </main>
</template>
