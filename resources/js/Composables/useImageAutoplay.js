import { computed, ref, watch } from 'vue'
import {
  useDocumentVisibility,
  useEventListener,
  useIntersectionObserver,
  useIntervalFn,
} from '@vueuse/core'

export function useImageAutoplay(element, images, currentIndex) {
  const visible = ref(false)
  const hovered = ref(false)
  const focused = ref(false)
  const touching = ref(false)
  const documentVisibility = useDocumentVisibility()

  useIntersectionObserver(element, ([entry]) => {
    visible.value = Boolean(entry?.isIntersecting && entry.intersectionRatio >= 0.1)
  }, { threshold: 0.1 })

  useEventListener(element, 'pointerenter', (event) => {
    if (event.pointerType !== 'touch') hovered.value = true
  })
  useEventListener(element, 'pointerleave', () => { hovered.value = false })
  useEventListener(element, 'focusin', () => { focused.value = true })
  useEventListener(element, 'focusout', (event) => {
    focused.value = Boolean(element.value?.contains(event.relatedTarget))
  })
  useEventListener(element, 'touchstart', () => { touching.value = true }, { passive: true })
  useEventListener(element, ['touchend', 'touchcancel'], () => { touching.value = false }, { passive: true })

  const canPlay = computed(() => images.value.length > 1
    && visible.value
    && !hovered.value
    && !focused.value
    && !touching.value
    && documentVisibility.value === 'visible')

  function advance(direction) {
    if (images.value.length > 1) {
      currentIndex.value = (currentIndex.value + direction + images.value.length) % images.value.length
    }
  }

  const { pause, resume } = useIntervalFn(() => advance(1), 4000, { immediate: false })

  watch(canPlay, (play) => { play ? resume() : pause() }, { immediate: true, flush: 'sync' })
  watch(images, () => { currentIndex.value = 0 })

  // Fetch the upcoming photo before the fade starts, only for visible, playing cards.
  watch([canPlay, currentIndex, images], ([play]) => {
    if (!play || typeof Image === 'undefined') return
    const nextPhoto = images.value[(currentIndex.value + 1) % images.value.length]
    if (nextPhoto) new Image().src = nextPhoto
  }, { immediate: true })

  function moveImage(direction) {
    advance(direction)
    pause()
    if (canPlay.value) resume()
  }

  return { moveImage, isPlaying: canPlay }
}
