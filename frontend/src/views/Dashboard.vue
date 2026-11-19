<script setup>
import { onMounted, ref } from 'vue'
import client from '@/api/client'
import { rupiah } from '@/utils/rupiah'
const data = ref(null), loading = ref(true), error = ref('')
onMounted(async () => { try { data.value = (await client.get('/dashboard')).data.data } catch (e) { error.value = e.response?.data?.message || 'Dashboard gagal dimuat.' } finally { loading.value = false } })
</script>
<template>
  <main><h1 class="h4 mb-4">Dashboard</h1>
    <div v-if="loading">Memuat dashboard...</div><div v-else-if="error" class="alert alert-danger">{{ error }}</div>
    <template v-else><div class="row g-3 mb-4"><div v-for="(count,status) in data.counts" :key="status" class="col-6 col-lg-3"><div class="card h-100"><div class="card-body"><small class="text-muted text-capitalize">{{ status }}</small><div class="display-6">{{ count }}</div></div></div></div><div class="col-12 col-lg-3"><div class="card h-100 bg-dark text-white"><div class="card-body"><small>Pendapatan dibayar</small><div class="h3">{{ rupiah(data.revenue) }}</div></div></div></div></div>
    <section><h2 class="h5">Stok menipis</h2><div v-if="!data.low_stock.length" class="text-muted">Semua stok aman.</div><div v-else class="table-responsive"><table class="table"><thead><tr><th>Sparepart</th><th>Stok</th><th>Minimum</th></tr></thead><tbody><tr v-for="part in data.low_stock" :key="part.id"><td>{{ part.name }}</td><td>{{ part.stock }}</td><td>{{ part.min_stock }}</td></tr></tbody></table></div></section></template>
  </main>
</template>
