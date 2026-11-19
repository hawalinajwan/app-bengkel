<script setup>
import MasterCrud from '@/components/MasterCrud.vue'

const columns = [
  { key: 'name', label: 'Nama' },
  { key: 'phone', label: 'Telepon' },
  { key: 'is_active', label: 'Status' },
  { key: 'actions', label: 'Aksi' },
]

const fields = [
  { key: 'name', label: 'Nama', type: 'text', required: true },
  { key: 'phone', label: 'Telepon', type: 'text' },
  { key: 'is_active', label: 'Aktif', type: 'checkbox', default: true },
]
</script>

<template>
  <MasterCrud title="Mekanik" endpoint="mechanics" :columns="columns" :fields="fields">
    <template #is_active="{ row }">
      <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold" :class="row.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
        {{ row.is_active ? 'Aktif' : 'Nonaktif' }}
      </span>
    </template>
  </MasterCrud>
</template>
