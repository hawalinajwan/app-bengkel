<script setup>
import { ref } from 'vue'
import client from '@/api/client'
const date = ref(new Date().toISOString().slice(0, 10)), report = ref(null), error = ref(''), loading = ref(false)
async function load() { loading.value = true; error.value = ''; try { report.value = (await client.get('/reports/daily', { params: { date: date.value } })).data.data } catch (e) { error.value = e.response?.data?.message || 'Laporan gagal dimuat.' } finally { loading.value = false } }
load()
</script>
<template><main><h1 class="text-2xl font-bold text-forest">Laporan harian</h1><form class="flex flex-wrap gap-2 mb-6" @submit.prevent="load"><input v-model="date" type="date" class="service-input" required><button class="service-primary" :disabled="loading">Muat</button></form><div v-if="error" class="service-alert">{{ error }}</div><div v-if="report" class="service-service-card p-4"><p>Pendapatan: Rp {{ Number(report.revenue).toLocaleString('id-ID') }}</p><ul><li v-for="(count, status) in report.counts" :key="status">{{ status }}: {{ count }}</li></ul></div></main></template>
