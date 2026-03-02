<script setup>
import Badge from '@/Components/shadcn/ui/badge/Badge.vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import Card from '@/Components/shadcn/ui/card/Card.vue'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/shadcn/ui/dialog'
import { Input } from '@/Components/shadcn/ui/input'
import { Label } from '@/Components/shadcn/ui/label'
import WebLayout from '@/Layouts/WebLayout.vue'
import { formatPhone, formatWhatsAppNumber } from '@/utils/formatters'
import { Icon } from '@iconify/vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import { computed, ref, onMounted, onBeforeUnmount } from 'vue'

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

const activeSectionTab = ref('sobre')
const showAllCollections = ref(false)
const selectedCollectionId = ref('vestidos')
const selectedCollectionImageIndex = ref(0)
const highlightCollectionViewer = ref(false)
const isGalleryLightboxOpen = ref(false)
const lightboxSource = ref('collection')
const topGalleryImageIndex = ref(0)
const lightboxTouchStartX = ref(0)
const lightboxTouchEndX = ref(0)
const isLightboxZoomed = ref(false)
const showBackToTop = ref(false)
const lastScrollTop = ref(0)
const sectionTabs = [
  { id: 'sobre', icon: 'lucide:file-text' },
  { id: 'midias', icon: 'lucide:images' },
  { id: 'infos', icon: 'lucide:circle-check-big' },
  { id: 'avaliacoes', icon: 'lucide:star' },
]

const previewReviews = [
  {
    id: 1,
    name: 'Mariana S.',
    rating: 5,
    date: 'há 2 semanas',
    comment: 'Atendimento rápido e peças com ótima qualidade. Recebi tudo certinho e dentro do prazo.',
  },
  {
    id: 2,
    name: 'Loja Bella Center',
    rating: 4,
    date: 'há 1 mês',
    comment: 'Boa variedade e comunicação fácil pelo WhatsApp. Voltaria a comprar.',
  },
  {
    id: 3,
    name: 'Patrícia A.',
    rating: 5,
    date: 'há 2 meses',
    comment: 'Fotos fiéis aos produtos e envio organizado. Experiência muito boa.',
  },
]

const previewReviewsRequireAuth = ref(true)

const previewRatingAverage = computed(() => {
  if (!previewReviews.length) return 0
  const total = previewReviews.reduce((sum, review) => sum + review.rating, 0)
  return (total / previewReviews.length).toFixed(1)
})

function maskReviewerName(name) {
  if (!name) return ''
  const parts = name.split(' ')
  return parts.map((part) => {
    if (part.length <= 2) return `${part[0] || ''}*`
    const visibleLength = Math.ceil(part.length / 2)
    return `${part.slice(0, visibleLength)}${'*'.repeat(Math.max(1, part.length - visibleLength))}`
  }).join(' ')
}

const previewCollections = [
  {
    id: 'vestidos',
    title: 'Vestidos',
    description: 'Modelos casuais e festa com foco em giro rapido e tendencias da estacao.',
    cover: '/images/room-vetfun3.png',
    count: 18,
    images: ['/images/room-vetfun3.png', '/images/room-vetfun2.png', '/images/room-vetfun.jpg', '/images/brand-image.png'],
  },
  {
    id: 'blusas',
    title: 'Blusas',
    description: 'Basicas e fashion para compor looks versateis no dia a dia.',
    cover: '/images/brand-image.png',
    count: 24,
    images: ['/images/brand-image.png', '/images/dashboard-light.webp', '/images/dashboard-dark.webp', '/images/og.webp'],
  },
  {
    id: 'calcas',
    title: 'Calças',
    description: 'Jeans e alfaiataria com variacao de modelagens e tamanhos.',
    cover: '/images/notification.png',
    count: 12,
    images: ['/images/notification.png', '/images/saving-money.png', '/images/close-business.png'],
  },
  {
    id: 'conjuntos',
    title: 'Conjuntos',
    description: 'Pecas coordenadas para vitrines e campanhas sazonais.',
    cover: '/images/og.webp',
    count: 9,
    images: ['/images/og.webp', '/images/video-placeholder.png', '/images/brand-image.png'],
  },
  {
    id: 'saias',
    title: 'Saias',
    description: 'Modelos midi, curtos e longos para compor colecoes femininas.',
    cover: '/images/saving-money.png',
    count: 11,
    images: ['/images/saving-money.png', '/images/notification.png', '/images/dashboard-dark.webp'],
  },
  {
    id: 'moda-praia',
    title: 'Moda Praia',
    description: 'Linha verao com biquinis, saidas e conjuntos leves.',
    cover: '/images/dashboard-light.webp',
    count: 15,
    images: ['/images/dashboard-light.webp', '/images/dashboard-dark.webp', '/images/room-vetfun3.png'],
  },
  {
    id: 'plus-size',
    title: 'Plus Size',
    description: 'Selecao dedicada com modelagens pensadas para conforto e estilo.',
    cover: '/images/dashboard-dark.webp',
    count: 13,
    images: ['/images/dashboard-dark.webp', '/images/brand-image.png', '/images/room-vetfun2.png'],
  },
  {
    id: 'acessorios',
    title: 'Acessórios',
    description: 'Bolsas, cintos e complementos para aumentar ticket medio da vitrine.',
    cover: '/images/video-placeholder.png',
    count: 21,
    images: ['/images/video-placeholder.png', '/images/og.webp', '/images/notification.png', '/images/brand-image.png'],
  },
]

