<script setup>
const props = defineProps({
  title: { type: String, default: '' },
  message: { type: String, required: true },
  variant: { type: String, default: 'info' },
})

const variants = {
  info: { border: 'border-info/20', bg: 'bg-zinc-900', text: 'text-zinc-200' },
  success: { border: 'border-success/20', bg: 'bg-success/10', text: 'text-success' },
  error: { border: 'border-error/20', bg: 'bg-error/10', text: 'text-error' },
  warning: { border: 'border-warning/20', bg: 'bg-warning/10', text: 'text-warning' },
}
</script>

<template>
  <div :class="['rounded-2xl border p-4 text-sm', variants[variant].border, variants[variant].bg, variants[variant].text]">
    <strong v-if="title" class="block font-semibold mb-1">{{ title }}</strong>
    <p>{{ message }}</p>
  </div>
</template>
