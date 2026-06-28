<script setup>
const props = defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  actions: { type: Array, default: () => [] },
})
</script>

<template>
  <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
    <div class="min-w-0">
      <h1 class="text-xl sm:text-2xl font-semibold text-zinc-100 leading-tight truncate">{{ title }}</h1>
      <p v-if="subtitle" class="text-sm text-zinc-500 mt-1 truncate">{{ subtitle }}</p>
    </div>

    <div v-if="actions.length" class="flex flex-wrap items-center gap-2">
      <slot name="actions">
        <button
          v-for="action in actions"
          :key="action.label"
          :type="action.type || 'button'"
          @click="action.onClick?.()"
          class="rounded-lg border border-zinc-800 bg-zinc-900 px-4 py-2 text-sm font-medium text-zinc-200 transition hover:border-zinc-700 hover:bg-zinc-800"
        >
          {{ action.label }}
        </button>
      </slot>
    </div>
  </div>
</template>