const featuredCollection = computed(() => previewCollections[0] ?? null)
const secondaryCollections = computed(() => previewCollections.slice(1))
const visibleSecondaryCollections = computed(() => (
  showAllCollections.value ? secondaryCollections.value : secondaryCollections.value.slice(0, 4)
))
const selectedCollection = computed(() =>
  previewCollections.find(collection => collection.id === selectedCollectionId.value) ?? featuredCollection.value
)
const selectedCollectionImages = computed(() => selectedCollection.value?.images ?? [])
const selectedCollectionMainImage = computed(() =>
  selectedCollectionImages.value[selectedCollectionImageIndex.value] ?? selectedCollectionImages.value[0] ?? null
)
const topGalleryImages = computed(() => mediaItems.value
  .map((item, index) => ({ ...item, originalIndex: index }))
  .filter(item => item.type === 'image'))
const activeLightboxImages = computed(() =>
  lightboxSource.value === 'top'
    ? topGalleryImages.value.map(item => item.url)
    : selectedCollectionImages.value
)
const activeLightboxTitle = computed(() =>
  lightboxSource.value === 'top'
    ? `${props.store.name} • Fotos Destaques`
    : (selectedCollection.value?.title || 'Coleção')
)
const activeLightboxIndex = computed({
  get: () => (lightboxSource.value === 'top' ? topGalleryImageIndex.value : selectedCollectionImageIndex.value),
  set: (value) => {
    if (lightboxSource.value === 'top') topGalleryImageIndex.value = value
    else selectedCollectionImageIndex.value = value
  },
})
const activeLightboxMainImage = computed(() =>
  activeLightboxImages.value[activeLightboxIndex.value] ?? activeLightboxImages.value[0] ?? null
)

function selectCollection(collectionId) {
  selectedCollectionId.value = collectionId
  selectedCollectionImageIndex.value = 0

  requestAnimationFrame(() => {
    const viewer = document.getElementById('preview2-collection-viewer')
    if (!viewer) return

    const offset = 120
    const top = viewer.getBoundingClientRect().top + window.scrollY - offset
    window.scrollTo({ top, behavior: 'smooth' })

    highlightCollectionViewer.value = true
    window.setTimeout(() => {
      highlightCollectionViewer.value = false
    }, 900)
  })
}

function openGalleryLightbox(index = selectedCollectionImageIndex.value, source = 'collection') {
  lightboxSource.value = source
  if (source === 'top') topGalleryImageIndex.value = index
  else selectedCollectionImageIndex.value = index
  isLightboxZoomed.value = false
  isGalleryLightboxOpen.value = true
}

function openTopGalleryLightboxByMediaIndex(mediaIndex) {
  const imageIndex = topGalleryImages.value.findIndex(item => item.originalIndex === mediaIndex)
  if (imageIndex === -1) return
  openGalleryLightbox(imageIndex, 'top')
}

function closeGalleryLightbox() {
  isLightboxZoomed.value = false
  isGalleryLightboxOpen.value = false
}

function nextCollectionImage() {
  if (!activeLightboxImages.value.length) return
  isLightboxZoomed.value = false
  activeLightboxIndex.value = (activeLightboxIndex.value + 1) % activeLightboxImages.value.length
}

