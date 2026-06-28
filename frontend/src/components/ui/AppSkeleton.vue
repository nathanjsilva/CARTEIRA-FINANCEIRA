<script setup>
defineProps({
  rounded: { type: String, default: 'rounded-md' },
})
</script>

<template>
  <div
    :class="['animate-shimmer', rounded]"
    aria-hidden="true"
  />
</template>
