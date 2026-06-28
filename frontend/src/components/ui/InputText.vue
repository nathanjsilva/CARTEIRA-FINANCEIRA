<script setup>
import { computed, ref } from 'vue'
import AppIcon from './AppIcon.vue'

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  label: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  type: { type: String, default: 'text' },
  error: { type: String, default: '' },
  hint: { type: String, default: '' },
  required: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  prefix: { type: String, default: '' },
  id: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])

const inputId = props.id || `input-${Math.random().toString(36).slice(2, 8)}`
const isPassword = computed(() => props.type === 'password')
const typeValue = ref(props.type)

function toggleVisibility() {
  typeValue.value = typeValue.value === 'password' ? 'text' : 'password'
}
</script>

<template>
  <div class="space-y-2">
    <div class="flex items-center justify-between gap-3">
      <label v-if="label" :for="inputId" class="text-sm font-medium text-zinc-200">
        {{ label }}
        <span v-if="required" class="text-brand">*</span>
      </label>
      <span v-if="hint && !error" class="text-xs text-zinc-500">{{ hint }}</span>
    </div>

    <div class="relative">
      <span v-if="props.prefix" class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-zinc-500">{{ props.prefix }}</span>
      <input
        :id="inputId"
        :type="typeValue"
        :value="props.modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        @input="emit('update:modelValue', $event.target.value)"
        :class="[
          'w-full rounded-2xl border px-4 py-3 text-sm text-zinc-100 bg-zinc-950 border-zinc-800 transition focus:outline-none focus:ring-2 focus:ring-brand/30',
          props.prefix ? 'pl-12' : 'pl-4',
          error ? 'border-error text-error focus:border-error focus:ring-error/30' : 'border-zinc-800 text-zinc-100',
          disabled ? 'cursor-not-allowed opacity-70' : 'hover:border-zinc-700',
        ]"
      />

      <button
        v-if="isPassword"
        type="button"
        @click="toggleVisibility"
        class="absolute right-4 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-zinc-300"
        aria-label="Alternar visibilidade da senha"
      >
        <AppIcon :name="typeValue === 'password' ? 'eye' : 'eye-off'" size="16" />
      </button>
    </div>

    <p v-if="error" class="text-xs text-error">{{ error }}</p>
  </div>
</template>
