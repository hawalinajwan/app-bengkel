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
  <div class="d-flex align-items-center justify-content-center min-vh-100 bg-body-tertiary">
    <div class="card shadow-sm" style="width: 24rem">
      <div class="card-body p-4">
        <h1 class="h4 mb-1">Antrian Bengkel</h1>
        <p class="text-secondary small mb-4">Masuk untuk mengelola antrian dan servis.</p>

        <div v-if="error" class="alert alert-danger py-2">{{ error }}</div>

        <form @submit.prevent="submit">
          <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input id="email" v-model="email" type="email" class="form-control" required autofocus />
          </div>
          <div class="mb-3">
            <label class="form-label" for="password">Kata sandi</label>
            <input id="password" v-model="password" type="password" class="form-control" required />
          </div>
          <button class="btn btn-primary w-100" type="submit" :disabled="loading">
            {{ loading ? 'Memproses…' : 'Masuk' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>
