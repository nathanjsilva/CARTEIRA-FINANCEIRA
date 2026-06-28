export function useTransaction() {
  const typeConfig = {
    deposit: {
      label:    'Depósito',
      icon:     'arrow-down-circle',
      color:    'text-success',
      bgColor:  'bg-success/10',
      sign:     '+',
      positive: true,
    },
    withdrawal: {
      label:    'Saque',
      icon:     'arrow-up-circle',
      color:    'text-error',
      bgColor:  'bg-error/10',
      sign:     '-',
      positive: false,
    },
    transfer: {
      label:    'Transferência enviada',
      icon:     'arrow-up-right',
      color:    'text-error',
      bgColor:  'bg-error/10',
      sign:     '-',
      positive: false,
    },
    transfer_received: {
      label:    'Transferência recebida',
      icon:     'arrow-down-left',
      color:    'text-success',
      bgColor:  'bg-success/10',
      sign:     '+',
      positive: true,
    },
    reversal: {
      label:    'Reversão',
      icon:     'refresh-ccw',
      color:    'text-brand',
      bgColor:  'bg-brand/10',
      sign:     '+',
      positive: true,
    },
  }

  function getConfig(type, direction) {
    if (type === 'transfer' && direction === 'received') {
      return typeConfig['transfer_received']
    }
    return typeConfig[type] ?? {
      label:    type,
      icon:     'info',
      color:    'text-zinc-400',
      bgColor:  'bg-zinc-800',
      sign:     '',
      positive: false,
    }
  }

  function formatDate(dateStr) {
    return new Date(dateStr).toLocaleString('pt-BR', {
      day:    '2-digit',
      month:  '2-digit',
      year:   'numeric',
      hour:   '2-digit',
      minute: '2-digit',
    })
  }

  return { getConfig, formatDate }
}
