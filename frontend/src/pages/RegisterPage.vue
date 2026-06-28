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

const name                 = ref('')
const email                = ref('')
const password             = ref('')
const passwordConfirmation = ref('')
const loading              = ref(false)
const error                = ref('')

async function onSubmit() {
  if (password.value !== passwordConfirmation.value) {
    error.value = 'As senhas não coincidem.'
    return
  }

  loading.value = true
  error.value   = ''
  try {
    await authStore.register(name.value, email.value, password.value, passwordConfirmation.value)
    router.push('/dashboard')
  } catch (err) {
    const errors = err.response?.data?.errors
    error.value  = errors
      ? Object.values(errors).flat().join(', ')
      : err.response?.data?.message || 'Falha no cadastro'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <AuthLayout>
    <AppFormCard title="Criar sua conta" description="Registre-se e comece a controlar seu dinheiro com transparência.">
      <form @submit.prevent="onSubmit" class="space-y-5" novalidate>
        <AppInput
          v-model="name"
          label="Nome completo"
          placeholder="Seu nome"
          :required="true"
          :disabled="loading"
        />

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
          placeholder="Mínimo 8 caracteres"
          :required="true"
          :disabled="loading"
          hint="Use letras, números e símbolos para mais segurança"
        />

        <AppInput
          v-model="passwordConfirmation"
          type="password"
          label="Confirmar senha"
          placeholder="Repita a senha"
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
          {{ loading ? 'Criando conta...' : 'Criar Conta' }}
        </AppButton>

        <p class="text-center text-sm text-zinc-500">
          Já possui conta?
          <RouterLink to="/login" class="text-brand font-semibold hover:text-[#f4b400]">
            Entrar
          </RouterLink>
        </p>
      </form>
    </AppFormCard>
  </AuthLayout>
</template>
