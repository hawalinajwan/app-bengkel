<script setup>
import { onMounted, ref } from 'vue'
import client from '@/api/client'
import { rupiah } from '@/utils/rupiah'
const data = ref(null), loading = ref(true), error = ref('')
onMounted(async () => { try { data.value = (await client.get('/dashboard')).data.data } catch (e) { error.value = e.response?.data?.message || 'Dashboard gagal dimuat.' } finally { loading.value = false } })
</script>
<template>
  <main><h1 class="text-2xl font-bold text-forest mb-6">Dashboard</h1>
    <div v-if="loading">Memuat dashboard...</div><div v-else-if="error" class="service-alert">{{ error }}</div>
    <template v-else><div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4 mb-6"><div v-for="(count,status) in data.counts" :key="status" class="min-w-0"><div class="service-service-card h-full"><div class="service-card-body"><small class="text-muted text-capitalize">{{ status }}</small><div class="text-4xl font-bold text-forest">{{ count }}</div></div></div></div><div class="min-w-0 col-lg-3"><div class="service-service-card h-full bg-dark text-white"><div class="service-card-body"><small>Pendapatan dibayar</small><div class="h3">{{ rupiah(data.revenue) }}</div></div></div></div></div>
    <section><h2 class="text-lg font-bold text-forest">Stok menipis</h2><div v-if="!data.low_stock.length" class="text-muted">Semua stok aman.</div><div v-else class="overflow-x-auto"><service-table class="service-table"><thead><tr><th>Sparepart</th><th>Stok</th><th>Minimum</th></tr></thead><tbody><tr v-for="part in data.low_stock" :key="part.id"><td>{{ part.name }}</td><td>{{ part.stock }}</td><td>{{ part.min_stock }}</td></tr></tbody></service-table></div></section></template>
  </main>
</template>
