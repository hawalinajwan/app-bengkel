<script setup>
import MasterCrud from '@/components/MasterCrud.vue'

const columns = [
  { key: 'name', label: 'Nama' },
  { key: 'phone', label: 'Telepon' },
  { key: 'is_active', label: 'Status' },
  { key: 'actions', label: 'Aksi' }
]

const fields = [
  { key: 'name', label: 'Nama', type: 'text', required: true },
  { key: 'phone', label: 'Telepon', type: 'text' },
  { key: 'is_active', label: 'Aktif', type: 'checkbox', default: true }
]
</script>

<template>
  <MasterCrud title="Mekanik" endpoint="mechanics" :columns="columns" :fields="fields">
    <template #is_active="{ row }">
      <span
        class="inline-flex rounded-full border px-2 py-1 text-xs font-semibold"
        :class="
          row.is_active
            ? 'border-forest/20 bg-forest-soft text-forest'
            : 'border-line bg-ivory text-muted'
        "
      >
        {{ row.is_active ? 'Aktif' : 'Nonaktif' }}
      </span>
    </template>
  </MasterCrud>
</template>
