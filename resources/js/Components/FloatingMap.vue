<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { Dialog, DialogContent } from '@/Components/shadcn/ui/dialog'
import { Button } from '@/Components/shadcn/ui/button'
import Badge from '@/Components/shadcn/ui/badge/Badge.vue'
import StoreMap from '@/Components/StoreMap.vue'
import { Icon } from '@iconify/vue'
import { useMediaQuery } from '@vueuse/core'

const props = defineProps({
  stores: {
    type: Array,
    required: true,
  },
  hoveredStoreId: {
    type: Number,
    default: null,
  },
  title: {
    type: String,
    default: 'Mapa de Lojas',
  },
})

const emit = defineEmits(['marker-click', 'marker-hover'])

// Detect mobile
const isMobile = useMediaQuery('(max-width: 1023px)')

// Widget states
const isVisible = ref(false) // Widget is visible - starts hidden to avoid mount errors
const isMinimized = ref(false) // Widget is minimized to button only
const isFullscreen = ref(false) // Fullscreen mode (desktop)
const isMobileModalOpen = ref(false) // Mobile modal
const isMounted = ref(false) // Component is mounted and ready

// Map ref
const mapRef = ref(null)

// Storage keys
const STORAGE_KEY_VISIBLE = 'floating-map-visible'
const STORAGE_KEY_MINIMIZED = 'floating-map-minimized'

// Load state from localStorage
onMounted(() => {
  // Set mounted flag first
  isMounted.value = true

  // Then restore saved state after a delay to ensure DOM is ready
  nextTick(() => {
    setTimeout(() => {
      const savedVisible = localStorage.getItem(STORAGE_KEY_VISIBLE)
      const savedMinimized = localStorage.getItem(STORAGE_KEY_MINIMIZED)

      // Default to visible if no saved state
      if (savedVisible !== null) {
        isVisible.value = savedVisible === 'true'
      } else {
        isVisible.value = true // Show by default
      }

      if (savedMinimized !== null) {
        isMinimized.value = savedMinimized === 'true'
      }
    }, 200)
  })
})

// Save state to localStorage
watch(isVisible, (value) => {
  localStorage.setItem(STORAGE_KEY_VISIBLE, value.toString())
})

watch(isMinimized, (value) => {
  localStorage.setItem(STORAGE_KEY_MINIMIZED, value.toString())
  if (!value) {
    // When expanding, invalidate map size
    nextTick(() => {
      setTimeout(() => {
        mapRef.value?.invalidateMapSize()
      }, 100)
    })
  }
})

// Toggle functions
function toggleMinimize() {
  if (isMobile.value) {
    // On mobile, open modal instead
    isMobileModalOpen.value = true
  } else {
    isMinimized.value = !isMinimized.value
  }
}

function toggleFullscreen() {
  isFullscreen.value = !isFullscreen.value
  nextTick(() => {
    setTimeout(() => {
      mapRef.value?.invalidateMapSize()
    }, 100)
  })
}

function closeWidget() {
  isVisible.value = false
}

function openWidget() {
  if (isMobile.value) {
    isMobileModalOpen.value = true
  } else {
    isVisible.value = true
    isMinimized.value = false
  }
}

// Invalidate map when modal opens
watch(isMobileModalOpen, (isOpen) => {
  if (isOpen) {
    nextTick(() => {
      setTimeout(() => {
        mapRef.value?.invalidateMapSize()
      }, 100)
    })
  }
})

// Watch fullscreen changes
watch(isFullscreen, () => {
  nextTick(() => {
    setTimeout(() => {
      mapRef.value?.invalidateMapSize()
    }, 150)
  })
})

// Widget size classes
const widgetClasses = computed(() => {
  if (isFullscreen.value) {
    return 'w-[calc(100vw-2rem)] h-[calc(100vh-2rem)] bottom-4 right-4'
  }
  if (isMinimized.value) {
    return 'w-auto h-auto bottom-24 right-6'
  }
  return 'w-[400px] h-[500px] bottom-24 right-6'
})

const hasFeaturedStores = computed(() => {
  return props.stores.some(store => store.featured)
})

// Handle events from map
function handleMarkerClick(store) {
  emit('marker-click', store)
}

function handleMarkerHover(store) {
  emit('marker-hover', store)
}

defineExpose({
  openWidget,
  invalidateMapSize: () => mapRef.value?.invalidateMapSize?.(),
})
</script>

