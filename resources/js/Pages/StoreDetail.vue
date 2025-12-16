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
import { computed, ref } from 'vue'

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
  <WebLayout :can-login="canLogin" :can-register="canRegister">
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
                  <img
                    v-else
                    :src="item.url"
                    :alt="`${store.name} - Foto ${index + 1}`"
                    class="w-full h-full object-cover"
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
              </div>

              <!-- Description -->
              <Card class="p-6">
                <h2 class="text-2xl font-bold mb-4">Sobre o Fornecedor</h2>
                <p class="text-muted-foreground leading-relaxed whitespace-pre-line">
                  {{ store.description }}
                </p>
              </Card>

              <!-- Additional Info -->
              <Card class="p-6">
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
  </WebLayout>
</template>
