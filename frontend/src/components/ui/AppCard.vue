<script setup>
const props = defineProps({
  customClass: { type: String, default: '' },
})
</script>

<template>
  <div
    :class="[
      'rounded-[28px] border border-zinc-800/90 bg-zinc-950/95 shadow-[0_0_0_1px_rgba(255,255,255,0.04)] p-6 sm:p-7',
      props.customClass,
    ]"
  >
    <slot />
  </div>
</template>
