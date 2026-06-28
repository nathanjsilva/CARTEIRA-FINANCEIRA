<script setup>
const props = defineProps({
  variant: { type: String, default: 'primary' },
  size: { type: String, default: 'md' },
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  full: { type: Boolean, default: false },
  type: { type: String, default: 'button' },
})

const emit = defineEmits(['click'])

const variants = {
  primary: 'bg-brand text-zinc-950 hover:bg-[#f5b600] focus-visible:ring-brand/40',
  secondary: 'bg-zinc-900 text-zinc-100 hover:bg-zinc-800 border border-zinc-700',
  ghost: 'bg-transparent text-zinc-300 hover:bg-zinc-900',
}

const sizes = {
  sm: 'text-xs px-3 py-2',
  md: 'text-sm px-4 py-3',
  lg: 'text-base px-5 py-3.5',
}
</script>

<template>
  <button
    :type="props.type"
    :disabled="props.disabled || props.loading"
    @click="emit('click')"
    :class="[
      'inline-flex items-center justify-center gap-2 rounded-2xl font-semibold transition duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/30 disabled:cursor-not-allowed disabled:opacity-60',
      variants[variant],
      sizes[size],
      full ? 'w-full' : '',
    ]"
  >
    <span v-if="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent"></span>
    <slot />
  </button>
</template>
