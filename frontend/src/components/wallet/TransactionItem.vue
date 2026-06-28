<script setup>
import { computed } from 'vue'
import { useTransaction } from '@/composables/useTransaction'
import AppIcon from '@/components/ui/AppIcon.vue'
import AppBadge from '@/components/ui/AppBadge.vue'

const props = defineProps({
  transaction:    { type: Object, required: true },
  formattedAmount: { type: String, required: true },
  compact:        { type: Boolean, default: false },
})

const { getConfig, formatDate } = useTransaction()
const config = computed(() => getConfig(props.transaction.type))
</script>

<template>
  <div
    :class="[
      'flex items-center justify-between gap-4 rounded-3xl border border-zinc-800 bg-zinc-950/80 p-4 transition hover:border-zinc-700 hover:bg-zinc-900/80',
      props.compact ? 'py-3' : 'py-4',
    ]"
  >
    <div class="flex items-center gap-4 min-w-0">
      <div
        :class="[
          'flex h-11 w-11 items-center justify-center rounded-3xl shrink-0',
          config.bgColor,
          config.color,
        ]"
      >
        <AppIcon :name="config.icon" :size="18" :stroke-width="1.75" />
      </div>

      <div class="min-w-0">
        <p class="text-sm font-semibold text-zinc-100 truncate">
          {{ config.label }}
        </p>
        <p v-if="transaction.description" class="text-sm text-zinc-500 truncate max-w-[16rem]">
          {{ transaction.description }}
        </p>
        <p class="text-xs text-zinc-600 mt-1">{{ formatDate(transaction.created_at) }}</p>
      </div>
    </div>

    <div class="flex flex-col items-end gap-2 min-w-[120px] text-right">
      <p
        :class="[
          'font-semibold tabular-nums',
          config.positive ? 'text-success' : 'text-zinc-200',
        ]"
      >
        {{ config.sign }}{{ formattedAmount }}
      </p>
      <AppBadge :variant="transaction.status" size="xs">{{ transaction.status }}</AppBadge>
    </div>
  </div>
</template>
