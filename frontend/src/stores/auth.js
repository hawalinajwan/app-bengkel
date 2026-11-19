import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import client from '@/api/client'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('token'))
  const user = ref(null)
  const isLoggedIn = computed(() => Boolean(token.value))

  function setToken(value) {
    token.value = value
    if (value) localStorage.setItem('token', value)
    else localStorage.removeItem('token')
  }

  async function login(email, password) {
    const { data } = await client.post('/auth/login', { email, password })
    setToken(data.token)
    user.value = data.user
    return data.user
  }

  async function fetchMe() {
    if (!token.value) return null
    const { data } = await client.get('/auth/me')
    user.value = data.data
    return user.value
  }

  async function logout() {
    try {
      await client.post('/auth/logout')
    } catch {
      // token sudah tidak berlaku, tetap bersihkan penyimpanan lokal
    }
    setToken(null)
    user.value = null
  }

  return { token, user, isLoggedIn, login, fetchMe, logout }
})
