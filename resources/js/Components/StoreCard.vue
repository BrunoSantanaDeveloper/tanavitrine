<script setup>
import Badge from '@/Components/shadcn/ui/badge/Badge.vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import Card from '@/Components/shadcn/ui/card/Card.vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/shadcn/ui/avatar'
import { Icon } from '@iconify/vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import axios from 'axios'
import { trackWhatsAppAdsConversion } from '@/services/googleAnalytics'
import { formatWhatsAppNumber } from '@/utils/formatters'
import { useImageAutoplay } from '@/Composables/useImageAutoplay'

const props = defineProps({
  store: {
    type: Object,
    required: true,
  },
  showFavoriteButton: {
    type: Boolean,
    default: true,
  },
  weeklyFeatured: {
    type: Boolean,
    default: false,
  },
})

const isFavorited = ref(props.store.is_favorited || false)
const isFavoriting = ref(false)
const currentImageIndex = ref(0)
const imageContainer = ref(null)

// Get all images from store (assuming store has an 'images' array, fallback to single 'image')
const images = computed(() => {
  if (props.store.images && props.store.images.length > 0) {
    const photos = props.store.images.filter(Boolean)
    if (photos.length) return photos
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
const isManufacturerStore = computed(() => {
  const value = props.store?.is_manufacturer

  if (typeof value === 'boolean') return value
  if (value === 1 || value === '1') return true
  if (typeof value === 'string') return value.toLowerCase() === 'true'

  return false
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

const storeInfoLabel = computed(() => [
  props.store.badge?.toLowerCase() === 'ambos' ? 'Atacado e varejo' : props.store.badge,
  props.store.category,
  subcategoriesLabel.value,
].filter(Boolean).join(' · '))

const hasMultipleImages = computed(() => images.value.length > 1)
const { moveImage } = useImageAutoplay(imageContainer, images, currentImageIndex)
const touchStartX = ref(0)
const touchStartY = ref(0)
const isNavigatingToDetails = ref(false)
let lastImageSwipeAt = 0

function nextImage() {
  moveImage(1)
}

function prevImage() {
  moveImage(-1)
}

let lastStoreOpenClickAt = 0

function trackStoreOpenClick() {
  if (props.store.previewDetailUrl) return

  const now = Date.now()
  if (now - lastStoreOpenClickAt < 750) return
  lastStoreOpenClickAt = now

  axios.post(`/loja/${props.store.slug}/track/open`).catch(() => {
    // Tracking failures must not prevent opening the store.
  })
}

function openStoreDetails() {
  if (isNavigatingToDetails.value) return
  isNavigatingToDetails.value = true
  trackStoreOpenClick()

  router.visit(storeDetailHref.value, {
    onFinish: () => {
      // Short lock to prevent duplicate history entries on touch/click combo in mobile Chrome.
      setTimeout(() => {
        isNavigatingToDetails.value = false
      }, 250)
    },
  })
}

function handleImageTap() {
  if (Date.now() - lastImageSwipeAt < 500) return
  openStoreDetails()
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

  lastImageSwipeAt = Date.now()

  if (deltaX < 0) {
    nextImage()
  } else {
    prevImage()
  }
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
  const storeUrl = `${window.location.origin}${storeDetailHref.value}`
  const message = `Olá! Vi a vitrine da *${props.store.name}* no Tá na Vitrine e gostaria de saber mais sobre os produtos e condições. Link da vitrine: ${storeUrl}`
  const url = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`
  trackWhatsAppAdsConversion()
  window.open(url, '_blank')

  // Fire-and-forget tracking to keep Safari/iOS popup flow tied to user gesture.
  axios.post(`/loja/${props.store.slug}/track/whatsapp`).catch(() => {
    // Ignore tracking errors to avoid blocking WhatsApp navigation.
  })
}

</script>

<template>
  <Card class="flex h-full min-w-0 flex-col border-0 bg-transparent shadow-none">
    <div
      ref="imageContainer"
      class="store-card-visual group relative aspect-[16/10] shrink-0 overflow-hidden rounded-xl bg-muted"
      @touchstart.passive="onImageTouchStart"
      @touchend.passive="onImageTouchEnd"
    >
      <button
        type="button"
        class="absolute inset-0 h-full w-full cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-teal-700"
        :aria-label="`Ver vitrine de ${store.name}`"
        @click="handleImageTap"
      >
        <Transition name="store-photo">
          <img
            v-if="currentImage"
            :key="currentImage"
            :src="currentImage"
            :alt="store.name"
            loading="lazy"
            draggable="false"
            class="store-card-photo absolute inset-0 h-full w-full object-cover object-center"
          >
          <span v-else class="flex h-full flex-col items-center justify-center gap-2 text-muted-foreground">
            <Icon icon="lucide:image-off" class="size-10" aria-hidden="true" />
            <span class="text-xs">Sem imagem</span>
          </span>
        </Transition>
      </button>

      <div class="pointer-events-none absolute inset-x-0 bottom-0 h-28 bg-gradient-to-t from-black/80 via-black/25 to-transparent" />
      <div class="pointer-events-none absolute left-3 right-3 top-3 flex items-start justify-between gap-2">
        <div class="flex min-w-0 flex-wrap gap-1.5">
          <Badge v-if="weeklyFeatured || store.featured" class="bg-primary text-primary-foreground text-[10px] shadow-sm">
            <Icon icon="lucide:star" class="mr-1 size-3" aria-hidden="true" />
            {{ weeklyFeatured ? 'Destaque da semana' : 'Destaque' }}
          </Badge>
          <Badge v-if="store.is_verified" class="bg-teal-700 text-white text-[10px] shadow-sm">
            <Icon icon="lucide:badge-check" class="mr-1 size-3" aria-hidden="true" />
            Verificado
          </Badge>
        </div>
        <Badge v-if="storeTypeTagLabel" class="shrink-0 border-0 bg-black/65 text-[10px] text-white shadow-sm backdrop-blur-sm">
          {{ storeTypeTagLabel }}
        </Badge>
      </div>
      <div class="absolute bottom-3 left-3 right-3 flex items-center justify-between gap-3 text-white">
        <Link
          :href="storeDetailHref"
          class="flex min-w-0 flex-1 items-center gap-2 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-transparent"
          @click.stop="trackStoreOpenClick"
        >
          <Avatar size="sm" shape="circle" class="size-9 shrink-0 border-2 border-white/90 bg-white shadow-sm">
            <AvatarImage v-if="store.logo" :src="store.logo" :alt="store.name" />
            <AvatarFallback class="flex h-full w-full items-center justify-center bg-white text-xs font-semibold text-teal-900">{{ store.name.substring(0, 2).toUpperCase() }}</AvatarFallback>
          </Avatar>
          <div class="min-w-0 flex-1 drop-shadow-sm">
            <h3 class="truncate text-sm font-bold sm:text-base" :title="store.name">{{ store.name }}</h3>
            <div v-if="storeInfoLabel || isManufacturerStore" class="mt-0.5 flex min-w-0 items-center gap-1.5">
              <p v-if="storeInfoLabel" class="line-clamp-2 min-w-0 text-[10px] leading-snug text-white/90 sm:text-xs" :title="storeInfoLabel">{{ storeInfoLabel }}</p>
              <Icon v-if="isManufacturerStore" icon="lucide:factory" class="size-3.5 shrink-0 text-white/90" aria-label="Fabricação própria" />
            </div>
          </div>
        </Link>
        <Button
          v-if="store.whatsapp"
          type="button"
          size="sm"
          class="h-10 shrink-0 cursor-pointer gap-1.5 rounded-lg bg-green-600 px-3 text-xs text-white shadow-sm hover:bg-green-700 focus-visible:ring-white"
          :aria-label="`Falar com ${store.name} no WhatsApp`"
          @click.stop="openWhatsApp"
        >
          <Icon icon="lucide:message-circle" class="size-3.5" aria-hidden="true" />
          WhatsApp
        </Button>
      </div>

      <template v-if="hasMultipleImages">
        <button
          type="button"
          class="absolute left-2 top-1/2 flex size-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/30 text-white backdrop-blur-sm transition-opacity sm:opacity-0 sm:group-hover:opacity-100 sm:group-focus-within:opacity-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
          aria-label="Imagem anterior"
          @click.prevent.stop="prevImage"
        >
          <Icon icon="lucide:chevron-left" class="size-5" aria-hidden="true" />
        </button>
        <button
          type="button"
          class="absolute right-2 top-1/2 flex size-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/30 text-white backdrop-blur-sm transition-opacity sm:opacity-0 sm:group-hover:opacity-100 sm:group-focus-within:opacity-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
          aria-label="Próxima imagem"
          @click.prevent.stop="nextImage"
        >
          <Icon icon="lucide:chevron-right" class="size-5" aria-hidden="true" />
        </button>
      </template>
    </div>

  </Card>
</template>

<style scoped>
.store-card-photo {
  transition: transform 250ms ease-out;
}

.store-photo-enter-active,
.store-photo-leave-active {
  transition: opacity 350ms ease, transform 250ms ease-out;
}

.store-photo-enter-from,
.store-photo-leave-to {
  opacity: 0;
}

@media (min-width: 1024px) {
  .store-card-visual {
    transition: transform 250ms ease-out, box-shadow 250ms ease-out;
  }

  .store-card-visual:hover,
  .store-card-visual:focus-within {
    box-shadow: 0 12px 28px -8px rgb(15 118 110 / 35%), 0 0 0 2px rgb(15 118 110 / 30%);
  }

  @media (prefers-reduced-motion: no-preference) {
    .store-card-visual:hover,
    .store-card-visual:focus-within {
      transform: translateY(-4px);
    }

    .store-card-visual:hover .store-card-photo,
    .store-card-visual:focus-within .store-card-photo {
      transform: scale(1.06);
    }
  }
}

@media (prefers-reduced-motion: reduce) {
  .store-card-visual,
  .store-card-photo,
  .store-photo-enter-active,
  .store-photo-leave-active {
    transition: none;
  }
}
</style>
