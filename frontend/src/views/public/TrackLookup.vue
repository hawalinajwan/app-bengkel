<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()
const code = ref(typeof route.query.code === 'string' ? route.query.code.toUpperCase() : '')

function submit() {
  const value = code.value.toUpperCase().replace(/\s+/g, '')
  if (value) router.push(`/track/${value}`)
}
</script>

<template>
  <section class="mx-auto grid max-w-md gap-6">
    <header>
      <p class="text-sm text-muted">Pelacakan servis</p>
      <h1 class="text-2xl font-bold text-forest">Masukkan kode pelacakan</h1>
      <p class="mt-1 text-sm text-muted">Kode ada di tiket atau QR yang diberikan bengkel.</p>
    </header>
    <form
      @submit.prevent="submit"
      class="grid gap-4 rounded-service border border-line bg-white p-5 shadow-sm"
    >
      <label class="grid gap-1 text-sm font-semibold text-ink">
        Kode pelacakan
        <input
          v-model="code"
          required
          class="service-input font-mono uppercase"
          autocomplete="off"
        />
      </label>
      <button class="service-primary">Lacak</button>
    </form>
  </section>
</template>
