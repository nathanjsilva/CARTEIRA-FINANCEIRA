<script setup>
import { watch } from 'vue'
import AppIcon from './AppIcon.vue'

const props = defineProps({
  show:  { type: Boolean, required: true },
  title: { type: String, default: '' },
  size:  { type: String, default: 'md' },
})

const emit = defineEmits(['close'])

watch(
  () => props.show,
  (val) => {
    document.body.style.overflow = val ? 'hidden' : ''
    if (val) {
      const onEsc = (e) => {
        if (e.key === 'Escape') {
          emit('close')
          document.removeEventListener('keydown', onEsc)
        }
      }
      document.addEventListener('keydown', onEsc)
    }
  },
)

const sizes = {
  sm: 'max-w-sm',
  md: 'max-w-md',
  lg: 'max-w-lg',
  xl: 'max-w-xl',
}
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200 ease-out"
      enter-from-class="opacity-0"
      leave-active-class="transition-opacity duration-150 ease-in"
      leave-to-class="opacity-0"
    >
      <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
      >
        <div
          class="absolute inset-0 bg-black/75 backdrop-blur-sm"
          @click="emit('close')"
        />

        <div
          :class="[
            'relative z-10 w-full bg-zinc-900 border border-zinc-800 rounded-2xl shadow-2xl animate-slide-up',
            sizes[size] ?? sizes.md,
          ]"
        >
          <div
            v-if="title || $slots.header"
            class="flex items-center justify-between px-5 py-4 border-b border-zinc-800"
          >
            <slot name="header">
              <h2 class="text-base font-semibold text-zinc-100">{{ title }}</h2>
            </slot>

            <button
              @click="emit('close')"
              class="p-1.5 text-zinc-500 hover:text-zinc-300 hover:bg-zinc-800 rounded-lg transition-colors"
              aria-label="Fechar"
            >
              <AppIcon name="x" :size="16" :stroke-width="2.5" />
            </button>
          </div>

          <div class="p-5">
            <slot />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
