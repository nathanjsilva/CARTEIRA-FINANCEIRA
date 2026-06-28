import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api/http'

export const useWalletStore = defineStore('wallet', () => {
  const balance      = ref(0)
  const currency     = ref('BRL')
  const transactions = ref([])
  const users        = ref([])
  const loading      = ref(false)
  const error        = ref('')

  async function fetchBalance() {
    loading.value = true
    try {
      const { data } = await api.get('/wallet/balance')
      balance.value  = data.data.balance
      currency.value = data.data.currency
    } catch (err) {
      error.value = err.response?.data?.message || 'Erro ao buscar o saldo. Tente novamente.'
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
      error.value = err.response?.data?.message || 'Erro ao buscar o histórico de transações.'
    } finally {
      loading.value = false
    }
  }

  async function fetchUsers() {
    try {
      const { data } = await api.get('/users')
      users.value = data.data.users
    } catch {
      users.value = []
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
      error.value = err.response?.data?.message || 'Erro ao processar o depósito. Tente novamente.'
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
      error.value = err.response?.data?.message || 'Erro ao processar o saque. Tente novamente.'
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
      error.value = err.response?.data?.message || 'Erro ao processar a transferência. Tente novamente.'
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
      error.value = err.response?.data?.message || 'Erro ao solicitar a reversão. Tente novamente.'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function downloadReceipt(uuid) {
    try {
      const response = await api.get(`/transactions/${uuid}/receipt`, { responseType: 'blob' })
      const url  = URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
      const link = document.createElement('a')
      link.href  = url
      link.download = `extrato-${uuid}.pdf`
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      URL.revokeObjectURL(url)
    } catch (err) {
      throw err
    }
  }

  function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: currency.value,
    }).format(value)
  }

  return {
    balance, currency, transactions, users, loading, error,
    fetchBalance, fetchHistory, fetchUsers, deposit, withdraw, transfer, requestReversal, downloadReceipt, formatCurrency,
  }
})
