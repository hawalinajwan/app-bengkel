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
  { key: 'actions', label: 'Aksi' }
]

const fields = computed(() => [
  {
    key: 'customer_id',
    label: 'Pelanggan',
    type: 'select',
    required: true,
    options: customers.value.map((customer) => ({ value: customer.id, label: customer.name }))
  },
  { key: 'plate_number', label: 'Nomor Polisi', type: 'text', required: true },
  {
    key: 'type',
    label: 'Jenis',
    type: 'select',
    required: true,
    default: 'mobil',
    options: [{ value: 'mobil', label: 'Mobil' }]
  },
  { key: 'brand', label: 'Merek', type: 'text' },
  { key: 'model', label: 'Model', type: 'text' },
  { key: 'year', label: 'Tahun', type: 'number' }
])
</script>

<template>
  <MasterCrud title="Kendaraan" endpoint="vehicles" :columns="columns" :fields="fields">
    <template #customer="{ row }">{{ row.customer?.name }}</template>
    <template #row-actions="{ row }">
      <RouterLink
        class="min-h-11 mr-1 inline-flex items-center rounded-lg border border-line px-3 py-2 text-sm font-semibold text-forest hover:bg-forest-soft"
        :to="`/vehicles/${row.id}/history`"
        >Riwayat</RouterLink
      >
    </template>
  </MasterCrud>
</template>
