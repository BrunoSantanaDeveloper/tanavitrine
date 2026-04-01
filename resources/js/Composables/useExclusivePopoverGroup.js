import { ref } from 'vue'

export function useExclusivePopoverGroup() {
  const openId = ref(null)

  function isOpen(id) {
    return openId.value === id
  }

  function setOpen(id, nextOpen) {
    openId.value = nextOpen ? id : openId.value === id ? null : openId.value
  }

  function close(id) {
    if (openId.value === id) {
      openId.value = null
    }
  }

  function toggle(id) {
    openId.value = openId.value === id ? null : id
  }

  function closeAll() {
    openId.value = null
  }

  return {
    openId,
    close,
    isOpen,
    setOpen,
    toggle,
    closeAll,
  }
}
