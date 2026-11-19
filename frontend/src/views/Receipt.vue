<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import client from '@/api/client'
import QrCode from '@/components/QrCode.vue'
const item = ref(null), route = useRoute()
const trackingUrl = () => `${window.location.origin}/track/${item.value?.tracking_code || ''}`
const whatsapp = () => item.value?.vehicle?.customer?.phone ? `https://wa.me/${item.value.vehicle.customer.phone.replace(/^0/, '62').replace(/\D/g, '')}?text=${encodeURIComponent(`Pesanan ${item.value.queue_number_display} siap dilihat: ${trackingUrl()}`)}` : ''
onMounted(async () => { item.value = (await client.get(`/work-orders/${route.params.id}`)).data.data })
</script>
<template><main class="receipt"><h1>Struk Bengkel</h1><div v-if="item"><p>Antrian: {{ item.queue_number_display }}<br>Total: Rp {{ Number(item.total || 0).toLocaleString('id-ID') }}</p><QrCode :value="trackingUrl()" /><div class="mt-3"><a v-if="whatsapp()" class="btn btn-success me-2" :href="whatsapp()" target="_blank" rel="noreferrer">WhatsApp</a><button class="btn btn-primary" @click="window.print()">Cetak</button></div></div></main></template>
<style>@media print{nav,button,a{display:none!important}.receipt{margin:2cm}}</style>
