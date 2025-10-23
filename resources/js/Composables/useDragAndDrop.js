import { ref } from 'vue'

export function useDragAndDrop({ onDrop }) {
  const isDragging = ref(false)
  const dragOver = ref(false)

  function handleDragOver(e) {
    e.preventDefault()
    dragOver.value = true
  }

  function handleDragLeave(e) {
    e.preventDefault()
    dragOver.value = false
  }

  function handleDrop(e) {
    e.preventDefault()
    dragOver.value = false
    isDragging.value = false

    const files = Array.from(e.dataTransfer.files)
    if (files.length > 0) {
      onDrop(files)
    }
  }

  return {
    isDragging,
    dragOver,
    handleDragOver,
    handleDragLeave,
    handleDrop,
  }
}
