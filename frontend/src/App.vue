<script setup>
import { computed } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import AppNavbar from '@/components/AppNavbar.vue'

const route = useRoute()
const showNavbar = computed(() => !route.meta.public)
</script>

<template>
  <AppNavbar v-if="showNavbar" />
  <main class="container-fluid py-4 px-3">
    <RouterView />
  </main>
</template>
