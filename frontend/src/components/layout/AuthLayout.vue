<script setup>
import AppIcon from '@/components/ui/AppIcon.vue'
</script>

<template>
  <div class="min-h-screen bg-zinc-950 text-zinc-100">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(255,193,7,0.14),_transparent_26%)] pointer-events-none"></div>
    <div class="relative min-h-screen flex items-center justify-center px-4 py-10">
      <div class="w-full max-w-md space-y-8">
        <div class="text-center">
          <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-3xl bg-zinc-900 border border-zinc-800 text-brand shadow-[0_18px_60px_rgba(255,193,7,0.08)]">
            <AppIcon name="wallet" size="24" stroke-width="2" />
          </div>
          <p class="text-sm font-medium uppercase tracking-[0.24em] text-zinc-500">Carteira Financeira</p>
          <h1 class="mt-4 text-3xl font-semibold text-zinc-100">Acessar sua conta</h1>
          <p class="mt-2 text-sm text-zinc-500">Gerencie seu dinheiro com segurança e simplicidade.</p>
        </div>

        <div class="rounded-[32px] border border-zinc-800/90 bg-zinc-950/95 p-6 shadow-[0_20px_80px_rgba(0,0,0,.30)]">
          <slot />
        </div>
      </div>
    </div>
  </div>
</template>
