<script setup>
import { computed, ref } from 'vue'
import { useTransaction } from '@/composables/useTransaction'
import { useToast } from '@/composables/useToast'
import { useWalletStore } from '@/stores/wallet'
import AppModal from '@/components/ui/AppModal.vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import AppBadge from '@/components/ui/AppBadge.vue'

const props = defineProps({
  transaction:    { type: Object, required: true },
  formattedAmount: { type: String, required: true },
  compact:        { type: Boolean, default: false },
})

const { getConfig, formatDate } = useTransaction()
const config = computed(() => getConfig(props.transaction.type))
const walletStore = useWalletStore()
const toast = useToast()
const requesting  = ref(false)
const downloading = ref(false)
const modalOpen   = ref(false)
const reason      = ref('')

async function downloadReceipt() {
  downloading.value = true
  try {
    const uuid = props.transaction.id || props.transaction.uuid
    await walletStore.downloadReceipt(uuid)
  } catch {
    toast.error('Erro ao gerar extrato PDF')
  } finally {
    downloading.value = false
  }
}

function openReversalModal() {
  reason.value = 'user_request'
  modalOpen.value = true
}

async function submitReversalRequest() {
  if (!props.transaction) return
  requesting.value = true
  try {
    const txUuid = props.transaction.uuid || props.transaction.id
    const txId = props.transaction.id || props.transaction.uuid
    await walletStore.requestReversal({ transaction_id: txId, original_transaction_uuid: txUuid }, reason.value || 'user_request')
    toast.success('Reversão solicitada com sucesso')
    modalOpen.value = false
  } catch (err) {
    toast.error(err.response?.data?.message || 'Erro ao solicitar reversão')
  } finally {
    requesting.value = false
  }
}
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

      <button
        @click="downloadReceipt"
        :disabled="downloading"
        class="w-full rounded-xl border border-zinc-800 bg-zinc-950/90 px-3 py-1 text-xs text-zinc-400 hover:border-zinc-600 hover:text-zinc-100 transition-colors flex items-center justify-center gap-1"
        title="Baixar extrato em PDF"
      >
        <AppIcon name="file-text" :size="11" :stroke-width="2" />
        <span>{{ downloading ? 'Gerando...' : 'Baixar extrato' }}</span>
      </button>

      <button
        v-if="transaction.type === 'transfer' && transaction.status === 'completed'"
        @click="openReversalModal"
        :disabled="requesting"
        class="w-full rounded-xl border border-zinc-800 bg-zinc-950/90 px-3 py-1 text-xs text-zinc-100 hover:border-zinc-700"
      >
        <span v-if="!requesting">Solicitar reversão</span>
        <span v-else>Solicitando...</span>
      </button>

      <AppModal :show="modalOpen" title="Solicitar Reversão" @close="modalOpen = false">
        <div class="space-y-3">
          <p class="text-sm text-zinc-400">Informe o motivo da solicitação (opcional)</p>
          <label class="text-sm text-zinc-400">Selecione o motivo</label>
          <select v-model="reason" class="w-full rounded-lg bg-zinc-950/90 border border-zinc-800 p-3 text-sm text-zinc-100">
            <option value="user_request">Solicitação do usuário</option>
            <option value="system_error">Erro do sistema</option>
            <option value="fraud_detection">Suspeita de fraude</option>
            <option value="compliance">Conformidade</option>
          </select>
          <div class="flex justify-end gap-2">
            <button @click="modalOpen = false" class="rounded-xl border border-zinc-800 bg-zinc-900 px-4 py-2 text-sm">Cancelar</button>
            <button @click="submitReversalRequest" :disabled="requesting" class="rounded-xl border border-brand bg-brand px-4 py-2 text-sm text-zinc-950">
              <span v-if="!requesting">Enviar</span>
              <span v-else>Enviando...</span>
            </button>
          </div>
        </div>
      </AppModal>
    </div>
  </div>
</template>
