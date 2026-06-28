<script setup>
import { useToast } from '@/composables/useToast'
import AppIcon from './AppIcon.vue'

const { toasts, dismiss } = useToast()

const config = {
  success: { icon: 'check',         iconClass: 'text-success', barClass: 'bg-success' },
  error:   { icon: 'alert-circle',  iconClass: 'text-error',   barClass: 'bg-error' },
  warning: { icon: 'alert-circle',  iconClass: 'text-warning',  barClass: 'bg-warning' },
  info:    { icon: 'info',          iconClass: 'text-info',    barClass: 'bg-info' },
}
</script>

<template>
  <Teleport to="body">
    <div
      class="fixed bottom-4 right-4 z-[100] flex flex-col gap-2 w-full max-w-sm pointer-events-none"
      aria-live="polite"
      aria-label="Notificações"
    >
      <TransitionGroup
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 translate-x-8 scale-95"
        leave-active-class="transition-all duration-200 ease-in"
        leave-to-class="opacity-0 translate-x-8 scale-95"
        move-class="transition-all duration-200"
      >
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="relative overflow-hidden pointer-events-auto bg-zinc-900 border border-zinc-800 rounded-xl shadow-2xl"
        >
          <div
            :class="['absolute left-0 top-0 bottom-0 w-1 rounded-l-xl', config[toast.type]?.barClass]"
          />

          <div class="flex items-start gap-3 pl-4 pr-3 py-3.5">
            <span :class="['mt-0.5 shrink-0', config[toast.type]?.iconClass]">
              <AppIcon :name="config[toast.type]?.icon" :size="16" :stroke-width="2" />
            </span>

            <p class="text-sm text-zinc-200 leading-relaxed flex-1">{{ toast.message }}</p>

            <button
              @click="dismiss(toast.id)"
              class="text-zinc-600 hover:text-zinc-400 transition-colors shrink-0 p-0.5"
              aria-label="Fechar notificação"
            >
              <AppIcon name="x" :size="14" :stroke-width="2.5" />
            </button>
          </div>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>
