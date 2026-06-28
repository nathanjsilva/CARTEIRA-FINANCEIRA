<script setup>
defineProps({
  variant: { type: String, default: 'default' },
  size:    { type: String, default: 'sm' },
})

const variants = {
  default:   'bg-zinc-800 text-zinc-300',
  success:   'bg-success/15 text-success',
  error:     'bg-error/15 text-error',
  warning:   'bg-warning/15 text-warning',
  info:      'bg-info/15 text-info',
  brand:     'bg-brand/15 text-brand',
  deposit:   'bg-success/15 text-success',
  withdrawal:'bg-error/15 text-error',
  transfer:  'bg-info/15 text-info',
  reversal:  'bg-brand/15 text-brand',
  completed: 'bg-success/15 text-success',
  pending:   'bg-warning/15 text-warning',
  failed:    'bg-error/15 text-error',
}

const sizes = {
  xs: 'text-[10px] px-1.5 py-0.5',
  sm: 'text-xs px-2 py-0.5',
  md: 'text-sm px-2.5 py-1',
}
</script>

<template>
  <span
    :class="[
      'inline-flex items-center font-medium rounded-full',
      variants[variant] ?? variants.default,
      sizes[size]  ?? sizes.sm,
    ]"
  >
    <slot />
  </span>
</template>
