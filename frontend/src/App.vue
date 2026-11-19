<script setup>
import { computed } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import AppNavbar from '@/components/AppNavbar.vue'
import PublicLayout from '@/components/PublicLayout.vue'

const route = useRoute()
const showNavbar = computed(() => !route.meta.public)
</script>

<template>
  <AppNavbar v-if="showNavbar" />
  <PublicLayout v-if="route.meta.public && route.name !== 'login'"><RouterView /></PublicLayout>
  <main v-else class="container-fluid py-4 px-3"><RouterView /></main>
</template>