<template>
  <div>
    <!-- Desktop Floating Widget -->
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 translate-y-4"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-4"
    >
      <div
        v-show="isVisible && !isMobile"
        :class="widgetClasses"
        class="fixed z-50 transition-all duration-300"
        :style="isMinimized ? {} : undefined"
        :data-minimized="isMinimized"
      >
        <div
          :class="isMinimized
            ? 'bg-transparent border-0 shadow-none rounded-full'
            : 'bg-background border border-border rounded-lg shadow-2xl h-full'"
      >
        <!-- Minimized State (Button Only) -->
        <div v-if="isMinimized" class="p-0">
          <Button
            size="lg"
            class="rounded-full shadow-2xl h-14 w-14 p-0"
            @click="toggleMinimize"
            aria-label="Abrir mapa"
            title="Abrir mapa"
          >
            <Icon icon="lucide:map" class="size-6" />
          </Button>
        </div>

        <!-- Expanded State -->
        <div v-else class="h-full flex flex-col">
          <!-- Header -->
          <div class="flex items-center justify-between p-3 border-b bg-muted/30">
            <div class="flex items-center gap-2">
              <Icon icon="lucide:map-pin" class="size-4 text-primary" />
              <h3 class="font-semibold text-sm">{{ title }}</h3>
              <Badge
                v-if="hasFeaturedStores"
                class="bg-primary text-primary-foreground shadow-lg"
              >
                <Icon icon="lucide:star" class="size-3 mr-1" />
                Destaque
              </Badge>
            </div>
            <div class="flex items-center gap-1">
              <!-- Fullscreen Toggle -->
              <Button
                variant="ghost"
                size="icon"
                class="h-8 w-8"
                @click="toggleFullscreen"
              >
                <Icon
                  :icon="isFullscreen ? 'lucide:minimize' : 'lucide:maximize'"
                  class="size-4"
                />
              </Button>
              <!-- Minimize -->
              <Button
                variant="ghost"
                size="icon"
                class="h-8 w-8"
                @click="toggleMinimize"
              >
                <Icon icon="lucide:minus" class="size-4" />
              </Button>
              <!-- Close -->
              <Button
                variant="ghost"
                size="icon"
                class="h-8 w-8"
                @click="closeWidget"
              >
                <Icon icon="lucide:x" class="size-4" />
              </Button>
            </div>
          </div>

          <!-- Map Content -->
          <div class="flex-1 overflow-hidden">
            <StoreMap
              v-if="isMounted && isVisible && !isMinimized"
              ref="mapRef"
              :stores="stores"
              :hovered-store-id="hoveredStoreId"
              @marker-click="handleMarkerClick"
              @marker-hover="handleMarkerHover"
            />
          </div>
        </div>
        </div>
      </div>
    </Transition>

    <!-- Reopen Button (when closed on desktop) -->
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <Button
        v-if="!isVisible && !isMobile"
        size="lg"
        class="fixed bottom-24 right-6 z-50 rounded-full shadow-2xl h-14 w-14 p-0"
        @click="openWidget"
      >
        <Icon icon="lucide:map" class="size-6" />
      </Button>
    </Transition>

    <!-- Mobile Floating Button -->
    <Button
      v-if="isMobile"
      size="lg"
      class="fixed bottom-[calc(7rem+env(safe-area-inset-bottom))] right-4 z-50 rounded-full shadow-2xl h-14 w-14 p-0"
      @click="openWidget"
    >
      <Icon icon="lucide:map" class="size-6" />
    </Button>

    <!-- Mobile Modal -->
    <Dialog v-model:open="isMobileModalOpen">
      <DialogContent class="w-[calc(100vw-1rem)] max-w-md rounded-2xl p-0 gap-0 border shadow-2xl [&>button]:hidden">
        <div class="flex flex-col max-h-[88vh] overflow-hidden">
          <div class="flex justify-center pt-2 pb-1">
            <div class="h-1.5 w-12 rounded-full bg-muted-foreground/30" />
          </div>
          <!-- Header -->
          <div class="p-4 pb-3 border-b flex items-start justify-between bg-background/95 backdrop-blur-sm">
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-lg font-semibold leading-tight">
                  {{ title }}
                </h2>
                <Badge
                  v-if="hasFeaturedStores"
                  class="bg-primary text-primary-foreground shadow-lg w-fit"
                >
                  <Icon icon="lucide:star" class="size-3 mr-1" />
                  Destaque
                </Badge>
              </div>
              <p class="text-sm text-muted-foreground mt-1">
                {{ stores.length }} {{ stores.length === 1 ? 'loja encontrada' : 'lojas encontradas' }}
              </p>
            </div>
              <Button
                variant="ghost"
                size="icon"
                class="shrink-0 -mt-1"
                aria-label="Fechar mapa"
                @click="isMobileModalOpen = false"
              >
              <Icon icon="lucide:x" class="size-5" />
            </Button>
          </div>

          <!-- Map Content -->
          <div class="h-[min(62vh,520px)] p-3">
            <StoreMap
              v-if="isMounted && isMobileModalOpen"
              :stores="stores"
              :hovered-store-id="hoveredStoreId"
              @marker-click="handleMarkerClick"
              @marker-hover="handleMarkerHover"
            />
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </div>
</template>

<style scoped>
/* Ensure smooth transitions */
.transition-all {
  transition-property: all;
}
</style>
