<script setup>
const props = defineProps({
  label: { type: String, default: '' },
  hint: { type: String, default: '' },
  error: { type: String, default: '' },
  secondary: { type: String, default: '' },
  required: { type: Boolean, default: false },
  inline: { type: Boolean, default: false },
  full: { type: Boolean, default: false },
})
</script>

<template>
  <div :class="['flex flex-col gap-2', inline ? 'sm:flex-row sm:items-center' : '', full ? 'w-full' : '']">
    <div v-if="label" class="flex items-center gap-2">
      <label class="text-sm font-medium text-zinc-200">
        {{ label }}
        <span v-if="required" class="text-brand">*</span>
      </label>
      <span v-if="secondary" class="text-xs text-zinc-500">{{ secondary }}</span>
    </div>

    <div>
      <slot />
      <p v-if="error" class="mt-2 text-xs text-error flex items-center gap-1">
        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z" />
          <path d="M12 8v4" />
          <path d="M12 16h.01" />
        </svg>
        {{ error }}
      </p>
      <p v-else-if="hint" class="mt-2 text-xs text-zinc-500">{{ hint }}</p>
    </div>
  </div>
</template>
