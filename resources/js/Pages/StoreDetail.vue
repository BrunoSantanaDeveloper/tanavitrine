<script setup>
import Badge from '@/Components/shadcn/ui/badge/Badge.vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import Card from '@/Components/shadcn/ui/card/Card.vue'
import { useSeoMetaTags } from '@/Composables/useSeoMetaTags'
import { Dialog, DialogContent } from '@/Components/shadcn/ui/dialog'
import FloatingMap from '@/Components/FloatingMap.vue'
import WebLayout from '@/Layouts/WebLayout.vue'
import { formatWhatsAppNumber } from '@/utils/formatters'
import { Icon } from '@iconify/vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import { computed, ref, onMounted, onBeforeUnmount, nextTick } from 'vue'

const props = defineProps({
  store: {
    type: Object,
    required: true,
  },
  canLogin: {
    type: Boolean,
  },
  canRegister: {
    type: Boolean,
  },
})

function truncateText(value, maxLength = 160) {
  const normalizedValue = String(value || '').trim().replace(/\s+/g, ' ')
  if (!normalizedValue)
    return ''

  if (normalizedValue.length <= maxLength)
    return normalizedValue

  return `${normalizedValue.slice(0, maxLength - 1)}…`
}

function toAbsoluteUrl(path) {
  const fallbackBaseUrl = 'https://tanavitrine.com.br'
  const baseUrl = typeof window !== 'undefined' ? window.location.origin : fallbackBaseUrl

  if (!path)
    return `${baseUrl}/images/og.webp`

  if (String(path).startsWith('http://') || String(path).startsWith('https://'))
    return path

  const normalizedPath = String(path).startsWith('/') ? String(path) : `/${String(path)}`
  return `${baseUrl}${normalizedPath}`
}

const storePageTitle = computed(() => {
  const storeName = props.store?.name || 'Loja'
  return `${storeName} | Tá na Vitrine`
})

const storePageDescription = computed(() => {
  const fallbackDescription = `Conheça ${props.store?.name || 'esta loja'} na Tá na Vitrine e entre em contato direto para atacado e varejo.`
  return truncateText(props.store?.description || fallbackDescription)
})

const storePageImage = computed(() => {
  const logo = props.store?.logo
  const firstImage = props.store?.images?.[0]?.url
  return toAbsoluteUrl(logo || firstImage || '/images/og.webp')
})

const storePageUrl = computed(() => {
  if (typeof window !== 'undefined')
    return window.location.href

  return toAbsoluteUrl(`/loja/${props.store?.slug || ''}`)
})

useSeoMetaTags({
  title: storePageTitle,
  description: storePageDescription,
  robots: 'index, follow',
  ogTitle: storePageTitle,
  ogDescription: storePageDescription,
  ogType: 'website',
  ogImage: storePageImage,
  ogUrl: storePageUrl,
  twitterTitle: storePageTitle,
  twitterDescription: storePageDescription,
  twitterImage: storePageImage,
  twitterCard: 'summary_large_image',
})

const normalizedStoreType = computed(() => String(props.store?.storeType || '').trim().toLowerCase())
const normalizedSaleType = computed(() =>
  String(props.store?.saleType || '')
    .trim()
    .toLowerCase(),
)
const saleTypeDisplayLabel = computed(() => {
  if (normalizedSaleType.value === 'ambos')
    return 'Atacado / Varejo'
  return props.store?.saleType || ''
})
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
const isVirtualOnlyStore = computed(() => {
  const raw = normalizedStoreType.value
  return raw === 'virtual' || raw === 'online'
})
const fullAddressDisplay = computed(() => {
  return props.store?.full_address || props.store?.location || ''
})
const locationSummaryDisplay = computed(() => {
  return props.store?.location || fullAddressDisplay.value || ''
})
const storesForMap = computed(() => {
  if (isVirtualOnlyStore.value) return []
  if (props.store.show_on_map !== true) return []
  if (!props.store.latitude || !props.store.longitude) return []

  return [
    {
      id: props.store.id,
      code: props.store.code,
      slug: props.store.slug,
      name: props.store.name,
      category: props.store.category,
      description: props.store.description,
      featured: props.store.featured,
      location: fullAddressDisplay.value || props.store.location,
      latitude: props.store.latitude,
      longitude: props.store.longitude,
      logo: props.store.logo,
      image: props.store.images?.[0]?.url || null,
      url: `/loja/${props.store.slug}`,
    },
  ]
})

const showBackToTop = ref(false)
const lastScrollTop = ref(0)
const activeSectionTab = ref('sobre')
const showAllCollections = ref(false)
const selectedCollectionId = ref(null)
const selectedCollectionImageIndex = ref(0)
const floatingMapRef = ref(null)
const selectedCollectionPreviewRef = ref(null)

function handleBackToTopVisibility() {
  const scrollTop = window.scrollY || document.documentElement.scrollTop || document.body.scrollTop || 0
  const delta = scrollTop - lastScrollTop.value

  if (scrollTop <= 180) {
    showBackToTop.value = false
    lastScrollTop.value = Math.max(scrollTop, 0)
    return
  }

  if (delta > 6) {
    showBackToTop.value = true
  } else if (delta < -6) {
    showBackToTop.value = false
  }

  lastScrollTop.value = Math.max(scrollTop, 0)
}

