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
  } finally { loading.value = false }
}
</script>

<template>
  <div class="login-page">
    <section class="card login-card" aria-labelledby="login-title">
      <div class="card-body p-4 p-sm-5">
        <p class="small fw-semibold text-uppercase text-muted mb-2">Ruang layanan bengkel</p>
        <h1 id="login-title" class="h3 mb-2">Masuk ke Antrian Bengkel</h1>
        <p class="text-muted mb-4">Kelola antrian, servis, dan pelanggan dari satu tempat.</p>
        <div v-if="error" class="alert alert-danger" role="alert">{{ error }}</div>
        <form @submit.prevent="submit">
          <div class="mb-3"><label class="form-label" for="email">Email</label><input id="email" v-model="email" type="email" class="form-control" autocomplete="email" required autofocus /></div>
          <div class="mb-4"><label class="form-label" for="password">Kata sandi</label><input id="password" v-model="password" type="password" class="form-control" autocomplete="current-password" required /></div>
          <button class="btn btn-primary w-100" type="submit" :disabled="loading">{{ loading ? 'Memeriksa...' : 'Masuk' }}</button>
        </form>
      </div>
    </section>
  </div>
</template>
