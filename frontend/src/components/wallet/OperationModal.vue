<script setup>
import { reactive, computed } from 'vue'
import { useWalletStore } from '@/stores/wallet'
import { useToast } from '@/composables/useToast'
import AppModal from '@/components/ui/AppModal.vue'
import AppInput from '@/components/ui/AppInput.vue'
import AppButton from '@/components/ui/AppButton.vue'

const props = defineProps({
  show: { type: Boolean, required: true },
  type: { type: String, required: true },
})

const emit = defineEmits(['close', 'success'])

const walletStore = useWalletStore()
const toast       = useToast()

const form = reactive({
  amount:      '',
  description: '',
  recipientId: '',
  error:       '',
})

const titles = {
  deposit:  'Depositar',
  withdraw: 'Sacar',
  transfer: 'Transferir',
}

const modalTitle = computed(() => titles[props.type] ?? 'Operação')

function resetForm() {
  form.amount      = ''
  form.description = ''
  form.recipientId = ''
  form.error       = ''
}

function handleClose() {
  resetForm()
  emit('close')
}

async function handleSubmit() {
  form.error = ''

  const amount = parseFloat(form.amount)

  if (!amount || amount <= 0) {
    form.error = 'Informe um valor válido maior que zero.'
    return
  }

  try {
    if (props.type === 'deposit') {
      await walletStore.deposit(amount, form.description)
      toast.success('Depósito realizado com sucesso!')
    } else if (props.type === 'withdraw') {
      await walletStore.withdraw(amount, form.description)
      toast.success('Saque realizado com sucesso!')
    } else {
      if (!form.recipientId) {
        form.error = 'Informe o ID do destinatário.'
        return
      }
      await walletStore.transfer(form.recipientId, amount, form.description)
      toast.success('Transferência realizada com sucesso!')
    }

    await walletStore.fetchHistory(10)
    emit('success')
    handleClose()
  } catch (err) {
    form.error = err.response?.data?.message || 'Erro ao processar operação.'
  }
}
</script>

<template>
  <AppModal :show="show" :title="modalTitle" @close="handleClose">
    <form @submit.prevent="handleSubmit" class="space-y-4" novalidate>
      <AppInput
        v-if="type === 'transfer'"
        v-model="form.recipientId"
        label="ID do Destinatário"
        placeholder="ID numérico do usuário"
        :required="true"
        hint="Informe o identificador numérico da conta de destino"
      />

      <AppInput
        v-model="form.amount"
        type="number"
        label="Valor"
        placeholder="0,00"
        prefix="R$"
        :required="true"
        hint="Mínimo de R$ 0,01"
      />

      <AppInput
        v-model="form.description"
        label="Descrição"
        placeholder="Adicione uma descrição (opcional)"
      />

      <div
        v-if="form.error"
        class="flex items-start gap-2.5 bg-error/10 border border-error/20 text-error text-sm px-4 py-3 rounded-lg"
      >
        <svg class="shrink-0 mt-0.5" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zM12 8v4M12 16h.01"/>
        </svg>
        {{ form.error }}
      </div>

      <div class="flex gap-2.5 pt-1">
        <AppButton
          type="button"
          variant="secondary"
          :full="true"
          @click="handleClose"
        >
          Cancelar
        </AppButton>
        <AppButton
          type="submit"
          variant="primary"
          :full="true"
          :loading="walletStore.loading"
        >
          Confirmar
        </AppButton>
      </div>
    </form>
  </AppModal>
</template>
