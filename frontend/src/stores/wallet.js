import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api/http'

export const useWalletStore = defineStore('wallet', () => {
  const balance      = ref(0)
  const currency     = ref('BRL')
  const transactions = ref([])
  const loading      = ref(false)
  const error        = ref('')

  async function fetchBalance() {
    loading.value = true
    try {
      const { data } = await api.get('/wallet/balance')
      balance.value  = data.data.balance
      currency.value = data.data.currency
    } catch (err) {
      error.value = err.response?.data?.message || 'Erro ao buscar saldo'
    } finally {
      loading.value = false
    }
  }

  async function fetchHistory(limit = 50) {
    loading.value = true
    try {
      const { data }     = await api.get(`/wallet/history?limit=${limit}`)
      transactions.value = data.data.transactions
    } catch (err) {
      error.value = err.response?.data?.message || 'Erro ao buscar histórico'
    } finally {
      loading.value = false
    }
  }

  async function deposit(amount, description = '') {
    loading.value = true
    error.value   = ''
    try {
      const { data } = await api.post('/wallet/deposit', { amount, description })
      balance.value  = data.data.new_balance
      return data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erro no depósito'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function withdraw(amount, description = '') {
    loading.value = true
    error.value   = ''
    try {
      const { data } = await api.post('/wallet/withdraw', { amount, description })
      balance.value  = data.data.new_balance
      return data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erro no saque'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function transfer(recipientId, amount, description = '') {
    loading.value = true
    error.value   = ''
    try {
      const { data } = await api.post('/transactions/transfer', {
        recipient_id: recipientId,
        amount,
        description,
      })
      await fetchBalance()
      return data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erro na transferência'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function requestReversal(identifiers = {}, reason = 'user_request') {
    // identifiers: { transaction_id, original_transaction_uuid }
    loading.value = true
    error.value   = ''
    try {
      const payload = {
        transaction_id: identifiers.transaction_id ?? identifiers.original_transaction_uuid ?? null,
        original_transaction_uuid: identifiers.original_transaction_uuid ?? identifiers.transaction_id ?? null,
        reason,
      }
      const { data } = await api.post('/transactions/reversal/request', payload)
      // refresh history to reflect new pending reversal
      await fetchHistory()
      return data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erro ao solicitar reversão'
      throw err
    } finally {
      loading.value = false
    }
  }

  function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: currency.value,
    }).format(value)
  }

  return {
    balance, currency, transactions, loading, error,
    fetchBalance, fetchHistory, deposit, withdraw, transfer, requestReversal, formatCurrency,
  }
})
