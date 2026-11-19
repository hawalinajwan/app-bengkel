<script setup>
import MasterCrud from '@/components/MasterCrud.vue'

const columns = [
  { key: 'name', label: 'Nama' },
  { key: 'phone', label: 'Telepon' },
  { key: 'email', label: 'Email' },
  { key: 'address', label: 'Alamat' },
  { key: 'actions', label: 'Aksi' },
]

const fields = [
  { key: 'name', label: 'Nama', type: 'text', required: true },
  { key: 'phone', label: 'Telepon', type: 'text' },
  { key: 'email', label: 'Email', type: 'email' },
  { key: 'address', label: 'Alamat', type: 'textarea' },
]
</script>

<template>
  <MasterCrud title="Pelanggan" endpoint="customers" :columns="columns" :fields="fields" />
</template>
