<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue:  { type: [String, Number], default: '' },
  label:       { type: String, default: '' },
  options:     { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Selecione...' },
  error:       { type: String, default: '' },
  hint:        { type: String, default: '' },
  required:    { type: Boolean, default: false },
  disabled:    { type: Boolean, default: false },
  id:          { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])

const selectId = computed(() => props.id || `select-${Math.random().toString(36).slice(2, 8)}`)

const chevronStyle = {
  backgroundImage: `url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2371717a' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C%2Fpolyline%3E%3C%2Fsvg%3E")`,
  backgroundPosition: 'right 1rem center',
}
</script>

<template>
  <div class="space-y-2">
    <label v-if="props.label" :for="selectId" class="text-sm font-medium text-zinc-200 flex items-center gap-1">
      {{ props.label }}
      <span v-if="props.required" class="text-brand">*</span>
    </label>

    <div class="relative">
      <select
        :id="selectId"
        :value="props.modelValue"
        :disabled="props.disabled"
        :required="props.required"
        @change="emit('update:modelValue', $event.target.value)"
        :class="[
          'w-full rounded-2xl border py-3 px-4 text-sm transition focus:outline-none focus:ring-2 focus:ring-brand/30 disabled:cursor-not-allowed disabled:opacity-70 appearance-none bg-no-repeat',
          props.error
            ? 'border-error text-error bg-red-950/80 focus:border-error focus:ring-error/30'
            : 'border-zinc-800 bg-zinc-950 text-zinc-100 focus:border-brand',
        ]"
        :style="chevronStyle"
      >
        <option value="" disabled>{{ props.placeholder }}</option>
        <option
          v-for="opt in props.options"
          :key="opt.value"
          :value="opt.value"
        >
          {{ opt.label }}
        </option>
      </select>
    </div>

    <p v-if="props.error" class="text-xs text-error">{{ props.error }}</p>
    <p v-else-if="props.hint" class="text-xs text-zinc-500">{{ props.hint }}</p>
  </div>
</template>
