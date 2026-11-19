<script setup>
import { onMounted, ref } from 'vue'; import { useRoute } from 'vue-router'; import api from '@/api/client'; import ConfirmModal from '@/components/ConfirmModal.vue'; import StatusBadge from '@/components/StatusBadge.vue'
const route=useRoute(), data=ref(null), error=ref(''), confirm=ref(false), busy=ref(false)
async function load(){try{data.value=(await api.get(`/public/bookings/${route.params.code}`)).data.data}catch(e){error.value=e.response?.data?.message||'Booking tidak ditemukan.'}}
async function cancel(){busy.value=true;try{data.value=(await api.post(`/public/bookings/${route.params.code}/cancel`)).data.data;confirm.value=false}catch(e){error.value=e.response?.data?.message||'Pembatalan gagal.'}finally{busy.value=false}}
onMounted(load)
</script>
<template><section><h1>Status booking</h1><p v-if="error" class="text-red-800">{{error}}</p><article v-else-if="data" class="service-service-card p-4"><h2 class="text-lg font-bold text-forest">{{data.booking_code}}</h2><StatusBadge :status="data.status"/><p>{{data.booking_date}} pukul {{data.slot_time}}</p><p>{{data.brand}} {{data.model}} · {{data.plate_number}}</p><RouterLink v-if="data.tracking_code" :to="`/track/${data.tracking_code}`" class="service-primary">Lacak servis</RouterLink><button v-if="['menunggu','dikonfirmasi'].includes(data.status)" class="service-danger ms-2" @click="confirm=true">Batalkan</button></article><ConfirmModal :show="confirm" message="Batalkan booking ini?" :busy="busy" confirm-label="Batalkan" @confirm="cancel" @cancel="confirm=false"/></section></template>
