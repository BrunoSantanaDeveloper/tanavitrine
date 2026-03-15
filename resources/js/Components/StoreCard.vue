<script setup>
import Badge from '@/Components/shadcn/ui/badge/Badge.vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import Card from '@/Components/shadcn/ui/card/Card.vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/shadcn/ui/avatar'
import { Icon } from '@iconify/vue'
import { router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import axios from 'axios'
import { formatWhatsAppNumber } from '@/utils/formatters'

const props = defineProps({
  store: {
    type: Object,
    required: true,
  },
  showFavoriteButton: {
    type: Boolean,
    default: true,
  },
})

const isFavorited = ref(props.store.is_favorited || false)
const isFavoriting = ref(false)
const currentImageIndex = ref(0)

// Get all images from store (assuming store has an 'images' array, fallback to single 'image')
const images = computed(() => {
  if (props.store.images && props.store.images.length > 0) {
    return props.store.images
  }
  // Fallback to single image or placeholder
  if (props.store.image) {
    return [props.store.image]
  }
  // Return placeholder
  return [null]
})

const currentImage = computed(() => images.value[currentImageIndex.value])
const storeDetailHref = computed(() => props.store.previewDetailUrl || `/loja/${props.store.slug}`)
const normalizedStoreType = computed(() => String(props.store.storeType || '').trim().toLowerCase())
const storeTypeTagLabel = computed(() => {
  const raw = normalizedStoreType.value
  if (raw === 'ambos')
    return 'Virtual / Física'
  if (raw === 'virtual' || raw === 'online')
    return 'Loja Virtual'
  if (raw === 'fisica' || raw === 'física')
    return 'Loja Física'
  return null
})
const showLocationTag = computed(() => {
  const raw = normalizedStoreType.value
  return raw !== 'virtual' && raw !== 'online'
})
const normalizedSubcategories = computed(() => {
  const raw = props.store.subcategory

  if (Array.isArray(raw)) {
    return raw
      .map(item => String(item || '').trim())
      .filter(Boolean)
  }

  if (typeof raw === 'string' && raw.trim()) {
    return [raw.trim()]
  }

  return []
})
const showSubcategoryTag = computed(() => {
  const raw = normalizedStoreType.value
  return (raw === 'virtual' || raw === 'online' || raw === 'ambos') && normalizedSubcategories.value.length > 0
})
const subcategoriesLabel = computed(() => {
  const items = normalizedSubcategories.value
  const raw = normalizedStoreType.value

  if (raw === 'ambos') {
    return items[0] || ''
  }

  if (items.length <= 2) {
    return items.join(', ')
  }

  return `${items.slice(0, 2).join(', ')} +${items.length - 2}`
})

const hasMultipleImages = computed(() => images.value.length > 1)
const touchStartX = ref(0)
const touchStartY = ref(0)
const isNavigatingToDetails = ref(false)
const imageAspectRatios = ref({})

const currentImageAspectRatio = computed(() => {
  const imageUrl = currentImage.value
  if (!imageUrl)
    return null

  return imageAspectRatios.value[imageUrl] ?? null
})

const adaptiveImageScaleClass = computed(() => {
  const aspectRatio = currentImageAspectRatio.value

  if (aspectRatio === null) {
    return 'scale-100 sm:scale-100 lg:scale-100'
  }

  // Wider images tend to expose bottom background more often, so apply stronger zoom.
  if (aspectRatio >= 0.82) {
    return 'scale-110 sm:scale-[1.18] lg:scale-[1.22]'
  }

  // Tall/near-card images already fill the frame; extra zoom causes unnecessary crop.
  return 'scale-100 sm:scale-100 lg:scale-100'
})

const adaptiveImagePositionClass = computed(() => {
  const aspectRatio = currentImageAspectRatio.value

  if (aspectRatio === null) {
    return 'object-center'
  }

  return aspectRatio >= 0.82 ? 'object-top' : 'object-center'
})

function nextImage() {
  if (hasMultipleImages.value) {
    currentImageIndex.value = (currentImageIndex.value + 1) % images.value.length
  }
}

function prevImage() {
  if (hasMultipleImages.value) {
    currentImageIndex.value = (currentImageIndex.value - 1 + images.value.length) % images.value.length
  }
}

function openStoreDetails() {
  if (isNavigatingToDetails.value) return
  isNavigatingToDetails.value = true

  router.visit(storeDetailHref.value, {
    onFinish: () => {
      // Short lock to prevent duplicate history entries on touch/click combo in mobile Chrome.
      setTimeout(() => {
        isNavigatingToDetails.value = false
      }, 250)
    },
  })
}

function handleCardContentClick(event) {
  const interactiveTarget = event.target?.closest?.('a, button, [role="button"]')
  if (interactiveTarget) return
  openStoreDetails()
}

function isMobileCardView() {
  return typeof window !== 'undefined' && window.matchMedia('(max-width: 639px)').matches
}

function handleImageTap() {
  if (!hasMultipleImages.value || !isMobileCardView()) return
  nextImage()
}

function onImageTouchStart(event) {
  const touch = event.changedTouches?.[0]
  if (!touch) return
  touchStartX.value = touch.clientX
  touchStartY.value = touch.clientY
}

function onImageTouchEnd(event) {
  if (!hasMultipleImages.value) return

  const touch = event.changedTouches?.[0]
  if (!touch) return

  const deltaX = touch.clientX - touchStartX.value
  const deltaY = touch.clientY - touchStartY.value
  const minSwipe = 40

  // Only treat as swipe when horizontal gesture is dominant.
  if (Math.abs(deltaX) < minSwipe || Math.abs(deltaX) <= Math.abs(deltaY)) return

  if (deltaX < 0) {
    nextImage()
  } else {
    prevImage()
  }
}

function onImageLoad(event) {
  const imageUrl = currentImage.value
  const target = event.target

  if (!imageUrl || !(target instanceof HTMLImageElement)) {
    return
  }

  if (!target.naturalWidth || !target.naturalHeight) {
    return
  }

  imageAspectRatios.value[imageUrl] = target.naturalWidth / target.naturalHeight
}

async function toggleFavorite() {
  // Check if user is logged in (you can pass this as prop or check auth state)
  if (!props.store.can_favorite) {
    router.visit('/login', {
      data: { redirect: window.location.pathname }
    })
    return
  }

  isFavoriting.value = true

  try {
    const response = await axios.post(`/loja/${props.store.slug}/favorite`)
    isFavorited.value = response.data.is_favorited
  } catch (error) {
    console.error('Error toggling favorite:', error)
  } finally {
    isFavoriting.value = false
  }
}

async function openWhatsApp() {
  const phone = formatWhatsAppNumber(props.store.whatsapp)
  const message = `Olá! Vi a vitrine de ${props.store.name} no TanaVitrine e gostaria de saber mais.`
  const url = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`
  window.open(url, '_blank')

  // Fire-and-forget tracking to keep Safari/iOS popup flow tied to user gesture.
  axios.post(`/loja/${props.store.slug}/track/whatsapp`).catch(() => {
    // Ignore tracking errors to avoid blocking WhatsApp navigation.
  })
}

async function shareStore() {
  try {
    await axios.post(`/loja/${props.store.slug}/track/share`)
  } catch (error) {
    // Continue even if tracking fails
  }

  if (navigator.share) {
    navigator.share({
      title: props.store.name,
      text: props.store.description,
      url: `${window.location.origin}${storeDetailHref.value}`
    }).catch(() => {})
  } else {
    // Fallback: copy to clipboard
    const url = `${window.location.origin}${storeDetailHref.value}`
    navigator.clipboard.writeText(url)
    alert('Link copiado para a área de transferência!')
  }
}
</script>

<template>
  <Card
    class="h-full overflow-hidden hover:shadow-lg transition-shadow duration-300"
    :class="{ 'ring-2 ring-primary/20': store.featured }"
  >
    <div class="grid h-full grid-cols-1 gap-0 sm:grid-cols-5 sm:items-stretch">
      <!-- Image Section with Carousel -->
      <div class="sm:col-span-2 relative group h-64 sm:h-auto sm:self-stretch sm:min-h-[320px] lg:min-h-[340px] overflow-hidden bg-muted">
        <!-- Placeholder quando não houver imagem -->
        <div
          v-if="!currentImage"
          class="absolute inset-0 w-full h-full bg-muted flex flex-col items-center justify-center"
        >
          <Icon icon="lucide:image-off" class="size-12 text-muted-foreground mb-2" />
          <p class="text-muted-foreground text-sm">Sem imagem</p>
        </div>

        <!-- Main Image -->
        <img
          v-else
          :src="currentImage"
          :alt="store.name"
          :class="[
            'absolute inset-0 block w-full h-full object-cover origin-top transition-opacity duration-300',
            adaptiveImagePositionClass,
            adaptiveImageScaleClass,
          ]"
          @load="onImageLoad"
          @click="handleImageTap"
          @touchstart.passive="onImageTouchStart"
          @touchend.passive="onImageTouchEnd"
        />

        <div class="absolute top-3 left-3 flex flex-wrap gap-2 z-10">
          <Badge
            v-if="store.featured"
            class="bg-primary text-primary-foreground shadow-lg"
          >
            <Icon icon="lucide:star" class="size-3 mr-1" />
            Destaque
          </Badge>
          <Badge
            v-if="store.is_verified"
            class="bg-teal-600 text-white shadow-lg"
          >
            <Icon icon="lucide:badge-check" class="size-3 mr-1" />
            Verificado
          </Badge>
        </div>

        <!-- Navigation Arrows (only show if multiple images) -->
        <template v-if="hasMultipleImages">
          <button
            @click.prevent.stop="prevImage"
            class="absolute left-2 top-1/2 -translate-y-1/2 rounded-full p-2 shadow-md transition-opacity z-10 bg-black/25 text-white backdrop-blur-sm opacity-90 sm:opacity-0 sm:bg-black/25 sm:text-white sm:backdrop-blur-sm sm:group-hover:opacity-100 sm:hover:bg-black/35"
            aria-label="Imagem anterior"
          >
            <Icon icon="lucide:chevron-left" class="size-5" />
          </button>
          <button
            @click.prevent.stop="nextImage"
            class="absolute right-2 top-1/2 -translate-y-1/2 rounded-full p-2 shadow-md transition-opacity z-10 bg-black/25 text-white backdrop-blur-sm opacity-90 sm:opacity-0 sm:bg-black/25 sm:text-white sm:backdrop-blur-sm sm:group-hover:opacity-100 sm:hover:bg-black/35"
            aria-label="Próxima imagem"
          >
            <Icon icon="lucide:chevron-right" class="size-5" />
          </button>

          <!-- Image Indicators -->
          <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5 z-10">
            <button
              v-for="(_, index) in images"
              :key="index"
              @click.prevent.stop="currentImageIndex = index"
              class="w-2 h-2 rounded-full transition-all"
              :class="currentImageIndex === index ? 'bg-white w-6' : 'bg-white/60'"
              :aria-label="`Ver imagem ${index + 1}`"
            />
          </div>
        </template>
      </div>
      <!-- Content Section -->
      <div class="sm:col-span-3 p-6 flex h-full flex-col justify-between sm:min-h-[320px] lg:min-h-[340px] cursor-pointer" @click="handleCardContentClick">
        <!-- Header -->
        <div>
          <div class="flex items-start justify-between mb-3">
            <div class="flex items-start gap-3 flex-1">
              <!-- Logo Avatar -->
              <Avatar v-if="store.logo" size="sm" shape="square" class="mt-1">
                <AvatarImage :src="store.logo" :alt="store.name" />
                <AvatarFallback>{{ store.name.substring(0, 2).toUpperCase() }}</AvatarFallback>
              </Avatar>

              <div class="flex-1">
                <p class="text-xs text-muted-foreground mb-1">Cod.: {{ store.code }}</p>
                <div class="flex flex-wrap gap-1 mb-2">
                  <template v-if="store.badge?.toLowerCase() === 'ambos'">
                    <Badge variant="secondary" class="text-xs">Atacado</Badge>
                    <Badge variant="secondary" class="text-xs">Varejo</Badge>
                  </template>
                  <Badge v-else variant="secondary" class="text-xs">
                    {{ store.badge }}
                  </Badge>
                  <Badge v-if="storeTypeTagLabel" variant="secondary" class="text-xs">
                    {{ storeTypeTagLabel }}
                  </Badge>
                </div>
                <h3 class="text-xl font-bold text-foreground mb-2 line-clamp-2 min-h-[3.5rem]">
                  {{ store.name }}
                </h3>
              </div>
            </div>
            <!-- Action Icons -->
            <div class="flex gap-2">
              <!--<button
                v-if="showFavoriteButton"
                @click="toggleFavorite"
                :disabled="isFavoriting"
                class="p-2 hover:bg-muted rounded-lg transition-colors"
                :class="{ 'opacity-50 cursor-not-allowed': isFavoriting }"
                :aria-label="isFavorited ? 'Remover dos favoritos' : 'Adicionar aos favoritos'"
                :title="isFavorited ? 'Remover dos favoritos' : 'Adicionar aos favoritos'"
              >
                <Icon
                  :icon="isFavorited ? 'lucide:heart' : 'lucide:heart'"
                  class="size-5"
                  :class="isFavorited ? 'text-red-500 fill-current' : 'text-muted-foreground'"
                />
              </button>-->
              <button
                @click.stop="shareStore"
                class="p-2 hover:bg-muted rounded-lg transition-colors"
                aria-label="Compartilhar"
                title="Compartilhar loja"
              >
                <Icon icon="lucide:share-2" class="size-5 text-muted-foreground" />
              </button>
            </div>
          </div>

          <!-- Info Tags -->
          <div class="flex flex-wrap gap-2 mb-3 text-sm">
            <div v-if="store.category" class="flex items-center gap-1 text-muted-foreground">
              <Icon icon="lucide:tag" class="size-4" />
              <span>{{ store.category }}</span>
            </div>
            <div v-if="showSubcategoryTag" class="flex items-center gap-1 text-muted-foreground">
              <Icon icon="lucide:tags" class="size-4" />
              <span>{{ subcategoriesLabel }}</span>
            </div>
            <div v-if="store.location && showLocationTag" class="flex items-center gap-1 text-muted-foreground">
              <Icon icon="lucide:map-pin" class="size-4" />
              <span>{{ store.location }}</span>
            </div>
            <div v-if="store.minOrder" class="flex items-center gap-1 text-muted-foreground">
              <Icon icon="lucide:package" class="size-4" />
              <span>{{ store.minOrder }}</span>
            </div>
          </div>

          <!-- Description -->
          <p class="text-sm text-muted-foreground line-clamp-3 mb-4">
            {{ store.description }}
          </p>
        </div>

        <!-- Footer -->
        <div>
          <div class="flex items-center gap-3 pt-4 border-t border-border">
            <Button
              size="sm"
              variant="outline"
              class="flex-1 cursor-pointer"
              @click.stop="openStoreDetails"
            >
              <Icon icon="lucide:eye" class="size-4 mr-1" />
              Detalhes
            </Button>
            <Button
              size="sm"
              class="flex-1 bg-green-600 hover:bg-green-700 text-white cursor-pointer"
              @click.stop="openWhatsApp"
            >
              <Icon icon="lucide:message-circle" class="size-4 mr-1" />
              WhatsApp
            </Button>
          </div>
        </div>
      </div>
    </div>
  </Card>

</template>
