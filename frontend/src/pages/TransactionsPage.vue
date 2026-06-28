<script setup>
import { ref, computed, onMounted } from 'vue'
import { useWalletStore } from '@/stores/wallet'
import AppLayout       from '@/components/layout/AppLayout.vue'
import AppHeader       from '@/components/ui/AppHeader.vue'
import AppCard         from '@/components/ui/AppCard.vue'
import AppEmptyState   from '@/components/ui/AppEmptyState.vue'
import AppIcon         from '@/components/ui/AppIcon.vue'
import AppSkeleton     from '@/components/ui/AppSkeleton.vue'
import TransactionItem from '@/components/wallet/TransactionItem.vue'

const walletStore = useWalletStore()

const search     = ref('')
const filterType = ref('all')

onMounted(() => walletStore.fetchHistory(100))

const typeOptions = [
  { value: 'all',        label: 'Todos',        icon: 'fa-solid fa-list' },
  { value: 'deposit',    label: 'Depósitos',    icon: 'fa-solid fa-arrow-down' },
  { value: 'withdrawal', label: 'Saques',       icon: 'fa-solid fa-arrow-up' },
  { value: 'transfer',   label: 'Transferências', icon: 'fa-solid fa-arrows-left-right' },
  { value: 'reversal',   label: 'Reversões',    icon: 'fa-solid fa-rotate-left' },
]

const filtered = computed(() => {
  let list = walletStore.transactions

  if (filterType.value !== 'all') {
    list = list.filter((tx) => tx.type === filterType.value)
  }

  if (search.value.trim()) {
    const q = search.value.trim().toLowerCase()
    list = list.filter(
      (tx) =>
        tx.type.toLowerCase().includes(q) ||
        (tx.description ?? '').toLowerCase().includes(q) ||
        walletStore.formatCurrency(tx.amount).toLowerCase().includes(q),
    )
  }

  return list
})
</script>

<template>
  <AppLayout>
    <div class="space-y-6 animate-fade-in">
      <AppHeader
        title="Histórico de transações"
        subtitle="Filtre e analise cada movimentação realizada na sua conta"
      />

      <AppCard>
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
          <div class="min-w-0">
            <p class="text-sm font-semibold text-zinc-100">Filtrar transações</p>
            <p class="mt-1 text-sm text-zinc-500">Use o campo de busca ou selecione o tipo de operação desejada.</p>
          </div>
          <div class="grid w-full gap-3 grid-cols-1 lg:grid-cols-[1.6fr_0.9fr]">
            <div class="relative min-w-0">
              <div class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-zinc-500">
                <AppIcon name="search" size="16" stroke-width="2" />
              </div>
              <input
                v-model="search"
                type="search"
                placeholder="Buscar transações..."
                class="w-full rounded-2xl border border-zinc-800 bg-zinc-950/90 py-3 pl-11 pr-4 text-sm text-zinc-100 placeholder:text-zinc-600 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20"
              />
            </div>

            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5">
              <button
                v-for="opt in typeOptions"
                :key="opt.value"
                @click="filterType = opt.value"
                :aria-label="opt.label"
                :title="opt.label"
                :class="[
                  'w-full rounded-2xl border px-3 py-3 text-sm font-medium transition min-w-0 flex items-center justify-center gap-2',
                  filterType === opt.value
                    ? 'border-brand bg-brand text-zinc-950'
                    : 'border-zinc-800 bg-zinc-950 text-zinc-400 hover:border-zinc-700 hover:bg-zinc-900 hover:text-zinc-100',
                ]"
              >
                <i :class="opt.icon" aria-hidden="true"></i>
              </button>
            </div>
          </div>
        </div>
      </AppCard>

      <AppCard>
        <div class="space-y-5">
          <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <p class="text-sm font-semibold text-zinc-100">Resultados</p>
              <p class="mt-1 text-sm text-zinc-500">{{ filtered.length }} de {{ walletStore.transactions.length }} transações encontradas</p>
            </div>
          </div>

          <template v-if="walletStore.loading && !walletStore.transactions.length">
            <div class="space-y-4">
              <div v-for="i in 6" :key="i" class="rounded-3xl border border-zinc-800 bg-zinc-950/90 p-4">
                <div class="flex items-center gap-4">
                  <AppSkeleton class="w-12 h-12" rounded="rounded-full" />
                  <div class="flex-1 space-y-2">
                    <AppSkeleton class="h-4 w-32" />
                    <AppSkeleton class="h-3 w-24" />
                  </div>
                  <AppSkeleton class="h-4 w-20" />
                </div>
              </div>
            </div>
          </template>

          <template v-else-if="!filtered.length">
            <AppEmptyState
              icon="list"
              title="Nenhuma transação encontrada"
              :message="walletStore.transactions.length ? 'Ajuste os filtros para encontrar o que procura.' : 'Ainda não há transações cadastradas.'"
            />
          </template>

          <template v-else>
            <div class="space-y-3">
              <TransactionItem
                v-for="tx in filtered"
                :key="tx.id"
                :transaction="tx"
                :formatted-amount="walletStore.formatCurrency(tx.amount)"
              />
            </div>
          </template>
        </div>
      </AppCard>
    </div>
  </AppLayout>
</template>