function scrollToTop() {
  // Force scroll on the main window (helps keep behavior stable across layout changes).
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

onMounted(() => {
  if (hasCollections.value && featuredCollection.value) {
    selectedCollectionId.value = featuredCollection.value.id
  }
  window.addEventListener('scroll', handleBackToTopVisibility, { passive: true })
  document.addEventListener('scroll', handleBackToTopVisibility, { passive: true, capture: true })
  window.addEventListener('keydown', handleLightboxKeydown)
  handleBackToTopVisibility()
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', handleBackToTopVisibility)
  document.removeEventListener('scroll', handleBackToTopVisibility, true)
  window.removeEventListener('keydown', handleLightboxKeydown)
})

// Função para extrair ID do vídeo do YouTube/Vimeo
function extractVideoId(url) {
  if (!url)
    return null

  // YouTube regex
  const youtubeRegex = /(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/
  const youtubeMatch = url.match(youtubeRegex)
  if (youtubeMatch && youtubeMatch[1]) {
    return { platform: 'youtube', id: youtubeMatch[1] }
  }

  // Vimeo regex
  const vimeoRegex = /vimeo\.com\/(?:.*\/)?(\d+)/
  const vimeoMatch = url.match(vimeoRegex)
  if (vimeoMatch && vimeoMatch[1]) {
    return { platform: 'vimeo', id: vimeoMatch[1] }
  }

  return null
}

function getVideoEmbedUrl(url) {
  if (!url)
    return null

  // Se for uma URL externa (YouTube/Vimeo)
  if (url.startsWith('http')) {
    const videoData = extractVideoId(url)
    if (videoData) {
      if (videoData.platform === 'youtube') {
        return `https://www.youtube.com/embed/${videoData.id}`
      } else if (videoData.platform === 'vimeo') {
        return `https://player.vimeo.com/video/${videoData.id}`
      }
    }
  }

  return null
}

// Combina vídeo + imagens na galeria
const mediaItems = computed(() => {
  const items = []

  // Adiciona vídeo como primeiro item se existir
  if (props.store.video_url) {
    const embedUrl = getVideoEmbedUrl(props.store.video_url)

    if (embedUrl) {
      // Vídeo do YouTube/Vimeo
      items.push({
        type: 'video-embed',
        url: embedUrl,
      })
    } else if (!props.store.video_url.startsWith('http')) {
      // Vídeo hospedado
      items.push({
        type: 'video-upload',
        url: `/storage/${props.store.video_url}`,
      })
    }
  }

  // Adiciona imagens
  if (props.store.images && props.store.images.length > 0) {
    props.store.images.forEach(img => {
      items.push({
        type: 'image',
        url: img.url,
      })
    })
  }

  // Placeholder se não tiver nenhuma mídia
  if (items.length === 0) {
    return [{ type: 'placeholder' }]
  }

  return items
})

const scrollPosition = ref(0)
const carouselContainer = ref(null)
const isFavorited = ref(props.store.is_favorited || false)
const isFavoriting = ref(false)
const isGalleryLightboxOpen = ref(false)
const topGalleryImageIndex = ref(0)
const lightboxSource = ref('top')
const lightboxTouchStartX = ref(0)
const lightboxTouchEndX = ref(0)
const isLightboxZoomed = ref(false)
const topGalleryMedia = computed(() => mediaItems.value
  .map((item, index) => ({ ...item, originalIndex: index }))
  .filter(item => item.type !== 'placeholder'))
const hasCollections = computed(() => props.store.has_collections === true && Array.isArray(props.store.collections) && props.store.collections.length > 0)
const sectionTabs = computed(() => {
  const tabs = [{ id: 'sobre', icon: 'lucide:file-text' }]
  if (hasCollections.value) {
    tabs.push({ id: 'galeria', icon: 'lucide:images' })
  }
  tabs.push({ id: 'infos', icon: 'lucide:circle-check-big' })
  return tabs
})
const featuredCollection = computed(() => {
  if (!hasCollections.value) return null
  const items = props.store.collections
  return items.find(collection => collection.is_featured) || items[0] || null
})
const secondaryCollections = computed(() => {
  if (!hasCollections.value || !featuredCollection.value) return []
  return props.store.collections.filter(collection => collection.id !== featuredCollection.value.id)
})
const visibleSecondaryCollections = computed(() => (
  showAllCollections.value ? secondaryCollections.value : secondaryCollections.value.slice(0, 4)
))
const selectedCollection = computed(() => {
  if (!hasCollections.value) return null
  return props.store.collections.find(collection => collection.id === selectedCollectionId.value) || featuredCollection.value
})
const selectedCollectionImages = computed(() => selectedCollection.value?.photos ?? [])
const selectedCollectionMainImage = computed(() =>
  selectedCollectionImages.value[selectedCollectionImageIndex.value] ?? selectedCollectionImages.value[0] ?? null
)
const hasMinOrder = computed(() => {
  const value = props.store?.minOrder
  if (value === null || value === undefined) return false
  const normalized = String(value).trim()
  if (!normalized) return false

  // If it's numeric, only show when greater than zero.
  const numeric = Number(normalized.replace(',', '.'))
  if (!Number.isNaN(numeric)) return numeric > 0

  return true
})
const activeLightboxItems = computed(() => {
  if (lightboxSource.value === 'collection') {
    return selectedCollectionImages.value.map(url => ({ type: 'image', url }))
  }
  return topGalleryMedia.value
})
const activeLightboxTitle = computed(() => {
  if (lightboxSource.value === 'collection') {
    return selectedCollection.value?.name || 'Galeria'
  }
  return `${props.store.name} • Mídias Destaques`
})
const activeLightboxIndex = computed({
  get: () => (lightboxSource.value === 'collection' ? selectedCollectionImageIndex.value : topGalleryImageIndex.value),
  set: (value) => {
    if (lightboxSource.value === 'collection') {
      selectedCollectionImageIndex.value = value
    } else {
      topGalleryImageIndex.value = value
    }
  },
})
const activeLightboxMainItem = computed(() =>
  activeLightboxItems.value[activeLightboxIndex.value] ?? activeLightboxItems.value[0] ?? null
)
const canZoomActiveItem = computed(() => activeLightboxMainItem.value?.type === 'image')

// Format subcategories
const formattedSubcategories = computed(() => {
  const subcategory = props.store.subcategory
  if (!subcategory) return ''

  // If it's an array, join with ", "
  if (Array.isArray(subcategory)) {
    return subcategory.join(', ')
  }

  // If it's a string, return it directly
  return subcategory
})

async function openLocation() {
  const mapsUrl = props.store.google_maps_url
  const latitude = props.store.latitude
  const longitude = props.store.longitude

  if (mapsUrl) {
    window.open(mapsUrl, '_blank')
    trackMapClick()
    return
  }

  if (latitude && longitude) {
    const destination = encodeURIComponent(`${latitude},${longitude}`)
    window.open(`https://www.google.com/maps/dir/?api=1&destination=${destination}`, '_blank')
    trackMapClick()
    return
  }

  const query = encodeURIComponent(fullAddressDisplay.value || props.store.name)
  window.open(`https://www.google.com/maps/search/?api=1&query=${query}`, '_blank')
  trackMapClick()
}

function openMapWidget() {
  trackMapClick()
  floatingMapRef.value?.openWidget?.()
}

function openWhatsApp() {
  const phone = formatWhatsAppNumber(props.store.whatsapp)
  const message = `Olá! Vi a vitrine de ${props.store.name} no Tá na Vitrine e gostaria de saber mais.`
  const url = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`
  window.open(url, '_blank')
  trackWhatsAppClick()
}

function openWebsite() {
  window.open(props.store.website, '_blank')
  trackWebsiteClick()
}

async function toggleFavorite() {
  // Check if user is logged in
  if (!props.canLogin) {
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
    alert('Erro ao favoritar. Tente novamente.')
  } finally {
    isFavoriting.value = false
  }
}

async function shareStore() {
  // Track share
  try {
    await axios.post(`/loja/${props.store.slug}/track/share`)
  } catch (error) {
    // Continue even if tracking fails
  }

  if (navigator.share) {
    navigator.share({
      title: props.store.name,
      text: props.store.description,
      url: window.location.href
    }).catch(() => {})
  } else {
    // Fallback: copy to clipboard
    navigator.clipboard.writeText(window.location.href)
    alert('Link copiado para a área de transferência!')
  }
}

async function shareOnFacebook() {
  await trackShare()
  const url = encodeURIComponent(window.location.href)
  window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank', 'width=600,height=400')
}

async function shareOnTwitter() {
  await trackShare()
  const url = encodeURIComponent(window.location.href)
  const text = encodeURIComponent(`Confira ${props.store.name} no Tá na Vitrine!`)
  window.open(`https://twitter.com/intent/tweet?url=${url}&text=${text}`, '_blank', 'width=600,height=400')
}

async function copyLink() {
  await trackShare()
  try {
    await navigator.clipboard.writeText(window.location.href)
    alert('Link copiado para a área de transferência!')
  } catch (error) {
    console.error('Error copying to clipboard:', error)
    alert('Erro ao copiar link. Tente novamente.')
  }
}

async function trackShare() {
  try {
    await axios.post(`/loja/${props.store.slug}/track/share`)
  } catch (error) {
    // Continue even if tracking fails
  }
}

async function trackMapClick() {
  try {
    await axios.post(`/loja/${props.store.slug}/track/map`)
  } catch (error) {
    // Continue even if tracking fails
  }
}

async function trackWhatsAppClick() {
  try {
    await axios.post(`/loja/${props.store.slug}/track/whatsapp`)
  } catch (error) {
    // Continue even if tracking fails
  }
}

async function trackWebsiteClick() {
  try {
    await axios.post(`/loja/${props.store.slug}/track/website`)
  } catch (error) {
    // Continue even if tracking fails
  }
}

async function trackInstagramClick() {
  try {
    await axios.post(`/loja/${props.store.slug}/track/instagram`)
  } catch (error) {
    // Continue even if tracking fails
  }
}

async function trackFacebookClick() {
  try {
    await axios.post(`/loja/${props.store.slug}/track/facebook`)
  } catch (error) {
    // Continue even if tracking fails
  }
}

async function trackTikTokClick() {
  try {
    await axios.post(`/loja/${props.store.slug}/track/tiktok`)
  } catch (error) {
    // Continue even if tracking fails
  }
}

function scrollNext() {
  if (carouselContainer.value) {
    const containerWidth = carouselContainer.value.offsetWidth
    // 1 image on mobile (< 768px), 3 on desktop
    const isMobile = window.innerWidth < 768
    const imagesPerView = isMobile ? 1 : 3
    const imageWidth = containerWidth / imagesPerView

    scrollPosition.value = Math.min(
      scrollPosition.value + imageWidth,
      carouselContainer.value.scrollWidth - containerWidth
    )
    carouselContainer.value.scrollTo({
      left: scrollPosition.value,
      behavior: 'smooth'
    })
  }
}

function scrollPrev() {
  if (carouselContainer.value) {
    const containerWidth = carouselContainer.value.offsetWidth
    // 1 image on mobile (< 768px), 3 on desktop
    const isMobile = window.innerWidth < 768
    const imagesPerView = isMobile ? 1 : 3
    const imageWidth = containerWidth / imagesPerView

    scrollPosition.value = Math.max(scrollPosition.value - imageWidth, 0)
    carouselContainer.value.scrollTo({
      left: scrollPosition.value,
      behavior: 'smooth'
    })
  }
}

function openTopGalleryLightboxByMediaIndex(mediaIndex) {
  const imageIndex = topGalleryMedia.value.findIndex(item => item.originalIndex === mediaIndex)
  if (imageIndex === -1) return
  lightboxSource.value = 'top'
  topGalleryImageIndex.value = imageIndex
  isLightboxZoomed.value = false
  isGalleryLightboxOpen.value = true
}

function selectCollection(collectionId) {
  selectedCollectionId.value = collectionId
  selectedCollectionImageIndex.value = 0

  nextTick(() => {
    const element = selectedCollectionPreviewRef.value
    if (!element) return

    const offset = 120
    const top = element.getBoundingClientRect().top + window.scrollY - offset
    window.scrollTo({ top, behavior: 'smooth' })
  })
}

function openCollectionLightbox(index = selectedCollectionImageIndex.value) {
  if (!selectedCollectionImages.value.length) return
  lightboxSource.value = 'collection'
  selectedCollectionImageIndex.value = index
  isLightboxZoomed.value = false
  isGalleryLightboxOpen.value = true
}

function nextSelectedCollectionImage() {
  if (!selectedCollectionImages.value.length) return
  selectedCollectionImageIndex.value = (selectedCollectionImageIndex.value + 1) % selectedCollectionImages.value.length
}

function prevSelectedCollectionImage() {
  if (!selectedCollectionImages.value.length) return
  selectedCollectionImageIndex.value = (selectedCollectionImageIndex.value - 1 + selectedCollectionImages.value.length) % selectedCollectionImages.value.length
}

function closeGalleryLightbox() {
  isLightboxZoomed.value = false
  isGalleryLightboxOpen.value = false
}

function nextGalleryImage() {
  if (!activeLightboxItems.value.length) return
  isLightboxZoomed.value = false
  activeLightboxIndex.value = (activeLightboxIndex.value + 1) % activeLightboxItems.value.length
}

function prevGalleryImage() {
  if (!activeLightboxItems.value.length) return
  isLightboxZoomed.value = false
  activeLightboxIndex.value = (activeLightboxIndex.value - 1 + activeLightboxItems.value.length) % activeLightboxItems.value.length
}

function handleLightboxKeydown(event) {
  if (!isGalleryLightboxOpen.value) return

  if (event.key === 'Escape') {
    closeGalleryLightbox()
    return
  }

  if (event.key === 'ArrowRight') {
    nextGalleryImage()
    return
  }

  if (event.key === 'ArrowLeft') {
    prevGalleryImage()
  }
}

function onLightboxTouchStart(event) {
  lightboxTouchStartX.value = event.changedTouches[0]?.clientX ?? 0
  lightboxTouchEndX.value = lightboxTouchStartX.value
}

function onLightboxTouchMove(event) {
  lightboxTouchEndX.value = event.changedTouches[0]?.clientX ?? lightboxTouchEndX.value
}

function onLightboxTouchEnd() {
  const deltaX = lightboxTouchEndX.value - lightboxTouchStartX.value
  const minSwipeDistance = 50

  if (Math.abs(deltaX) < minSwipeDistance) return

  if (deltaX < 0) {
    nextGalleryImage()
  } else {
    prevGalleryImage()
  }
}

function toggleLightboxZoom() {
  if (!canZoomActiveItem.value) return
  isLightboxZoomed.value = !isLightboxZoomed.value
}

function scrollToSection(sectionId) {
  activeSectionTab.value = sectionId
  const element = document.getElementById(`store-section-${sectionId}`)
  if (!element) return

  const offset = 120
  const top = element.getBoundingClientRect().top + window.scrollY - offset
  window.scrollTo({ top, behavior: 'smooth' })
}
</script>

<template>
  <WebLayout :can-login="canLogin" :can-register="canRegister" :showFloatingWhatsApp="false">
    <div class="min-h-screen bg-background">
      <!-- Photo Carousel Section -->
      <section class="bg-muted/30 py-2">
        <div class="w-full">
          <!-- Carousel Container -->
          <div class="relative">
            <!-- Media Grid (1 column on mobile, 3 on desktop) -->
            <div
              ref="carouselContainer"
              class="flex gap-2 overflow-x-hidden scroll-smooth"
              style="scroll-snap-type: x mandatory;"
            >
              <div
                v-for="(item, index) in mediaItems"
                :key="index"
                class="flex-shrink-0 relative w-full md:w-[calc(33.333%-0.5rem)]"
                style="scroll-snap-align: start;"
              >
                <div class="relative aspect-[4/3] rounded-lg overflow-hidden bg-black">
                  <!-- Placeholder -->
                  <div
                    v-if="item.type === 'placeholder'"
                    class="w-full h-full flex flex-col items-center justify-center bg-muted"
                  >
                    <Icon icon="lucide:image-off" class="size-16 text-muted-foreground mb-3" />
                    <p class="text-muted-foreground text-sm">Sem mídia cadastrada</p>
                  </div>

                  <!-- Vídeo Embed (YouTube/Vimeo) -->
                  <iframe
                    v-else-if="item.type === 'video-embed'"
                    :src="item.url"
                    class="w-full h-full"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                  />

                  <!-- Vídeo Upload -->
                  <video
                    v-else-if="item.type === 'video-upload'"
                    :src="item.url"
                    class="w-full h-full object-cover"
                    controls
                    preload="metadata"
                  >
                    Seu navegador não suporta a reprodução de vídeos.
                  </video>

                  <!-- Imagem -->
                  <img
                    v-else
                    :src="item.url"
                    :alt="`${store.name} - Foto ${index + 1}`"
                    class="w-full h-full object-cover"
                  >

                  <button
                    v-if="item.type !== 'placeholder'"
                    type="button"
                    class="absolute inset-0 z-10"
                    :aria-label="`Abrir mídia ${index + 1}`"
                    @click="openTopGalleryLightboxByMediaIndex(index)"
                  />
                </div>
              </div>
            </div>

            <!-- Navigation Arrows -->
            <button
              @click="scrollPrev"
              class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white rounded-full p-3 shadow-lg transition-all z-10"
              aria-label="Imagens anteriores"
            >
              <Icon icon="lucide:chevron-left" class="size-6 text-foreground" />
            </button>
            <button
              @click="scrollNext"
              class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white rounded-full p-3 shadow-lg transition-all z-10"
              aria-label="Próximas imagens"
            >
              <Icon icon="lucide:chevron-right" class="size-6 text-foreground" />
            </button>

            <!-- Top Right Actions -->
            <div class="absolute top-4 right-4 flex items-center gap-2 z-10">
              <!-- Favorite Button
              <button
                @click="toggleFavorite"
                :disabled="isFavoriting"
                class="bg-white hover:bg-gray-100 rounded-full p-3 shadow-lg transition-all"
                :class="{ 'opacity-50 cursor-not-allowed': isFavoriting }"
                :title="isFavorited ? 'Remover dos favoritos' : 'Adicionar aos favoritos'"
              >
                <Icon
                  :icon="isFavorited ? 'lucide:heart-fill' : 'lucide:heart'"
                  class="size-6"
                  :class="isFavorited ? 'text-red-500' : 'text-gray-600'"
                />
              </button>-->

              <!-- Media Count Badge -->
              <div class="bg-black/70 backdrop-blur-sm text-white px-3 py-1 rounded-full text-sm flex items-center gap-2">
                <Icon icon="lucide:images" class="size-4" />
                <span>{{ mediaItems.length }} {{ mediaItems.length === 1 ? 'mídia' : 'mídias' }}</span>
              </div>
            </div>

            <!-- Location Button -->
            <button
              v-if="!isVirtualOnlyStore"
              @click="openLocation"
              class="absolute bottom-4 left-4 bg-white hover:bg-gray-100 text-foreground px-4 py-2 rounded-lg shadow-lg transition-all flex items-center gap-2 font-medium z-10"
            >
              <Icon icon="lucide:map-pin" class="size-5" />
              Ver Localização
            </button>
          </div>
        </div>
      </section>

      <!-- Store Details Section -->
      <section class="py-12">
        <div class="container mx-auto px-4">
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content (Left) -->
            <div class="lg:col-span-2 space-y-6">
              <!-- Header -->
              <div>
                <div class="flex items-start justify-between mb-4">
                  <div class="flex items-start gap-6">
                    <!-- Logo -->
                    <div v-if="store.logo" class="flex-shrink-0">
                      <div class="w-24 h-24 rounded-lg overflow-hidden border-2 border-border bg-white shadow-sm">
                        <img
                          :src="store.logo"
                          :alt="`${store.name} Logo`"
                          class="w-full h-full object-contain p-2"
                        />
                      </div>
                    </div>

                    <!-- Store Info -->
                    <div>
                      <div class="flex flex-wrap gap-1 mb-2">
                        <template v-if="store.badge?.toLowerCase() === 'ambos'">
                          <Badge variant="secondary">Atacado</Badge>
                          <Badge variant="secondary">Varejo</Badge>
                        </template>
                        <Badge v-else variant="secondary">
                          {{ store.badge }}
                        </Badge>
                        <Badge v-if="storeTypeTagLabel" variant="secondary">
                          {{ storeTypeTagLabel }}
                        </Badge>
                      </div>
                      <div class="mb-2 flex flex-wrap items-center gap-3">
                        <h1 class="text-4xl font-bold text-foreground">
                          {{ store.name }}
                        </h1>
                        <Badge
                          v-if="store.is_verified"
                          class="bg-teal-600 text-white shadow-sm"
                        >
                          <Icon icon="lucide:badge-check" class="size-3 mr-1" />
                          Fornecedor verificado
                        </Badge>
                      </div>
                      <p class="text-muted-foreground">Cod: {{ store.code }}</p>
                    </div>
                  </div>
                </div>

                <!-- Info Tags -->
                <div class="flex flex-wrap gap-4 mt-4">
                  <div class="flex items-center gap-2 text-muted-foreground">
                    <Icon icon="lucide:tag" class="size-5 text-primary" />
                    <span>{{ store.category }}<template v-if="formattedSubcategories"> - {{ formattedSubcategories }}</template></span>
                  </div>
                  <div v-if="!isVirtualOnlyStore" class="flex items-center gap-2 text-muted-foreground">
                    <Icon icon="lucide:map-pin" class="size-5 text-primary" />
                    <span>{{ locationSummaryDisplay }}</span>
                  </div>
                  <div v-if="hasMinOrder" class="flex items-center gap-2 text-muted-foreground">
                    <Icon icon="lucide:package" class="size-5 text-primary" />
                    <span>{{ store.minOrder }}</span>
                  </div>
                  <div class="flex items-center gap-2 text-muted-foreground">
                    <Icon icon="lucide:store" class="size-5 text-primary" />
                    <span>{{ saleTypeDisplayLabel }}</span>
                  </div>
                </div>

                <div class="mt-6 border-y border-border">
                  <div class="grid" :class="hasCollections ? 'grid-cols-3' : 'grid-cols-2'">
                    <button
                      v-for="tab in sectionTabs"
                      :key="tab.id"
                      type="button"
                      class="relative flex items-center justify-center py-3 text-muted-foreground transition-colors hover:text-foreground"
                      :class="{ 'text-foreground': activeSectionTab === tab.id }"
                      @click="scrollToSection(tab.id)"
                    >
                      <Icon :icon="tab.icon" class="size-5" />
                      <span
                        class="absolute inset-x-4 bottom-0 h-0.5 rounded-full transition-colors"
                        :class="activeSectionTab === tab.id ? 'bg-foreground' : 'bg-transparent'"
                      />
                    </button>
                  </div>
                </div>
              </div>

              <!-- Description -->
              <Card id="store-section-sobre" class="p-6 scroll-mt-28">
                <h2 class="text-2xl font-bold mb-4">Sobre o Fornecedor</h2>
                <p class="text-muted-foreground leading-relaxed whitespace-pre-line">
                  {{ store.description }}
                </p>
              </Card>

              <Card v-if="hasCollections" id="store-section-galeria" class="p-6 scroll-mt-28">
                <h2 class="text-2xl font-bold mb-4">Galeria de Fotos / Coleções</h2>
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                  <button
                    v-if="featuredCollection"
                    type="button"
                    class="group relative overflow-hidden rounded-2xl border border-border text-left lg:col-span-6 min-h-[220px]"
                    :class="{ 'ring-2 ring-primary/30': selectedCollectionId === featuredCollection.id }"
                    @click="selectCollection(featuredCollection.id)"
                  >
                    <img
                      v-if="featuredCollection.cover"
                      :src="featuredCollection.cover"
                      :alt="featuredCollection.name"
                      class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                    >
                    <div v-else class="absolute inset-0 bg-muted flex items-center justify-center">
                      <Icon icon="lucide:image-off" class="size-10 text-muted-foreground" />
                    </div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/20 to-black/10" />
                    <div class="relative h-full p-5 flex flex-col justify-end">
                      <div class="inline-flex w-fit items-center gap-2 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-foreground mb-3">
                        <Icon icon="lucide:crown" class="size-3.5 text-amber-500" />
                        Coleção em destaque
                      </div>
                      <h3 class="text-xl sm:text-2xl font-bold text-white">{{ featuredCollection.name }}</h3>
                      <div class="mt-3 flex items-center justify-between">
                        <span class="inline-flex items-center gap-1 rounded-full bg-white/15 px-2.5 py-1 text-xs text-white backdrop-blur-sm">
                          <Icon icon="lucide:images" class="size-3.5" />
                          {{ featuredCollection.photos_count }} fotos
                        </span>
                      </div>
                    </div>
                  </button>

                  <div class="lg:col-span-6 grid grid-cols-1 sm:grid-cols-2 gap-4 content-start">
                    <button
                      v-for="collection in visibleSecondaryCollections"
                      :key="collection.id"
                      type="button"
                      class="group relative overflow-hidden rounded-2xl border border-border text-left transition-all hover:shadow-md min-h-[150px]"
                      :class="{ 'ring-2 ring-primary/30': selectedCollectionId === collection.id }"
                      @click="selectCollection(collection.id)"
                    >
                      <img
                        v-if="collection.cover"
                        :src="collection.cover"
                        :alt="collection.name"
                        class="absolute inset-0 h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                      >
                      <div v-else class="absolute inset-0 bg-muted flex items-center justify-center">
                        <Icon icon="lucide:image-off" class="size-8 text-muted-foreground" />
                      </div>
                      <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent" />

                      <div class="relative h-full p-4 flex flex-col justify-end">
                        <div class="flex items-end justify-between gap-2">
                          <h3 class="text-sm font-semibold text-white">{{ collection.name }}</h3>
                          <span class="inline-flex items-center gap-1 rounded-full bg-white/15 px-2 py-0.5 text-[11px] text-white backdrop-blur-sm">
                            <Icon icon="lucide:images" class="size-3" />
                            {{ collection.photos_count }}
                          </span>
                        </div>
                      </div>
                    </button>
                  </div>
                </div>

                <div v-if="secondaryCollections.length > 4" class="mt-4 flex justify-center">
                  <Button
                    type="button"
                    variant="outline"
                    class="rounded-full px-5"
                    @click="showAllCollections = !showAllCollections"
                  >
                    <Icon
                      :icon="showAllCollections ? 'lucide:chevron-up' : 'lucide:chevron-down'"
                      class="size-4 mr-2"
                    />
                    {{ showAllCollections ? 'Mostrar menos categorias' : `Ver mais categorias (${secondaryCollections.length - 4})` }}
                  </Button>
                </div>

                <div
                  v-if="selectedCollection"
                  ref="selectedCollectionPreviewRef"
                  class="mt-6 rounded-2xl border border-border bg-card overflow-hidden scroll-mt-28"
                >
                  <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-border">
                    <div>
                      <p class="text-sm font-semibold">{{ selectedCollection.name }}</p>
                      <p class="text-xs text-muted-foreground">
                        Visualizando coleção • {{ selectedCollectionImages.length }} imagens
                      </p>
                    </div>
                    <span class="inline-flex items-center gap-1 rounded-full bg-muted px-2.5 py-1 text-xs text-muted-foreground">
                      <Icon icon="lucide:images" class="size-3.5" />
                      {{ selectedCollection.photos_count }} fotos
                    </span>
                  </div>

                  <div class="p-4">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 md:h-[420px]">
                      <div class="hidden md:block md:col-span-4 lg:col-span-3 border border-border rounded-xl p-2 md:overflow-y-auto md:h-full">
                        <div class="grid grid-cols-1 gap-2">
                          <button
                            v-for="(img, index) in selectedCollectionImages"
                            :key="`${selectedCollection.id}-${index}`"
                            type="button"
                            class="relative h-24 rounded-lg overflow-hidden border transition-all"
                            :class="selectedCollectionImageIndex === index ? 'border-primary ring-2 ring-primary/20' : 'border-border hover:border-primary/40'"
                            @click="selectedCollectionImageIndex = index"
                          >
                            <img
                              :src="img"
                              :alt="`${selectedCollection.name} ${index + 1}`"
                              class="h-full w-full object-cover"
                            >
                          </button>
                        </div>
                      </div>

                      <div class="md:col-span-8 lg:col-span-9 relative border border-border rounded-xl overflow-hidden md:h-full">
                        <div
                          v-if="selectedCollectionMainImage"
                          class="relative h-[260px] sm:h-[320px] md:h-full"
                        >
                          <div class="absolute inset-0">
                            <img
                              :src="selectedCollectionMainImage"
                              :alt="`${selectedCollection.name} fundo`"
                              class="h-full w-full object-cover scale-110 blur-2xl opacity-45"
                            >
                            <div class="absolute inset-0 bg-black/10" />
                          </div>
                          <button
                            type="button"
                            class="relative z-10 block h-full w-full text-left"
                            @click="openCollectionLightbox()"
                          >
                            <img
                              :src="selectedCollectionMainImage"
                              :alt="selectedCollection.name"
                              class="h-full w-full object-contain"
                            >
                          </button>

                          <button
                            v-if="selectedCollectionImages.length > 1"
                            type="button"
                            class="absolute left-2 md:left-3 top-1/2 z-20 -translate-y-1/2 inline-flex h-8 w-8 md:h-10 md:w-10 items-center justify-center rounded-full border border-black/30 bg-black/45 text-white shadow-sm backdrop-blur-sm hover:bg-black/60"
                            aria-label="Imagem anterior"
                            @click.stop="prevSelectedCollectionImage"
                          >
                            <Icon icon="lucide:chevron-left" class="size-4 md:size-5" />
                          </button>
                          <button
                            v-if="selectedCollectionImages.length > 1"
                            type="button"
                            class="absolute right-2 md:right-3 top-1/2 z-20 -translate-y-1/2 inline-flex h-8 w-8 md:h-10 md:w-10 items-center justify-center rounded-full border border-black/30 bg-black/45 text-white shadow-sm backdrop-blur-sm hover:bg-black/60"
                            aria-label="Próxima imagem"
                            @click.stop="nextSelectedCollectionImage"
                          >
                            <Icon icon="lucide:chevron-right" class="size-4 md:size-5" />
                          </button>
                        </div>
                      </div>
                    </div>

                    <div class="md:hidden mt-3 -mx-1 px-1 overflow-x-auto">
                      <div class="flex gap-2 min-w-max">
                        <button
                          v-for="(img, index) in selectedCollectionImages"
                          :key="`mobile-${selectedCollection.id}-${index}`"
                          type="button"
                          class="relative h-16 w-16 rounded-lg overflow-hidden border transition-all shrink-0"
                          :class="selectedCollectionImageIndex === index ? 'border-primary ring-2 ring-primary/20' : 'border-border'"
                          @click="selectedCollectionImageIndex = index"
                        >
                          <img
                            :src="img"
                            :alt="`${selectedCollection.name} ${index + 1}`"
                            class="h-full w-full object-cover"
                          >
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </Card>

              <!-- Additional Info -->
              <Card id="store-section-infos" class="p-6 scroll-mt-28">
                <h2 class="text-2xl font-bold mb-4">Informações Adicionais</h2>
                <div class="space-y-3">
                  <div v-if="hasMinOrder" class="flex items-start gap-3">
                    <Icon icon="lucide:check-circle" class="size-5 text-primary mt-1" />
                    <div>
                      <p class="font-medium">Pedido Mínimo</p>
                      <p class="text-sm text-muted-foreground">{{ store.minOrder }}</p>
                    </div>
                  </div>
                  <div class="flex items-start gap-3">
                    <Icon icon="lucide:check-circle" class="size-5 text-primary mt-1" />
                    <div>
                      <p class="font-medium">Categoria</p>
                      <p class="text-sm text-muted-foreground">{{ store.category }}<template v-if="formattedSubcategories"> - {{ formattedSubcategories }}</template></p>
                    </div>
                  </div>
                  <div class="flex items-start gap-3">
                    <Icon icon="lucide:check-circle" class="size-5 text-primary mt-1" />
                    <div>
                      <p class="font-medium">Tipo de Venda</p>
                      <p class="text-sm text-muted-foreground">{{ saleTypeDisplayLabel }}</p>
                    </div>
                  </div>
                </div>
              </Card>
            </div>

            <!-- Contact Sidebar (Right) -->
            <div class="lg:col-span-1">
              <Card class="p-6 sticky top-24">
                <h3 class="text-xl font-bold mb-4">Entre em Contato</h3>

                <!-- WhatsApp Button -->
                <Button
                  v-if="store.whatsapp"
                  @click="openWhatsApp"
                  size="lg"
                  class="w-full mb-3 bg-green-600 hover:bg-green-700 text-white"
                >
                  <Icon icon="lucide:message-circle" class="size-5 mr-2" />
                  WhatsApp
                </Button>

                <!-- Website Button -->
                <Button
                  v-if="store.website"
                  @click="openWebsite"
                  size="lg"
                  variant="outline"
                  class="w-full mb-3"
                >
                  <Icon icon="lucide:globe" class="size-5 mr-2" />
                  Visitar Site
                </Button>

                <!-- Location Button -->
                <Button
                  v-if="storesForMap.length > 0"
                  @click="openMapWidget"
                  size="lg"
                  variant="outline"
                  class="w-full mb-6"
                >
                  <Icon icon="lucide:map-pin" class="size-5 mr-2" />
                  Ver no Mapa
                </Button>

                <!-- Location -->
                <div v-if="!isVirtualOnlyStore" class="border-t pt-4 mb-4">
                  <h4 class="font-semibold mb-3">Localização</h4>
                  <p class="text-sm text-muted-foreground flex items-start gap-2">
                    <Icon icon="lucide:map-pin" class="size-4 mt-1 flex-shrink-0" />
                    {{ fullAddressDisplay }}
                  </p>
                </div>

                <!-- Social Media -->
                <div v-if="store.instagram || store.facebook || store.tiktok" class="border-t pt-4 mb-4">
                  <h4 class="font-semibold mb-3">Redes Sociais</h4>
                  <div class="flex gap-2">
                    <a
                      v-if="store.instagram"
                      :href="`https://instagram.com/${store.instagram}`"
                      target="_blank"
                      class="p-2 rounded-lg hover:bg-muted transition-colors"
                      aria-label="Instagram"
                      title="Instagram"
                      @click="trackInstagramClick"
                    >
                      <Icon icon="lucide:instagram" class="size-5 text-pink-600" />
                    </a>
                    <a
                      v-if="store.facebook"
                      :href="`https://facebook.com/${store.facebook}`"
                      target="_blank"
                      class="p-2 rounded-lg hover:bg-muted transition-colors"
                      aria-label="Facebook"
                      title="Facebook"
                      @click="trackFacebookClick"
                    >
                      <Icon icon="lucide:facebook" class="size-5 text-blue-600" />
                    </a>
                    <a
                      v-if="store.tiktok"
                      :href="`https://tiktok.com/@${store.tiktok}`"
                      target="_blank"
                      class="p-2 rounded-lg hover:bg-muted transition-colors"
                      aria-label="TikTok"
                      title="TikTok"
                      @click="trackTikTokClick"
                    >
                      <Icon icon="bi:tiktok" class="size-5 text-foreground" />
                    </a>
                  </div>
                </div>

                <!-- Share -->
                <div class="border-t pt-4">
                  <h4 class="font-semibold mb-3">Compartilhar</h4>
                  <div class="flex gap-2">
                    <button
                      @click="shareOnFacebook"
                      class="p-2 rounded-lg hover:bg-muted transition-colors"
                      aria-label="Compartilhar no Facebook"
                      title="Compartilhar no Facebook"
                    >
                      <Icon icon="lucide:facebook" class="size-5 text-muted-foreground hover:text-foreground" />
                    </button>
                    <button
                      @click="shareOnTwitter"
                      class="p-2 rounded-lg hover:bg-muted transition-colors"
                      aria-label="Compartilhar no Twitter"
                      title="Compartilhar no Twitter"
                    >
                      <Icon icon="lucide:twitter" class="size-5 text-muted-foreground hover:text-foreground" />
                    </button>
                    <button
                      @click="copyLink"
                      class="p-2 rounded-lg hover:bg-muted transition-colors"
                      aria-label="Copiar link"
                      title="Copiar link"
                    >
                      <Icon icon="lucide:link" class="size-5 text-muted-foreground hover:text-foreground" />
                    </button>
                  </div>
                </div>
              </Card>
            </div>
          </div>
        </div>
      </section>
    </div>

    <Dialog v-model:open="isGalleryLightboxOpen">
      <DialogContent class="max-w-[98vw] sm:max-w-6xl p-0 overflow-hidden border-0 bg-transparent shadow-none">
        <div class="relative rounded-2xl border border-[#d9c38a]/20 bg-[linear-gradient(160deg,rgba(7,56,58,0.96),rgba(8,25,33,0.97)_55%,rgba(18,14,22,0.97))] backdrop-blur-xl overflow-hidden shadow-[0_24px_80px_rgba(3,16,19,0.55)]">
          <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_12%_18%,rgba(255,214,102,0.16),transparent_42%),radial-gradient(circle_at_88%_12%,rgba(99,235,220,0.12),transparent_38%),radial-gradient(circle_at_50%_100%,rgba(255,255,255,0.06),transparent_40%)]" />

          <div class="relative z-10 flex items-center justify-between gap-3 border-b border-white/10 px-4 py-3 sm:px-5 bg-white/[0.02]">
            <div class="min-w-0">
              <p class="truncate text-sm font-semibold text-white">
                {{ activeLightboxTitle }}
              </p>
              <p class="text-xs text-white/70">
                {{ activeLightboxIndex + 1 }} de {{ activeLightboxItems.length }} mídias
              </p>
            </div>
            <div class="flex items-center gap-2">
              <button
                v-if="canZoomActiveItem"
                type="button"
                class="inline-flex items-center gap-1.5 rounded-full border border-[#d9c38a]/25 bg-white/5 px-3 py-1.5 text-xs text-white/90 hover:bg-white/10"
                @click="toggleLightboxZoom"
              >
                <Icon :icon="isLightboxZoomed ? 'lucide:zoom-out' : 'lucide:zoom-in'" class="size-3.5 text-[#f4d37a]" />
                {{ isLightboxZoomed ? 'Reduzir' : 'Zoom' }}
              </button>
              <button
                type="button"
                class="rounded-full border border-[#d9c38a]/25 bg-white/5 p-2 text-white hover:bg-white/10"
                @click="closeGalleryLightbox"
                aria-label="Fechar galeria"
              >
                <Icon icon="lucide:x" class="size-5" />
              </button>
            </div>
          </div>

          <div class="absolute left-1/2 top-14 z-10 h-px w-[80%] -translate-x-1/2 bg-gradient-to-r from-transparent via-[#f4d37a]/20 to-transparent" />

          <button
            v-if="activeLightboxItems.length > 1"
            type="button"
            class="absolute left-3 top-1/2 z-20 -translate-y-1/2 rounded-full border border-[#d9c38a]/25 bg-white/10 p-2.5 text-white backdrop-blur-sm hover:bg-white/15 sm:left-4"
            @click="prevGalleryImage"
            aria-label="Imagem anterior"
          >
            <Icon icon="lucide:chevron-left" class="size-6" />
          </button>

          <div
            class="relative z-10 flex items-center justify-center min-h-[52vh] sm:min-h-[72vh] px-3 py-4 sm:px-6"
            @touchstart="onLightboxTouchStart"
            @touchmove="onLightboxTouchMove"
            @touchend="onLightboxTouchEnd"
          >
            <button
              v-if="activeLightboxMainItem"
              type="button"
              class="group relative max-h-[82vh] max-w-full cursor-zoom-in overflow-hidden rounded-xl border border-white/15 bg-white/[0.03] shadow-[0_20px_60px_rgba(0,0,0,0.35)]"
              :class="{ 'cursor-zoom-out': isLightboxZoomed, 'cursor-default': !canZoomActiveItem }"
              @click="toggleLightboxZoom"
              :aria-label="isLightboxZoomed ? 'Reduzir zoom' : 'Ampliar imagem'"
            >
              <div class="pointer-events-none absolute inset-0 rounded-xl ring-1 ring-inset ring-white/10" />
              <img
                v-if="activeLightboxMainItem.type === 'image'"
                :src="activeLightboxMainItem.url"
                :alt="activeLightboxTitle || 'Imagem da coleção'"
                class="max-h-[82vh] w-auto max-w-full object-contain transition-transform duration-200"
                :class="isLightboxZoomed ? 'scale-125' : 'scale-100'"
              >
              <iframe
                v-else-if="activeLightboxMainItem.type === 'video-embed'"
                :src="activeLightboxMainItem.url"
                class="h-[52vh] sm:h-[72vh] w-[90vw] sm:w-[80vw] max-w-5xl"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
              />
              <video
                v-else-if="activeLightboxMainItem.type === 'video-upload'"
                :src="activeLightboxMainItem.url"
                class="max-h-[82vh] w-auto max-w-full object-contain"
                controls
                autoplay
                playsinline
              />
            </button>
          </div>

          <button
            v-if="activeLightboxItems.length > 1"
            type="button"
            class="absolute right-3 top-1/2 z-20 -translate-y-1/2 rounded-full border border-[#d9c38a]/25 bg-white/10 p-2.5 text-white backdrop-blur-sm hover:bg-white/15 sm:right-4"
            @click="nextGalleryImage"
            aria-label="Próxima imagem"
          >
            <Icon icon="lucide:chevron-right" class="size-6" />
          </button>

          <div v-if="activeLightboxItems.length > 1" class="relative z-10 border-t border-white/10 bg-white/[0.03] px-4 py-3 sm:px-5">
            <div class="flex gap-2 overflow-x-auto pb-1">
              <button
                v-for="(media, index) in activeLightboxItems"
                :key="`lightbox-thumb-${lightboxSource}-${index}`"
                type="button"
                class="relative h-14 w-14 flex-shrink-0 overflow-hidden rounded-lg border transition-all"
                :class="activeLightboxIndex === index ? 'border-[#f4d37a] ring-2 ring-[#f4d37a]/20' : 'border-white/15 hover:border-white/35'"
                @click="activeLightboxIndex = index"
              >
                <div
                  class="absolute inset-0 z-10 transition-colors"
                  :class="activeLightboxIndex === index ? 'bg-transparent' : 'bg-black/25'"
                />
                <img v-if="media.type === 'image'" :src="media.url" :alt="`Miniatura ${index + 1}`" class="h-full w-full object-cover">
                <div v-else class="flex h-full w-full items-center justify-center bg-black/50">
                  <Icon :icon="media.type === 'video-embed' ? 'lucide:youtube' : 'lucide:film'" class="size-5 text-white" />
                </div>
              </button>
            </div>
          </div>
        </div>
      </DialogContent>
    </Dialog>

    <button
      v-show="showBackToTop"
      type="button"
      class="fixed bottom-6 left-1/2 z-40 -translate-x-1/2 inline-flex items-center gap-2 rounded-full border border-green-700 bg-green-600 px-4 py-2.5 shadow-lg transition hover:bg-green-700 hover:shadow-xl"
      aria-label="Voltar ao topo"
      title="Voltar ao topo"
      @click="scrollToTop"
    >
      <Icon icon="lucide:arrow-up" class="size-4 text-white" />
      <span class="text-sm font-medium text-white">Voltar ao topo</span>
    </button>

    <FloatingMap
      v-if="storesForMap.length > 0"
      ref="floatingMapRef"
      :stores="storesForMap"
      title="Mapa de Lojas Físicas"
    />
  </WebLayout>
</template>
