<script setup>
import { cn } from '@/lib/utils'
import { onBeforeUnmount, onMounted, ref } from 'vue'

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  triggerClass: {
    type: null,
    default: null,
  },
  contentClass: {
    type: null,
    default: null,
  },
})

const emit = defineEmits(['update:open'])
const rootRef = ref(null)

function close() {
  if (props.open) {
    emit('update:open', false)
  }
}

function toggle() {
  if (props.disabled) {
    return
  }

  emit('update:open', !props.open)
}

function handlePointerStart(event) {
  if (!props.open) {
    return
  }

  const root = rootRef.value
  if (!root) {
    return
  }

  if (root.contains(event.target)) {
    return
  }

  close()
}

function handleEscape(event) {
  if (!props.open) {
    return
  }

  if (event.key === 'Escape') {
    close()
  }
}

onMounted(() => {
  document.addEventListener('pointerdown', handlePointerStart, true)
  document.addEventListener('touchstart', handlePointerStart, true)
  document.addEventListener('keydown', handleEscape)
})

onBeforeUnmount(() => {
  document.removeEventListener('pointerdown', handlePointerStart, true)
  document.removeEventListener('touchstart', handlePointerStart, true)
  document.removeEventListener('keydown', handleEscape)
})
</script>

<template>
  <div ref="rootRef" class="relative">
    <button
      type="button"
      role="combobox"
      :disabled="disabled"
      :aria-expanded="open"
      :class="cn(triggerClass)"
      @click="toggle"
    >
      <slot name="trigger" :open="open" />
    </button>

    <div
      v-if="open"
      :class="cn(
        'absolute left-0 top-[calc(100%+4px)] z-50 w-full rounded-md border bg-popover text-popover-foreground shadow-md',
        contentClass,
      )"
    >
      <slot />
    </div>
  </div>
</template>
