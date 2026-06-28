<script setup>
import { ref, computed, onMounted } from 'vue'
import { useWalletStore } from '@/stores/wallet'
import AppLayout       from '@/components/layout/AppLayout.vue'
import AppHeader       from '@/components/ui/AppHeader.vue'
import AppCard         from '@/components/ui/AppCard.vue'
import AppIcon         from '@/components/ui/AppIcon.vue'
import AppSkeleton     from '@/components/ui/AppSkeleton.vue'
import BalanceCard     from '@/components/wallet/BalanceCard.vue'
import TransactionItem from '@/components/wallet/TransactionItem.vue'
import OperationModal  from '@/components/wallet/OperationModal.vue'

const walletStore = useWalletStore()

const modalType = ref('deposit')
const modalOpen = ref(false)

const actions = [
  { type: 'deposit',  label: 'Depositar',  icon: 'arrow-down-circle', color: 'text-success', bg: 'bg-success/10 hover:bg-success/20' },
  { type: 'withdraw', label: 'Sacar',      icon: 'arrow-up-circle',   color: 'text-error',   bg: 'bg-error/10 hover:bg-error/20' },
  { type: 'transfer', label: 'Transferir', icon: 'arrows-left-right', color: 'text-info',    bg: 'bg-info/10 hover:bg-info/20' },
]

onMounted(async () => {
  await walletStore.fetchBalance()
  await walletStore.fetchHistory(10)
})

function openModal(type) {
  modalType.value = type
  modalOpen.value = true
}

function onOperationSuccess() {
  walletStore.fetchBalance()
}

const recentTransactions = computed(() => walletStore.transactions.slice(0, 5))
</script>

<template>
  <AppLayout>
    <div class="space-y-6 animate-fade-in">
      <AppHeader title="Dashboard" subtitle="Resumo das suas finanças e transações recentes" />

      <div class="grid gap-5 grid-cols-1 xl:grid-cols-[1.2fr_0.8fr] items-start">
        <div class="space-y-5 min-w-0">
          <BalanceCard
            :balance="walletStore.balance"
            :currency="walletStore.currency"
            :loading="walletStore.loading && !walletStore.balance"
            :formatted="walletStore.formatCurrency(walletStore.balance)"
          />

          <AppCard>
            <div class="flex items-center justify-between gap-4">
              <div>
                <p class="text-sm font-semibold text-zinc-100">Transações recentes</p>
                <p class="mt-1 text-sm text-zinc-500">As últimas 5 movimentações da sua conta</p>
              </div>
              <RouterLink
                to="/transactions"
                class="text-sm font-semibold text-brand hover:text-[#f4b400]"
              >
                Ver todas
              </RouterLink>
            </div>

            <div class="mt-6 space-y-3">
              <template v-if="walletStore.loading && !walletStore.transactions.length">
                <div v-for="i in 4" :key="i" class="rounded-3xl border border-zinc-800 bg-zinc-950/90 p-4">
                  <div class="flex items-center gap-4">
                    <AppSkeleton class="w-11 h-11" rounded="rounded-full" />
                    <div class="w-full space-y-2">
                      <AppSkeleton class="h-4 w-24" />
                      <AppSkeleton class="h-3 w-20" />
                    </div>
                    <AppSkeleton class="h-4 w-16" />
                  </div>
                </div>
              </template>

              <template v-else-if="!walletStore.transactions.length">
                <div class="rounded-3xl border border-zinc-800 bg-zinc-950/90 p-8 text-center">
                  <p class="text-sm font-semibold text-zinc-200">Nenhuma transação ainda</p>
                  <p class="mt-2 text-sm text-zinc-500">Realize um depósito para começar a registrar suas movimentações.</p>
                </div>
              </template>

              <template v-else>
                <div class="space-y-3">
                  <TransactionItem
                    v-for="tx in recentTransactions"
                    :key="tx.id"
                    :transaction="tx"
                    :formatted-amount="walletStore.formatCurrency(tx.amount)"
                    :compact="true"
                  />
                </div>
              </template>
            </div>
          </AppCard>
        </div>

        <AppCard class="h-full min-h-[22rem]">
          <div class="flex items-center justify-between gap-4">
            <div>
              <p class="text-sm font-semibold text-zinc-100">Ações rápidas</p>
              <p class="mt-1 text-sm text-zinc-500">Realize transferências, saques ou depósitos em segundos.</p>
            </div>
          </div>

          <div class="mt-6 grid gap-3 grid-cols-1 md:grid-cols-3">
            <button
              v-for="action in actions"
              :key="action.type"
              @click="openModal(action.type)"
              :class="[
                'group flex min-w-0 w-full flex-col items-center justify-center gap-3 rounded-3xl border px-4 py-5 text-center transition duration-150',
                'border-zinc-800 bg-zinc-950 hover:border-zinc-700 hover:bg-zinc-900/90',
              ]"
            >
              <div :class="['inline-flex h-12 w-12 items-center justify-center rounded-3xl', action.bg, action.color]">
                <AppIcon :name="action.icon" :size="18" :stroke-width="1.75" />
              </div>
              <span class="text-sm font-medium text-zinc-200">{{ action.label }}</span>
            </button>
          </div>
        </AppCard>
      </div>

      <OperationModal
        :show="modalOpen"
        :type="modalType"
        @close="modalOpen = false"
        @success="onOperationSuccess"
      />
    </div>
  </AppLayout>
</template>
