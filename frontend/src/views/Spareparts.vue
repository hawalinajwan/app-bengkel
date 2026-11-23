<script setup>
import MasterCrud from '@/components/MasterCrud.vue'
import { rupiah } from '@/utils/rupiah'

const columns = [
  { key: 'sku', label: 'SKU' },
  { key: 'name', label: 'Nama' },
  { key: 'price', label: 'Harga' },
  { key: 'stock', label: 'Stok' },
  { key: 'min_stock', label: 'Stok Minimum' },
  { key: 'low_stock', label: 'Status' },
  { key: 'actions', label: 'Aksi' }
]

const fields = [
  { key: 'sku', label: 'SKU', type: 'text', required: true },
  { key: 'name', label: 'Nama', type: 'text', required: true },
  { key: 'price', label: 'Harga', type: 'number', required: true, default: 0 },
  { key: 'stock', label: 'Stok', type: 'number', required: true, default: 0 },
  { key: 'min_stock', label: 'Stok Minimum', type: 'number', required: true, default: 0 }
]
</script>

<template>
  <MasterCrud title="Sparepart" endpoint="spareparts" :columns="columns" :fields="fields">
    <template #price="{ row }">{{ rupiah(row.price) }}</template>
    <template #low_stock="{ row }">
      <span
        class="inline-flex rounded-full border px-2 py-1 text-xs font-semibold"
        :class="
          row.stock === 0
            ? 'border-red-200 bg-red-50 text-red-800'
            : row.low_stock
              ? 'border-[#ecd9b0] bg-[#fbf1dd] text-[#7c5713]'
              : 'border-forest/20 bg-forest-soft text-forest'
        "
      >
        {{ row.stock === 0 ? 'Habis' : row.low_stock ? 'Menipis' : 'Aman' }}
      </span>
    </template>
  </MasterCrud>
</template>
