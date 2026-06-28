<script setup>
import { computed, ref } from 'vue'
import AppIcon from './AppIcon.vue'

const props = defineProps({
  modelValue:  { type: [String, Number], default: '' },
  label:       { type: String, default: '' },
  type:        { type: String, default: 'text' },
  placeholder: { type: String, default: '' },
  error:       { type: String, default: '' },
  hint:        { type: String, default: '' },
  required:    { type: Boolean, default: false },
  disabled:    { type: Boolean, default: false },
  prefix:      { type: String, default: '' },
  id:          { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])

const inputId = computed(() => props.id || `input-${Math.random().toString(36).slice(2, 8)}`)
const isPassword = computed(() => props.type === 'password')
const inputType = ref(props.type)

function toggleVisibility() {
  inputType.value = inputType.value === 'password' ? 'text' : 'password'
}
</script>

<template>
  <div class="space-y-2">
    <label v-if="props.label" :for="inputId" class="text-sm font-medium text-zinc-200 flex items-center gap-1">
      {{ props.label }}
      <span v-if="props.required" class="text-brand">*</span>
    </label>

    <div class="relative">
      <span
        v-if="props.prefix"
        class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-zinc-500"
      >
        {{ props.prefix }}
      </span>

      <input
        :id="inputId"
        :type="inputType"
        :value="props.modelValue"
        :placeholder="props.placeholder"
        :disabled="props.disabled"
        :required="props.required"
        @input="emit('update:modelValue', $event.target.value)"
        :class="[
          'w-full rounded-2xl border py-3 text-sm transition focus:outline-none focus:ring-2 focus:ring-brand/30 disabled:cursor-not-allowed disabled:opacity-70',
          props.prefix ? 'pl-12 pr-12' : 'px-4',
          props.error
            ? 'border-error text-error bg-red-950/80 focus:border-error focus:ring-error/30'
            : 'border-zinc-800 bg-zinc-950 text-zinc-100 focus:border-brand',
        ]"
      />

      <button
        v-if="isPassword"
        type="button"
        @click="toggleVisibility"
        class="absolute right-4 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-zinc-300 transition"
        :aria-label="inputType === 'password' ? 'Mostrar senha' : 'Ocultar senha'"
      >
        <AppIcon :name="inputType === 'password' ? 'eye' : 'eye-off'" :size="16" />
      </button>
    </div>

    <p v-if="props.error" class="text-xs text-error">{{ props.error }}</p>
    <p v-else-if="props.hint" class="text-xs text-zinc-500">{{ props.hint }}</p>
  </div>
</template>
