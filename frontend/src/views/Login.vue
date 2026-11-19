<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

async function submit() {
  loading.value = true
  error.value = ''
  try {
    await auth.login(email.value, password.value)
    router.push(route.query.redirect || { name: 'dashboard' })
  } catch (e) {
    error.value = e.response?.data?.message || 'Gagal masuk. Periksa email dan kata sandi.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="grid min-h-screen place-items-center bg-ivory px-4 py-8">
    <section class="service-card w-full max-w-[430px] p-6 sm:p-9" aria-labelledby="login-title">
      <p class="mb-2 text-sm font-semibold uppercase tracking-wide text-muted">Ruang layanan bengkel</p>
      <h1 id="login-title" class="mb-2 text-2xl font-bold text-forest">Masuk ke Antrian Bengkel</h1>
      <p class="mb-6 text-sm leading-6 text-muted">Kelola antrian, servis, dan pelanggan dari satu tempat.</p>
      <div v-if="error" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800" role="alert">{{ error }}</div>
      <form class="space-y-4" @submit.prevent="submit">
        <div>
          <label class="mb-1 block text-sm font-semibold text-ink" for="email">Email</label>
          <input id="email" v-model="email" class="service-input" type="email" autocomplete="email" required autofocus />
        </div>
        <div>
          <label class="mb-1 block text-sm font-semibold text-ink" for="password">Kata sandi</label>
          <input id="password" v-model="password" class="service-input" type="password" autocomplete="current-password" required />
        </div>
        <button class="service-primary w-full" type="submit" :disabled="loading">{{ loading ? 'Memproses...' : 'Masuk' }}</button>
      </form>
    </section>
  </div>
</template>
