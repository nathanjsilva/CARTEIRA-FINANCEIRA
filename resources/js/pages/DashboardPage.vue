<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow-sm">
      <div class="max-w-6xl mx-auto px-4 py-4 flex justify-between items-center">
        <h1 class="text-xl font-bold text-gray-900">Carteira Financeira</h1>
        <div class="flex items-center gap-4">
          <span class="text-sm text-gray-600">Olá, {{ authStore.user?.name }}</span>
          <button
            @click="handleLogout"
            class="text-sm text-red-600 hover:text-red-800 font-medium"
          >
            Sair
          </button>
        </div>
      </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-8 space-y-6">
      <!-- Balance Card -->
      <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-2xl p-6 shadow-lg">
        <p class="text-blue-200 text-sm font-medium uppercase tracking-wide">Saldo Disponível</p>
        <h2 class="text-4xl font-bold mt-2">{{ walletStore.formatCurrency(walletStore.balance) }}</h2>
        <p class="text-blue-200 text-sm mt-2">{{ walletStore.currency }}</p>
      </div>

      <!-- Action Buttons -->
      <div class="grid grid-cols-3 gap-4">
        <button
          @click="showModal('deposit')"
          class="bg-white rounded-xl p-5 shadow text-center hover:shadow-md transition"
        >
          <div class="text-green-500 text-2xl mb-2">+</div>
          <span class="text-sm font-medium text-gray-700">Depositar</span>
        </button>
        <button
          @click="showModal('withdraw')"
          class="bg-white rounded-xl p-5 shadow text-center hover:shadow-md transition"
        >
          <div class="text-red-500 text-2xl mb-2">-</div>
          <span class="text-sm font-medium text-gray-700">Sacar</span>
        </button>
        <button
          @click="showModal('transfer')"
          class="bg-white rounded-xl p-5 shadow text-center hover:shadow-md transition"
        >
          <div class="text-blue-500 text-2xl mb-2">→</div>
          <span class="text-sm font-medium text-gray-700">Transferir</span>
        </button>
      </div>

      <!-- Recent Transactions -->
      <div class="bg-white rounded-2xl shadow p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold text-gray-900">Transações Recentes</h3>
          <router-link to="/transactions" class="text-sm text-blue-600 hover:underline">Ver todas</router-link>
        </div>

        <div v-if="walletStore.loading" class="text-center text-gray-400 py-8">Carregando...</div>

        <div v-else-if="!walletStore.transactions.length" class="text-center text-gray-400 py-8">
          Nenhuma transação ainda
        </div>

        <div v-else class="divide-y divide-gray-100">
          <div
            v-for="tx in walletStore.transactions.slice(0, 5)"
            :key="tx.id"
            class="py-3 flex justify-between items-center"
          >
            <div class="flex items-center gap-3">
              <div
                class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold"
                :class="txIconClass(tx)"
              >
                {{ txIcon(tx) }}
              </div>
              <div>
                <p class="text-sm font-medium text-gray-800 capitalize">{{ txLabel(tx) }}</p>
                <p class="text-xs text-gray-400">{{ formatDate(tx.created_at) }}</p>
              </div>
            </div>
            <span class="text-sm font-semibold" :class="txAmountClass(tx)">
              {{ txSign(tx) }}{{ walletStore.formatCurrency(tx.amount) }}
            </span>
          </div>
        </div>
      </div>
    </main>

    <!-- Modal -->
    <div v-if="modal.show" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-2xl p-6 w-full max-w-md mx-4 shadow-xl">
        <h3 class="text-lg font-bold text-gray-900 mb-5 capitalize">
          {{ modal.type === 'deposit' ? 'Depositar' : modal.type === 'withdraw' ? 'Sacar' : 'Transferir' }}
        </h3>

        <form @submit.prevent="handleAction" class="space-y-4">
          <div v-if="modal.type === 'transfer'">
            <label class="block text-sm font-medium text-gray-700 mb-1">ID do Destinatário</label>
            <input
              v-model="modal.recipientId"
              type="text"
              required
              placeholder="ID do usuário destinatário"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Valor (R$)</label>
            <input
              v-model.number="modal.amount"
              type="number"
              step="0.01"
              min="0.01"
              required
              placeholder="0,00"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Descrição (opcional)</label>
            <input
              v-model="modal.description"
              type="text"
              placeholder="Adicione uma descrição"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
            />
          </div>

          <div v-if="modal.error" class="bg-red-50 text-red-600 text-sm px-4 py-3 rounded-lg">
            {{ modal.error }}
          </div>

          <div v-if="modal.success" class="bg-green-50 text-green-700 text-sm px-4 py-3 rounded-lg">
            {{ modal.success }}
          </div>

          <div class="flex gap-3 pt-2">
            <button
              type="button"
              @click="modal.show = false"
              class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-3 rounded-lg transition"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="walletStore.loading"
              class="flex-1 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-semibold py-3 rounded-lg transition"
            >
              {{ walletStore.loading ? 'Processando...' : 'Confirmar' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useWalletStore } from '@/stores/wallet'

const router      = useRouter()
const authStore   = useAuthStore()
const walletStore = useWalletStore()

const modal = reactive({
  show: false, type: 'deposit', amount: null,
  description: '', recipientId: '', error: '', success: '',
})

onMounted(async () => {
  await walletStore.fetchBalance()
  await walletStore.fetchHistory(10)
})

function showModal(type) {
  modal.type        = type
  modal.amount      = null
  modal.description = ''
  modal.recipientId = ''
  modal.error       = ''
  modal.success     = ''
  modal.show        = true
}

async function handleAction() {
  modal.error   = ''
  modal.success = ''
  try {
    if (modal.type === 'deposit') {
      await walletStore.deposit(modal.amount, modal.description)
      modal.success = 'Depósito realizado com sucesso!'
    } else if (modal.type === 'withdraw') {
      await walletStore.withdraw(modal.amount, modal.description)
      modal.success = 'Saque realizado com sucesso!'
    } else {
      await walletStore.transfer(modal.recipientId, modal.amount, modal.description)
      modal.success = 'Transferência realizada com sucesso!'
    }
    await walletStore.fetchHistory(10)
    setTimeout(() => { modal.show = false }, 1500)
  } catch (err) {
    modal.error = err.response?.data?.message || 'Erro ao processar operação'
  }
}

async function handleLogout() {
  await authStore.logout()
  router.push('/login')
}

const txLabel  = (tx) => ({ deposit: 'Depósito', withdrawal: 'Saque', transfer: 'Transferência', reversal: 'Reversão' }[tx.type] || tx.type)
const txIcon   = (tx) => ({ deposit: '+', withdrawal: '-', transfer: '→', reversal: '↩' }[tx.type] || '?')
const txIconClass = (tx) => ({ deposit: 'bg-green-100 text-green-600', withdrawal: 'bg-red-100 text-red-600', transfer: 'bg-blue-100 text-blue-600', reversal: 'bg-yellow-100 text-yellow-600' }[tx.type] || 'bg-gray-100 text-gray-600')
const txSign      = (tx) => (['deposit'].includes(tx.type) ? '+' : '-')
const txAmountClass = (tx) => tx.type === 'deposit' ? 'text-green-600' : 'text-red-600'

function formatDate(dateStr) {
  return new Date(dateStr).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>
