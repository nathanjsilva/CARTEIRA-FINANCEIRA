<script setup>
import { ref } from 'vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import AppSkeleton from '@/components/ui/AppSkeleton.vue'
import AppBadge from '@/components/ui/AppBadge.vue'

defineProps({
  balance:  { type: Number, required: true },
  currency: { type: String, default: 'BRL' },
  loading:  { type: Boolean, default: false },
  formatted: { type: String, default: '' },
})

const balanceVisible = ref(true)
</script>

<template>
  <div class="relative overflow-hidden rounded-[28px] border border-zinc-800 bg-zinc-950/95 p-6 sm:p-7">
    <div
      class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-gradient-to-br from-[#ffc107]/20 to-transparent opacity-70"
      aria-hidden="true"
    />
    <div
      class="pointer-events-none absolute -right-4 -bottom-4 h-24 w-24 rounded-full bg-gradient-to-br from-[#ffc107]/10 to-transparent opacity-70"
      aria-hidden="true"
    />

    <div class="relative">
      <div class="flex items-start justify-between gap-6">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.32em] text-zinc-500 mb-3">Saldo disponível</p>

          <template v-if="loading">
            <AppSkeleton class="h-10 w-48 mb-2" rounded="rounded-xl" />
            <AppSkeleton class="h-3 w-24 rounded-full" />
          </template>

          <template v-else>
            <div class="flex items-center gap-3">
              <h2 class="text-3xl sm:text-4xl font-semibold text-zinc-100 tracking-tight tabular-nums">
                {{ balanceVisible ? formatted : '•••' }}
              </h2>
            </div>

            <AppBadge variant="brand" size="xs" class="mt-3 inline-flex">{{ currency }}</AppBadge>
          </template>
        </div>

        <button
          @click="balanceVisible = !balanceVisible"
          class="rounded-2xl border border-zinc-800 bg-zinc-900 p-3 text-zinc-400 transition hover:border-zinc-700 hover:text-zinc-200"
          :aria-label="balanceVisible ? 'Ocultar saldo' : 'Mostrar saldo'"
        >
          <AppIcon :name="balanceVisible ? 'eye' : 'eye-off'" :size="16" />
        </button>
      </div>
    </div>
  </div>
</template>
