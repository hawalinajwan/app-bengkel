<script setup>
import { computed, onMounted, ref } from 'vue'
import client from '@/api/client'
import MasterCrud from '@/components/MasterCrud.vue'

const customers = ref([])

onMounted(async () => {
  try {
    const { data } = await client.get('/customers', { params: { per_page: 100 } })
    customers.value = data.data
  } catch {
    customers.value = []
  }
})

const columns = [
  { key: 'plate_number', label: 'Nomor Polisi' },
  { key: 'customer', label: 'Pelanggan' },
  { key: 'type', label: 'Jenis' },
  { key: 'brand', label: 'Merek' },
  { key: 'model', label: 'Model' },
  { key: 'year', label: 'Tahun' },
  { key: 'actions', label: 'Aksi' },
]

const fields = computed(() => [
  {
    key: 'customer_id',
    label: 'Pelanggan',
    type: 'select',
    required: true,
    options: customers.value.map((customer) => ({ value: customer.id, label: customer.name })),
  },
  { key: 'plate_number', label: 'Nomor Polisi', type: 'text', required: true },
  {
    key: 'type',
    label: 'Jenis',
    type: 'select',
    required: true,
    default: 'motor',
    options: [
      { value: 'motor', label: 'Motor' },
      { value: 'mobil', label: 'Mobil' },
    ],
  },
  { key: 'brand', label: 'Merek', type: 'text' },
  { key: 'model', label: 'Model', type: 'text' },
  { key: 'year', label: 'Tahun', type: 'number' },
])
</script>

<template>
  <MasterCrud title="Kendaraan" endpoint="vehicles" :columns="columns" :fields="fields">
    <template #customer="{ row }">{{ row.customer?.name }}</template>
  </MasterCrud>
</template>
