import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

export function __(key, params = {}) {
  const page = usePage()
  return computed(() => {
    const translations = page.props.translations || {}
    const keys = key.split('.')
    let value = translations
    for (const k of keys) {
      value = value?.[k]
      if (value === undefined)
        return key
    }
    if (typeof value === 'string') {
      // Substitui parâmetros, se houver
      Object.entries(params).forEach(([k, v]) => {
        value = value.replace(`:${k}`, v)
      })
      return value
    }
    return key
  }).value
}
