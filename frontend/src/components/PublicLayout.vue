<script setup>
import { useRoute } from 'vue-router'

const route = useRoute()
const width = () => (route.meta.wide ? 'max-w-5xl' : 'max-w-3xl')
</script>

<template>
  <div class="flex min-h-screen flex-col bg-ivory">
    <header class="border-b border-line bg-white">
      <nav
        class="mx-auto flex flex-wrap items-center justify-between gap-3 px-4 py-3"
        :class="width()"
        aria-label="Navigasi publik"
      >
        <RouterLink class="font-bold text-forest no-underline" to="/">Bengkel Anda</RouterLink>
        <div class="flex items-center gap-4 text-sm font-semibold">
          <RouterLink class="text-ink no-underline hover:text-forest" to="/booking"
            >Booking servis</RouterLink
          >
          <RouterLink class="text-ink no-underline hover:text-forest" to="/track"
            >Lacak servis</RouterLink
          >
        </div>
      </nav>
    </header>
    <main class="mx-auto w-full flex-1 px-4 py-8" :class="width()"><slot /></main>
    <footer class="border-t border-line">
      <div
        class="mx-auto flex flex-wrap items-center justify-between gap-2 px-4 py-4 text-xs text-muted"
        :class="width()"
      >
        <p>Bengkel Anda · layanan servis mobil</p>
        <p class="font-mono">Senin–Sabtu · 08.00–16.00</p>
      </div>
    </footer>
  </div>
</template>
