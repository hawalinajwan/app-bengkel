<script setup>
import { computed } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import AppNavbar from '@/components/AppNavbar.vue'
import PublicLayout from '@/components/PublicLayout.vue'

const route = useRoute()
const showNavbar = computed(() => !route.meta.public)
</script>

<template>
  <div class="min-h-screen">
    <AppNavbar v-if="showNavbar" />
    <PublicLayout v-if="route.meta.public && route.name !== 'login'"><RouterView /></PublicLayout>
    <main v-else class="px-4 py-6" :class="showNavbar ? 'lg:ml-64 lg:px-8' : ''">
      <RouterView />
    </main>
  </div>
</template>