function prevCollectionImage() {
  if (!activeLightboxImages.value.length) return
  isLightboxZoomed.value = false
  activeLightboxIndex.value = (activeLightboxIndex.value - 1 + activeLightboxImages.value.length) % activeLightboxImages.value.length
}

function handleLightboxKeydown(event) {
  if (!isGalleryLightboxOpen.value) return

  if (event.key === 'Escape') {
    closeGalleryLightbox()
    return
  }

  if (event.key === 'ArrowRight') {
    nextCollectionImage()
    return
  }

  if (event.key === 'ArrowLeft') {
    prevCollectionImage()
  }
}

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
  window.scrollTo({ top: 0, behavior: 'smooth' })
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
    nextCollectionImage()
  } else {
    prevCollectionImage()
  }
}

function toggleLightboxZoom() {
  isLightboxZoomed.value = !isLightboxZoomed.value
}

onMounted(() => {
  window.addEventListener('keydown', handleLightboxKeydown)
  window.addEventListener('scroll', handleBackToTopVisibility, { passive: true })
  document.addEventListener('scroll', handleBackToTopVisibility, { passive: true, capture: true })
  handleBackToTopVisibility()
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleLightboxKeydown)
  window.removeEventListener('scroll', handleBackToTopVisibility)
  document.removeEventListener('scroll', handleBackToTopVisibility, true)
})

function scrollToSection(sectionId) {
  activeSectionTab.value = sectionId
  const element = document.getElementById(`preview2-section-${sectionId}`)
  if (!element) return

  const offset = 120
  const top = element.getBoundingClientRect().top + window.scrollY - offset
  window.scrollTo({ top, behavior: 'smooth' })
}

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

// Lead capture modal
const showLeadModal = ref(false)
const leadAction = ref('whatsapp')
const leadForm = ref({
  name: '',
  whatsapp: ''
})
const isSubmittingLead = ref(false)

async function openLocation() {
  // Track map click
  await trackMapClick()

  leadAction.value = 'map'
  showLeadModal.value = true
}

function openWhatsApp() {
  leadAction.value = 'whatsapp'
  showLeadModal.value = true
}

function openWebsite() {
  leadAction.value = 'website'
  showLeadModal.value = true
}

function handlePhoneInput(event) {
  const formatted = formatPhone(event.target.value)
  leadForm.value.whatsapp = formatted
}

