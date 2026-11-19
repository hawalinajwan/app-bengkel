<script setup>
import MasterCrud from '@/components/MasterCrud.vue'
import { rupiah } from '@/utils/rupiah'

const columns = [
  { key: 'name', label: 'Nama' },
  { key: 'price', label: 'Harga' },
  { key: 'est_minutes', label: 'Estimasi (menit)' },
  { key: 'actions', label: 'Aksi' },
]

const fields = [
  { key: 'name', label: 'Nama', type: 'text', required: true },
  { key: 'price', label: 'Harga', type: 'number', required: true, default: 0 },
  { key: 'est_minutes', label: 'Estimasi (menit)', type: 'number', required: true, default: 30 },
]
</script>

<template>
  <MasterCrud title="Jasa Servis" endpoint="services" :columns="columns" :fields="fields">
    <template #price="{ row }">{{ rupiah(row.price) }}</template>
  </MasterCrud>
</template>
