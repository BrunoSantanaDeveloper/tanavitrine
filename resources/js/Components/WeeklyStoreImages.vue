<script setup>
import { computed, ref } from 'vue'
import { Icon } from '@iconify/vue'
import { useImageAutoplay } from '@/Composables/useImageAutoplay'

const props = defineProps({
  store: { type: Object, required: true },
  promoted: { type: Boolean, default: true },
  compact: { type: Boolean, default: false },
  priority: { type: Boolean, default: false },
})

const images = computed(() => {
  const photos = props.store.images?.filter(Boolean) || []
  return photos.length ? photos : [props.store.image].filter(Boolean)
})
const currentIndex = ref(0)
const imageContainer = ref(null)
const currentImage = computed(() => images.value[currentIndex.value])
const { moveImage: move, isPlaying } = useImageAutoplay(imageContainer, images, currentIndex)
let touchStart = null

function startSwipe(event) {
  const touch = event.touches[0]
  touchStart = touch ? { x: touch.clientX, y: touch.clientY } : null
}

function endSwipe(event) {
  const touch = event.changedTouches[0]
  if (!touchStart || !touch) return
  const dx = touch.clientX - touchStart.x
  const dy = touch.clientY - touchStart.y
  touchStart = null
  if (Math.abs(dx) >= 40 && Math.abs(dx) > Math.abs(dy)) move(dx < 0 ? 1 : -1)
}

function cancelSwipe() {
  touchStart = null
}
</script>

<template>
  <div
    ref="imageContainer"
    class="group/images relative overflow-hidden bg-teal-50"
    :class="compact ? 'h-36 sm:h-40 lg:h-[clamp(90px,18svh,180px)]' : 'aspect-[4/3]'"
    role="region"
    aria-roledescription="carrossel"
    :aria-label="`Fotos de ${store.name}`"
    @touchstart.passive="startSwipe"
    @touchend.passive="endSwipe"
    @touchcancel="cancelSwipe"
  >
    <Transition name="weekly-photo">
      <img
        v-if="currentImage"
        :key="currentImage"
        :src="currentImage"
        :alt="`${store.name} — foto ${currentIndex + 1} de ${images.length}`"
        :loading="priority ? 'eager' : 'lazy'"
        draggable="false"
        class="absolute inset-0 h-full w-full object-cover"
      >
      <div v-else class="flex h-full items-center justify-center text-teal-700/40">
        <Icon icon="lucide:store" class="size-16" aria-hidden="true" />
      </div>
    </Transition>
    <div class="pointer-events-none absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-black/55 to-transparent" />
    <span v-if="promoted" class="pointer-events-none absolute left-4 top-4 inline-flex items-center gap-1.5 rounded-full bg-amber-300 px-3 py-1.5 text-xs font-bold text-teal-950 shadow-sm">
      <Icon icon="lucide:star" class="size-3.5" aria-hidden="true" />
      Destaque da semana
    </span>
    <span v-else-if="store.category" class="pointer-events-none absolute left-3 top-3 rounded-md bg-black/65 px-2 py-1 text-[10px] font-medium text-white backdrop-blur-sm">{{ store.category }}</span>
    <span v-if="promoted" class="pointer-events-none absolute bottom-3 right-4 text-[11px] font-medium text-white">Patrocinado</span>

    <template v-if="images.length > 1">
      <button
        type="button"
        class="absolute left-2 top-1/2 flex size-11 -translate-y-1/2 items-center justify-center rounded-full bg-black/35 text-white backdrop-blur-sm transition-all hover:bg-black/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white sm:opacity-0 sm:group-hover/images:opacity-100 sm:group-focus-within/images:opacity-100"
        :aria-label="`Foto anterior de ${store.name}`"
        @click="move(-1)"
      >
        <Icon icon="lucide:chevron-left" class="size-5" aria-hidden="true" />
      </button>
      <button
        type="button"
        class="absolute right-2 top-1/2 flex size-11 -translate-y-1/2 items-center justify-center rounded-full bg-black/35 text-white backdrop-blur-sm transition-all hover:bg-black/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white sm:opacity-0 sm:group-hover/images:opacity-100 sm:group-focus-within/images:opacity-100"
        :aria-label="`Próxima foto de ${store.name}`"
        @click="move(1)"
      >
        <Icon icon="lucide:chevron-right" class="size-5" aria-hidden="true" />
      </button>
      <span class="sr-only" :aria-live="isPlaying ? 'off' : 'polite'" aria-atomic="true">Foto {{ currentIndex + 1 }} de {{ images.length }}</span>
    </template>
  </div>
</template>

<style scoped>
.weekly-photo-enter-active,
.weekly-photo-leave-active {
  transition: opacity 350ms ease;
}

.weekly-photo-enter-from,
.weekly-photo-leave-to {
  opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
  .weekly-photo-enter-active,
  .weekly-photo-leave-active {
    transition: none;
  }
}
</style>
