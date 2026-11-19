<script setup>
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
</script>

<template>
  <div>
    <h1 class="h4 mb-3">Halo, {{ auth.user?.name }}</h1>
    <p class="text-secondary">Dashboard akan diisi pada Fase 7.</p>
  </div>
</template>
