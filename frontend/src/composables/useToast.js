import { ref } from 'vue'

const toasts = ref([])
let _id = 0

export function useToast() {
  function show(message, type = 'info', duration = 4500) {
    const id = ++_id
    toasts.value.push({ id, message, type, leaving: false })
    setTimeout(() => dismiss(id), duration)
  }

  function dismiss(id) {
    const toast = toasts.value.find((t) => t.id === id)
    if (!toast) return
    toast.leaving = true
    setTimeout(() => {
      toasts.value = toasts.value.filter((t) => t.id !== id)
    }, 300)
  }

  const success = (msg) => show(msg, 'success')
  const error   = (msg) => show(msg, 'error')
  const warning = (msg) => show(msg, 'warning')
  const info    = (msg) => show(msg, 'info')

  return { toasts, show, dismiss, success, error, warning, info }
}
