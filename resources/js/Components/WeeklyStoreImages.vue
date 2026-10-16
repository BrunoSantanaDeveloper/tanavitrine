<script setup>
import { computed, ref, watch } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps({
  store: { type: Object, required: true },
})

const images = computed(() => {
  const photos = props.store.images?.filter(Boolean) || []
  return photos.length ? photos : [props.store.image].filter(Boolean)
})
const currentIndex = ref(0)
const currentImage = computed(() => images.value[currentIndex.value])
let touchStart = null

watch(images, () => { currentIndex.value = 0 })

function move(direction) {
  if (images.value.length < 2) return
  currentIndex.value = (currentIndex.value + direction + images.value.length) % images.value.length
}

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
    class="group/images relative aspect-[4/3] overflow-hidden bg-teal-50"
    role="region"
    aria-roledescription="carrossel"
    :aria-label="`Fotos de ${store.name}`"
    @touchstart.passive="startSwipe"
    @touchend.passive="endSwipe"
    @touchcancel="cancelSwipe"
  >
    <img
      v-if="currentImage"
      :src="currentImage"
      :alt="`${store.name} — foto ${currentIndex + 1} de ${images.length}`"
      loading="lazy"
      draggable="false"
      class="h-full w-full object-cover"
    >
    <div v-else class="flex h-full items-center justify-center text-teal-700/40">
      <Icon icon="lucide:store" class="size-16" aria-hidden="true" />
    </div>
    <div class="pointer-events-none absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-black/55 to-transparent" />
    <span class="pointer-events-none absolute left-4 top-4 inline-flex items-center gap-1.5 rounded-full bg-amber-300 px-3 py-1.5 text-xs font-bold text-teal-950 shadow-sm">
      <Icon icon="lucide:star" class="size-3.5" aria-hidden="true" />
      Destaque da semana
    </span>
    <span class="pointer-events-none absolute bottom-3 right-4 text-[11px] font-medium text-white">Patrocinado</span>

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
      <div class="absolute inset-x-0 bottom-8 flex flex-wrap items-center justify-center px-3">
        <button
          v-for="(_, index) in images"
          :key="index"
          type="button"
          class="flex size-7 items-center justify-center rounded-full focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
          :aria-label="`Ver foto ${index + 1} de ${store.name}`"
          :aria-current="currentIndex === index ? 'true' : undefined"
          @click="currentIndex = index"
        >
          <span class="h-1.5 rounded-full transition-all" :class="currentIndex === index ? 'w-5 bg-amber-300' : 'w-1.5 bg-white/70'" />
        </button>
      </div>
      <span class="sr-only" aria-live="polite" aria-atomic="true">Foto {{ currentIndex + 1 }} de {{ images.length }}</span>
    </template>
  </div>
</template>
