<script setup>
import { computed } from 'vue'

const props = defineProps({ status: { type: String, required: true } })

const labels = {
  menunggu: 'Menunggu',
  dikerjakan: 'Dikerjakan',
  selesai: 'Selesai',
  dibayar: 'Dibayar',
  batal: 'Batal',
  dikonfirmasi: 'Dikonfirmasi',
  ditolak: 'Ditolak',
  dibatalkan: 'Dibatalkan',
  check_in: 'Sudah check-in',
  tidak_hadir: 'Tidak hadir'
}

const tones = {
  menunggu: 'border-[#ecd9b0] bg-[#fbf1dd] text-[#7c5713]',
  dikerjakan: 'border-forest/20 bg-forest-soft text-forest',
  dikonfirmasi: 'border-forest/20 bg-forest-soft text-forest',
  check_in: 'border-forest/20 bg-forest-soft text-forest',
  selesai: 'border-forest bg-forest text-white',
  dibayar: 'border-forest bg-forest text-white',
  batal: 'border-red-200 bg-red-50 text-red-800',
  ditolak: 'border-red-200 bg-red-50 text-red-800',
  dibatalkan: 'border-red-200 bg-red-50 text-red-800',
  tidak_hadir: 'border-red-200 bg-red-50 text-red-800'
}

const label = computed(() => labels[props.status] || props.status)
const tone = computed(() => tones[props.status] || 'border-line bg-ivory text-muted')
</script>

<template>
  <span
    class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold"
    :class="tone"
    >{{ label }}</span
  >
</template>
