<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppButton from '@/components/ui/AppButton.vue'
import AppInput  from '@/components/ui/AppInput.vue'
import AppIcon   from '@/components/ui/AppIcon.vue'
import AppFormCard from '@/components/ui/AppFormCard.vue'
import AppAlert from '@/components/ui/AppAlert.vue'
import AuthLayout from '@/components/layout/AuthLayout.vue'

const router    = useRouter()
const authStore = useAuthStore()

const email    = ref('')
const password = ref('')
const loading  = ref(false)
const error    = ref('')

async function onSubmit() {
  loading.value = true
  error.value   = ''
  try {
    await authStore.login(email.value, password.value)
    router.push('/dashboard')
  } catch (err) {
    error.value = err.response?.data?.message || 'Credenciais inválidas'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <AuthLayout>
    <AppFormCard title="Acesse sua conta" description="Faça login para gerenciar suas finanças com segurança.">
      <form @submit.prevent="onSubmit" class="space-y-5" novalidate>
        <AppInput
          v-model="email"
          type="email"
          label="E-mail"
          placeholder="seu@email.com"
          :required="true"
          :disabled="loading"
        />

        <AppInput
          v-model="password"
          type="password"
          label="Senha"
          placeholder="Sua senha"
          :required="true"
          :disabled="loading"
        />

        <AppAlert v-if="error" variant="error" :message="error" />

        <AppButton
          type="submit"
          variant="primary"
          size="lg"
          :full="true"
          :loading="loading"
        >
          {{ loading ? 'Entrando...' : 'Entrar' }}
        </AppButton>

        <p class="text-center text-sm text-zinc-500">
          Ainda não tem uma conta?
          <RouterLink to="/register" class="text-brand font-semibold hover:text-[#f4b400]">
            Cadastre-se gratuitamente
          </RouterLink>
        </p>
      </form>
    </AppFormCard>
  </AuthLayout>
</template>