async function submitLead() {
  if (!leadForm.value.name || !leadForm.value.whatsapp) {
    alert('Por favor, preencha todos os campos.')
    return
  }

  isSubmittingLead.value = true

  try {
    await axios.post(`/loja/${props.store.slug}/lead`, {
      name: leadForm.value.name,
      whatsapp: leadForm.value.whatsapp,
      action: leadAction.value
    })

    showLeadModal.value = false

    // Execute the action
    if (leadAction.value === 'whatsapp') {
      const phone = formatWhatsAppNumber(props.store.whatsapp)
      const message = `Olá! Sou ${leadForm.value.name}. Vi a vitrine de ${props.store.name} no TanaVitrine e gostaria de saber mais.`
      const url = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`
      window.open(url, '_blank')
    } else if (leadAction.value === 'map') {
      const query = encodeURIComponent(props.store.location || props.store.name)
      window.open(`https://www.google.com/maps/search/?api=1&query=${query}`, '_blank')
    } else if (leadAction.value === 'website') {
      window.open(props.store.website, '_blank')
    }

    // Reset form
    leadForm.value = { name: '', whatsapp: '' }
  } catch (error) {
    alert('Erro ao enviar informações. Tente novamente.')
  } finally {
    isSubmittingLead.value = false
  }
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
  const text = encodeURIComponent(`Confira ${props.store.name} no TanaVitrine!`)
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

async function trackPhoneClick() {
  try {
    await axios.post(`/loja/${props.store.slug}/track/phone`)
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
                <div class="aspect-[4/3] rounded-lg overflow-hidden bg-black">
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
                  <button
                    v-else
                    type="button"
                    class="block h-full w-full text-left"
                    @click="openTopGalleryLightboxByMediaIndex(index)"
                  >
                    <img
                      :src="item.url"
                      :alt="`${store.name} - Foto ${index + 1}`"
                      class="w-full h-full object-cover"
                    >
                  </button>
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
                      </div>
                      <h1 class="text-4xl font-bold text-foreground mb-2">
                        {{ store.name }}
                      </h1>
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
                  <div class="flex items-center gap-2 text-muted-foreground">
                    <Icon icon="lucide:map-pin" class="size-5 text-primary" />
                    <span>{{ store.location }}</span>
                  </div>
                  <div class="flex items-center gap-2 text-muted-foreground">
                    <Icon icon="lucide:package" class="size-5 text-primary" />
                    <span>{{ store.minOrder }}</span>
                  </div>
                  <div class="flex items-center gap-2 text-muted-foreground">
                    <Icon icon="lucide:store" class="size-5 text-primary" />
                    <span>{{ store.saleType }}</span>
                  </div>
                </div>

                <!-- Section Tabs (preview2 only) -->
                <div class="mt-6 border-y border-border">
                  <div class="grid grid-cols-4">
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
              <Card id="preview2-section-sobre" class="p-6 scroll-mt-28">
                <h2 class="text-2xl font-bold mb-4">Sobre o Fornecedor</h2>
                <p class="text-muted-foreground leading-relaxed whitespace-pre-line">
                  {{ store.description }}
                </p>
              </Card>

              <Card id="preview2-section-midias" class="p-6 scroll-mt-28">
                <h2 class="text-2xl font-bold mb-4">Galeria de Fotos</h2>
                <p class="text-sm text-muted-foreground mb-5">
                  Primeira versao: colecoes por categoria para organizar o catalogo da loja.
                </p>

                <div class="mb-5 rounded-2xl border border-border bg-gradient-to-br from-foreground/[0.03] via-background to-primary/[0.04] p-5">
                  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                      <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Catalogo Premium</p>
                      <p class="text-sm text-muted-foreground mt-1">
                        Colecoes organizadas para facilitar navegacao por linha de produto.
                      </p>
                    </div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-border bg-background px-3 py-1.5 text-xs font-medium">
                      <Icon icon="lucide:sparkles" class="size-3.5 text-primary" />
                      Experiencia de vitrine
                    </div>
                  </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                  <button
                    v-if="featuredCollection"
                    type="button"
                    class="group relative overflow-hidden rounded-2xl border border-border text-left lg:col-span-6 min-h-[220px]"
                    :class="{ 'ring-2 ring-primary/30': selectedCollectionId === featuredCollection.id }"
                    @click="selectCollection(featuredCollection.id)"
                  >
                    <img
                      :src="featuredCollection.cover"
                      :alt="featuredCollection.title"
                      class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                    >
                    <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/20 to-black/10" />
                    <div class="relative h-full p-5 flex flex-col justify-end">
                      <div class="inline-flex w-fit items-center gap-2 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-foreground mb-3">
                        <Icon icon="lucide:crown" class="size-3.5 text-amber-500" />
                        Colecao destaque
                      </div>
                      <h3 class="text-xl sm:text-2xl font-bold text-white">{{ featuredCollection.title }}</h3>
                      <div class="mt-3 flex items-center justify-between">
                        <span class="inline-flex items-center gap-1 rounded-full bg-white/15 px-2.5 py-1 text-xs text-white backdrop-blur-sm">
                          <Icon icon="lucide:images" class="size-3.5" />
                          {{ featuredCollection.count }} fotos
                        </span>
                        <span class="inline-flex items-center gap-1 text-sm font-medium text-white">
                          Explorar
                          <Icon icon="lucide:arrow-right" class="size-4 transition-transform group-hover:translate-x-0.5" />
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
                        :src="collection.cover"
                        :alt="collection.title"
                        class="absolute inset-0 h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                      >
                      <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent" />

                      <div class="relative h-full p-4 flex flex-col justify-end">
                        <div class="flex items-end justify-between gap-2">
                          <h3 class="text-sm font-semibold text-white">{{ collection.title }}</h3>
                          <span class="inline-flex items-center gap-1 rounded-full bg-white/15 px-2 py-0.5 text-[11px] text-white backdrop-blur-sm">
                            <Icon icon="lucide:images" class="size-3" />
                            {{ collection.count }}
                          </span>
                        </div>
                        <div class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-white/90">
                          Ver colecao
                          <Icon icon="lucide:chevron-right" class="size-3.5 text-white/80 group-hover:text-white" />
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
                  id="preview2-collection-viewer"
                  v-if="selectedCollection"
                  class="mt-6 rounded-2xl border border-border bg-card overflow-hidden scroll-mt-28 transition-all duration-500"
                  :class="highlightCollectionViewer ? 'ring-2 ring-primary/40 shadow-lg shadow-primary/10' : ''"
                >
                  <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-border">
                    <div>
                      <p class="text-sm font-semibold">{{ selectedCollection.title }}</p>
                      <p class="text-xs text-muted-foreground">
                        Visualizando colecao • {{ selectedCollectionImages.length }} imagens (preview)
                      </p>
                    </div>
                    <span class="inline-flex items-center gap-1 rounded-full bg-muted px-2.5 py-1 text-xs text-muted-foreground">
                      <Icon icon="lucide:images" class="size-3.5" />
                      {{ selectedCollection.count }} fotos
                    </span>
                  </div>

                  <div class="p-4 space-y-4">
                    <div
                      v-if="selectedCollectionMainImage"
                      class="relative h-[320px] sm:h-[380px] rounded-xl overflow-hidden border border-border"
                    >
                      <button
                        type="button"
                        class="block h-full w-full text-left"
                        @click="openGalleryLightbox()"
                      >
                        <img
                          :src="selectedCollectionMainImage"
                          :alt="selectedCollection.title"
                          class="h-full w-full object-cover"
                        >
                      </button>
                      <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/65 to-transparent p-4">
                        <p class="text-white font-semibold">{{ selectedCollection.title }}</p>
                      </div>
                      <button
                        type="button"
                        class="absolute top-3 right-3 rounded-full bg-black/55 text-white p-2 backdrop-blur-sm hover:bg-black/70"
                        @click="openGalleryLightbox()"
                        aria-label="Abrir visualização"
                      >
                        <Icon icon="lucide:expand" class="size-4" />
                      </button>
                    </div>

                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                      <button
                        v-for="(img, index) in selectedCollectionImages"
                        :key="`${selectedCollection.id}-${index}`"
                        type="button"
                        class="relative h-20 sm:h-24 rounded-lg overflow-hidden border transition-all"
                        :class="selectedCollectionImageIndex === index ? 'border-primary ring-2 ring-primary/20' : 'border-border hover:border-primary/40'"
                        @click="openGalleryLightbox(index)"
                      >
                        <img
                          :src="img"
                          :alt="`${selectedCollection.title} ${index + 1}`"
                          class="h-full w-full object-cover"
                        >
                      </button>
                    </div>
                  </div>
                </div>

                <div class="mt-5 rounded-lg border border-dashed border-border p-4 bg-muted/20">
                  <p class="text-xs text-muted-foreground">
                    Proximo passo (se aprovar): abrir esta visualizacao em modal/lightbox com zoom e navegacao.
                  </p>
                </div>
              </Card>

              <!-- Additional Info -->
              <Card id="preview2-section-infos" class="p-6 scroll-mt-28">
                <h2 class="text-2xl font-bold mb-4">Informações Adicionais</h2>
                <div class="space-y-3">
                  <div class="flex items-start gap-3">
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
                      <p class="text-sm text-muted-foreground">{{ store.saleType }}</p>
                    </div>
                  </div>
                </div>
              </Card>

              <Card id="preview2-section-avaliacoes" class="p-6 scroll-mt-28">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                  <div>
                    <h2 class="text-2xl font-bold">Avaliações</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                      Nota média e comentários de clientes (preview)
                    </p>
                  </div>

                  <div class="rounded-xl border border-border px-4 py-3 min-w-[180px]">
                    <div class="flex items-center gap-2">
                      <span class="text-2xl font-bold">{{ previewRatingAverage }}</span>
                      <div class="flex items-center gap-1">
                        <Icon
                          v-for="star in 5"
                          :key="`avg-star-${star}`"
                          icon="lucide:star"
                          class="size-4"
                          :class="star <= Math.round(Number(previewRatingAverage)) ? 'text-yellow-500 fill-yellow-500' : 'text-muted-foreground/40'"
                        />
                      </div>
                    </div>
                    <p class="text-xs text-muted-foreground mt-1">
                      {{ previewReviews.length }} avaliações
                    </p>
                  </div>
                </div>

                <div
                  v-if="previewReviewsRequireAuth"
                  class="mb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 rounded-xl border border-amber-300/30 bg-amber-50/60 px-4 py-3"
                >
                  <div class="flex items-start gap-3">
                    <div class="mt-0.5 rounded-full bg-amber-100 p-2 text-amber-700">
                      <Icon icon="lucide:lock" class="size-4" />
                    </div>
                    <div>
                      <p class="text-sm font-semibold text-amber-900">Comentários disponíveis para usuários cadastrados</p>
                      <p class="text-xs text-amber-800/80">
                        Neste preview vamos mostrar apenas o visual bloqueado (frontend).
                      </p>
                    </div>
                  </div>
                  <Button type="button" size="sm" variant="outline" class="border-amber-300 bg-white/80 text-amber-900 hover:bg-white">
                    Criar cadastro
                  </Button>
                </div>

                <div class="space-y-4">
                  <div
                    v-for="review in previewReviews"
                    :key="review.id"
                    class="rounded-xl border border-border p-4"
                  >
                    <div class="flex items-start justify-between gap-3">
                      <div>
                        <p class="font-semibold">{{ previewReviewsRequireAuth ? maskReviewerName(review.name) : review.name }}</p>
                        <div class="flex items-center gap-1 mt-1">
                          <Icon
                            v-for="star in 5"
                            :key="`review-${review.id}-star-${star}`"
                            icon="lucide:star"
                            class="size-4"
                            :class="star <= review.rating ? 'text-yellow-500 fill-yellow-500' : 'text-muted-foreground/30'"
                          />
                        </div>
                      </div>
                      <span class="text-xs text-muted-foreground whitespace-nowrap">{{ review.date }}</span>
                    </div>

                    <div class="relative mt-3">
                      <p
                        class="text-sm text-muted-foreground leading-6"
                        :class="previewReviewsRequireAuth ? 'select-none blur-[3px]' : ''"
                        :aria-hidden="previewReviewsRequireAuth"
                      >
                        {{ review.comment }}
                      </p>

                      <div
                        v-if="previewReviewsRequireAuth"
                        class="absolute inset-0 flex items-center justify-center rounded-md bg-gradient-to-r from-background/70 via-background/55 to-background/70"
                      >
                        <span class="inline-flex items-center gap-1 rounded-full border border-border bg-background px-3 py-1 text-xs font-medium">
                          <Icon icon="lucide:lock" class="size-3.5" />
                          Cadastre-se para ler
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </Card>
            </div>

            <!-- Contact Sidebar (Right) -->
            <div class="lg:col-span-1">
              <Card id="preview2-section-contato" class="p-6 sticky top-24 scroll-mt-28">
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
                  @click="openLocation"
                  size="lg"
                  variant="outline"
                  class="w-full mb-6"
                >
                  <Icon icon="lucide:map-pin" class="size-5 mr-2" />
                  Ver no Mapa
                </Button>

                <!-- Contact Info -->
                <div v-if="store.email || store.phone" class="border-t pt-4 mb-4 space-y-2">
                  <h4 class="font-semibold mb-3">Informações de Contato</h4>

                  <div v-if="store.email" class="flex items-center gap-2 text-sm text-muted-foreground">
                    <Icon icon="lucide:mail" class="size-4 flex-shrink-0" />
                    <a :href="`mailto:${store.email}`" class="hover:text-primary transition-colors break-all">
                      {{ store.email }}
                    </a>
                  </div>

                  <div v-if="store.phone" class="flex items-center gap-2 text-sm text-muted-foreground">
                    <Icon icon="lucide:phone" class="size-4 flex-shrink-0" />
                    <a :href="`tel:${store.phone}`" @click="trackPhoneClick" class="hover:text-primary transition-colors">
                      {{ store.phone }}
                    </a>
                  </div>
                </div>

                <!-- Location -->
                <div class="border-t pt-4 mb-4">
                  <h4 class="font-semibold mb-3">Localização</h4>
                  <p class="text-sm text-muted-foreground flex items-start gap-2">
                    <Icon icon="lucide:map-pin" class="size-4 mt-1 flex-shrink-0" />
                    {{ store.location }}
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

    <!-- Lead Capture Modal -->
    <Dialog v-model:open="showLeadModal">
      <DialogContent class="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>
            {{ leadAction === 'whatsapp' ? 'Entrar em Contato' : leadAction === 'map' ? 'Ver Localização' : 'Visitar Site' }}
          </DialogTitle>
          <DialogDescription>
            Para continuar, precisamos de algumas informações suas.
          </DialogDescription>
        </DialogHeader>
        <div class="grid gap-4 py-4">
          <div class="grid gap-2">
            <Label for="lead-name">Seu Nome *</Label>
            <Input
              id="lead-name"
              v-model="leadForm.name"
              placeholder="Digite seu nome"
              :disabled="isSubmittingLead"
            />
          </div>
          <div class="grid gap-2">
            <Label for="lead-whatsapp">Seu WhatsApp *</Label>
            <Input
              id="lead-whatsapp"
              v-model="leadForm.whatsapp"
              type="tel"
              placeholder="(00) 00000-0000"
              maxlength="15"
              :disabled="isSubmittingLead"
              @input="handlePhoneInput"
            />
          </div>
        </div>
        <DialogFooter>
          <Button
            type="button"
            variant="outline"
            @click="showLeadModal = false"
            :disabled="isSubmittingLead"
          >
            Cancelar
          </Button>
          <Button
            type="button"
            @click="submitLead"
            :disabled="isSubmittingLead"
            class="bg-teal-600 hover:bg-teal-700"
          >
            <Icon v-if="isSubmittingLead" icon="lucide:loader-2" class="mr-2 h-4 w-4 animate-spin" />
            {{ leadAction === 'whatsapp' ? 'Abrir WhatsApp' : leadAction === 'map' ? 'Ver no Mapa' : 'Visitar Site' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

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
                {{ activeLightboxIndex + 1 }} de {{ activeLightboxImages.length }} imagens
              </p>
            </div>
            <div class="flex items-center gap-2">
              <button
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
            v-if="activeLightboxImages.length > 1"
            type="button"
            class="absolute left-3 top-1/2 z-20 -translate-y-1/2 rounded-full border border-[#d9c38a]/25 bg-white/10 p-2.5 text-white backdrop-blur-sm hover:bg-white/15 sm:left-4"
            @click="prevCollectionImage"
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
              v-if="activeLightboxMainImage"
              type="button"
              class="group relative max-h-[82vh] max-w-full cursor-zoom-in overflow-hidden rounded-xl border border-white/15 bg-white/[0.03] shadow-[0_20px_60px_rgba(0,0,0,0.35)]"
              :class="{ 'cursor-zoom-out': isLightboxZoomed }"
              @click="toggleLightboxZoom"
                :aria-label="isLightboxZoomed ? 'Reduzir zoom' : 'Ampliar imagem'"
            >
              <div class="pointer-events-none absolute inset-0 rounded-xl ring-1 ring-inset ring-white/10" />
              <img
                :src="activeLightboxMainImage"
                :alt="activeLightboxTitle || 'Imagem da coleção'"
                class="max-h-[82vh] w-auto max-w-full object-contain transition-transform duration-200"
                :class="isLightboxZoomed ? 'scale-125' : 'scale-100'"
              >
              <div class="pointer-events-none absolute bottom-3 left-3 rounded-full border border-[#d9c38a]/20 bg-white/10 px-2.5 py-1 text-[11px] text-white/90 backdrop-blur-sm">
                Toque/click para {{ isLightboxZoomed ? 'reduzir' : 'ampliar' }}
              </div>
            </button>
          </div>

          <button
            v-if="activeLightboxImages.length > 1"
            type="button"
            class="absolute right-3 top-1/2 z-20 -translate-y-1/2 rounded-full border border-[#d9c38a]/25 bg-white/10 p-2.5 text-white backdrop-blur-sm hover:bg-white/15 sm:right-4"
            @click="nextCollectionImage"
            aria-label="Próxima imagem"
          >
            <Icon icon="lucide:chevron-right" class="size-6" />
          </button>

          <div v-if="activeLightboxImages.length > 1" class="relative z-10 border-t border-white/10 bg-white/[0.03] px-4 py-3 sm:px-5">
            <div class="flex gap-2 overflow-x-auto pb-1">
              <button
                v-for="(img, index) in activeLightboxImages"
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
                <img :src="img" :alt="`Miniatura ${index + 1}`" class="h-full w-full object-cover">
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
  </WebLayout>
</template>
