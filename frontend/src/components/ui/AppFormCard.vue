<script setup>
const props = defineProps({
  title: { type: String, default: '' },
  description: { type: String, default: '' },
})
</script>

<template>
  <div class="rounded-[32px] border border-zinc-800 bg-zinc-950/95 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.20)]">
    <div v-if="props.title" class="mb-6">
      <h2 class="text-xl font-semibold text-zinc-100">{{ props.title }}</h2>
      <p v-if="props.description" class="text-sm text-zinc-500 mt-2">{{ props.description }}</p>
    </div>
    <slot />
  </div>
</template>
