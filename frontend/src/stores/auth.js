import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api/http'

export const useAuthStore = defineStore('auth', () => {
  const user    = ref(JSON.parse(localStorage.getItem('auth_user') || 'null'))
  const token   = ref(localStorage.getItem('auth_token') || '')
  const loading = ref(false)
  const error   = ref('')

  const isAuthenticated = computed(() => !!token.value && !!user.value)

  async function login(email, password) {
    loading.value = true
    error.value   = ''
    try {
      const { data } = await api.post('/auth/login', { email, password })
      token.value    = data.data.token
      user.value     = data.data.user
      localStorage.setItem('auth_token', data.data.token)
      localStorage.setItem('auth_user', JSON.stringify(data.data.user))
      return data
    } catch (err) {
      error.value = err.response?.data?.message || 'Login falhou'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function register(name, email, password, passwordConfirmation) {
    loading.value = true
    error.value   = ''
    try {
      const { data } = await api.post('/auth/register', {
        name,
        email,
        password,
        password_confirmation: passwordConfirmation,
      })
      token.value    = data.data.token
      user.value     = data.data.user
      localStorage.setItem('auth_token', data.data.token)
      localStorage.setItem('auth_user', JSON.stringify(data.data.user))
      return data
    } catch (err) {
      error.value = err.response?.data?.message || 'Registro falhou'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      await api.post('/auth/logout')
    } finally {
      user.value  = null
      token.value = ''
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
    }
  }

  async function fetchMe() {
    const { data } = await api.get('/auth/me')
    user.value     = data.data.user
    localStorage.setItem('auth_user', JSON.stringify(data.data.user))
    return data.data
  }

  return { user, token, loading, error, isAuthenticated, login, register, logout, fetchMe }
})
