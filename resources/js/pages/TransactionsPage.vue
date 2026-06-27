<template>
  <div class="min-h-screen bg-gray-100">
    <header class="bg-white shadow-sm">
      <div class="max-w-4xl mx-auto px-4 py-4 flex items-center gap-4">
        <router-link to="/dashboard" class="text-gray-500 hover:text-gray-700">← Voltar</router-link>
        <h1 class="text-xl font-bold text-gray-900">Histórico de Transações</h1>
      </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-8">
      <div class="bg-white rounded-2xl shadow p-6">
        <div v-if="walletStore.loading" class="text-center text-gray-400 py-12">Carregando...</div>

        <div v-else-if="!walletStore.transactions.length" class="text-center text-gray-400 py-12">
          Nenhuma transação encontrada
        </div>

        <div v-else class="divide-y divide-gray-100">
          <div
            v-for="tx in walletStore.transactions"
            :key="tx.id"
            class="py-4 flex justify-between items-center"
          >
            <div class="flex items-center gap-4">
              <div
                class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold"
                :class="txIconClass(tx)"
              >
                {{ txIcon(tx) }}
              </div>
              <div>
                <p class="font-medium text-gray-800 capitalize">{{ txLabel(tx) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ formatDate(tx.created_at) }}</p>
              </div>
            </div>
            <div class="text-right">
              <p class="font-semibold" :class="txAmountClass(tx)">
                {{ txSign(tx) }}{{ walletStore.formatCurrency(tx.amount) }}
              </p>
              <p class="text-xs text-gray-400 capitalize mt-0.5">{{ tx.status }}</p>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useWalletStore } from '@/stores/wallet'

const walletStore = useWalletStore()

onMounted(() => walletStore.fetchHistory(100))

const txLabel     = (tx) => ({ deposit: 'Depósito', withdrawal: 'Saque', transfer: 'Transferência', reversal: 'Reversão' }[tx.type] || tx.type)
const txIcon      = (tx) => ({ deposit: '+', withdrawal: '-', transfer: '→', reversal: '↩' }[tx.type] || '?')
const txIconClass = (tx) => ({ deposit: 'bg-green-100 text-green-600', withdrawal: 'bg-red-100 text-red-600', transfer: 'bg-blue-100 text-blue-600', reversal: 'bg-yellow-100 text-yellow-600' }[tx.type] || 'bg-gray-100 text-gray-600')
const txSign      = (tx) => tx.type === 'deposit' ? '+' : '-'
const txAmountClass = (tx) => tx.type === 'deposit' ? 'text-green-600' : 'text-red-600'

function formatDate(dateStr) {
  return new Date(dateStr).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>
