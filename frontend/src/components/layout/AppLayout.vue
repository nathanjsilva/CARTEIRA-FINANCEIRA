<script setup>
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppIcon from '@/components/ui/AppIcon.vue'
import AppButton from '@/components/ui/AppButton.vue'

const router    = useRouter()
const route     = useRoute()
const authStore = useAuthStore()

const navItems = [
  { name: 'Dashboard',   to: '/dashboard',    icon: 'home' },
  { name: 'Transações',  to: '/transactions', icon: 'list' },
]

const isActive = (path) => route.path === path

const userInitials = computed(() => {
  const name = authStore.user?.name ?? ''
  return name.split(' ').slice(0, 2).map((n) => n[0]).join('').toUpperCase()
})

async function handleLogout() {
  await authStore.logout()
  router.push('/login')
}
</script>

<template>
  <div class="min-h-screen bg-zinc-950 text-zinc-100">
    <header class="sticky top-0 z-40 border-b border-zinc-800 bg-zinc-950/95 backdrop-blur-sm shadow-[0_1px_0_rgba(255,255,255,0.03)]">
      <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div class="flex items-center gap-3">
          <div class="flex h-12 w-12 items-center justify-center rounded-3xl border border-zinc-800 bg-zinc-900 text-brand shadow-[0_8px_20px_rgba(255,193,7,0.08)]">
            <AppIcon name="wallet" size="20" stroke-width="2" />
          </div>
          <div>
            <p class="text-xs uppercase tracking-[0.28em] text-zinc-500">Carteira Financeira</p>
            <p class="text-sm font-semibold text-zinc-100">Controle financeiro inteligente</p>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <nav class="flex flex-wrap gap-2" aria-label="Navegação principal">
            <RouterLink
              v-for="item in navItems"
              :key="item.to"
              :to="item.to"
              :class="[
                'inline-flex items-center gap-2 rounded-2xl border px-4 py-2 text-sm font-medium transition',
                isActive(item.to)
                  ? 'border-zinc-700 bg-zinc-900 text-zinc-100'
                  : 'border-zinc-800 bg-zinc-950 text-zinc-400 hover:border-zinc-700 hover:bg-zinc-900 hover:text-zinc-100',
              ]"
            >
              <AppIcon :name="item.icon" :size="14" />
              <span>{{ item.name }}</span>
            </RouterLink>
          </nav>

          <span class="hidden sm:inline-flex items-center gap-2 rounded-2xl border border-zinc-800 bg-zinc-900 px-3 py-2 text-sm text-zinc-400">
            <span class="flex h-9 w-9 items-center justify-center rounded-2xl bg-brand/10 text-brand">{{ userInitials }}</span>
            <span class="truncate max-w-[12rem]">{{ authStore.user?.name }}</span>
          </span>

          <AppButton variant="ghost" size="sm" @click="handleLogout" class="text-zinc-300 hover:text-zinc-100">
            <AppIcon name="log-out" :size="15" />
            <span class="hidden sm:inline">Sair</span>
          </AppButton>
        </div>
      </div>
    </header>

    <main class="flex-1 w-full px-4 py-6 sm:px-6 lg:px-8">
      <div class="mx-auto w-full max-w-7xl">
        <slot />
      </div>
    </main>

    <footer class="border-t border-zinc-900 bg-zinc-950/95 py-4 text-center text-xs text-zinc-500">
      <p>&copy; {{ new Date().getFullYear() }} Carteira Financeira. Todos os direitos reservados.</p>
    </footer>
  </div>
</template>
