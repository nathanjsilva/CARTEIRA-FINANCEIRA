<script setup>
const props = defineProps({
  title: { type: String, required: true },
  message: { type: String, default: '' },
  icon: { type: String, default: 'list' },
})
</script>

<template>
  <div class="flex flex-col items-center justify-center gap-3 rounded-2xl border border-zinc-800 bg-zinc-900/90 px-6 py-12 text-center">
    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-zinc-800 text-zinc-500">
      <AppIcon :name="icon" :size="20" />
    </div>
    <h3 class="text-sm font-semibold text-zinc-100">{{ title }}</h3>
    <p class="max-w-xs text-sm text-zinc-500">{{ message }}</p>
  </div>
</template>
