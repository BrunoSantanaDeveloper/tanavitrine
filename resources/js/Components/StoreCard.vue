<script setup>
import Badge from '@/Components/shadcn/ui/badge/Badge.vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import Card from '@/Components/shadcn/ui/card/Card.vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/shadcn/ui/avatar'
import { Icon } from '@iconify/vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import axios from 'axios'
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

// Lead capture modal
const showLeadModal = ref(false)
const leadAction = ref('whatsapp')
const leadForm = ref({
  name: '',
  whatsapp: ''
})
const isSubmittingLead = ref(false)

// Get all images from store (assuming store has an 'images' array, fallback to single 'image')
const images = computed(() => {
  if (props.store.images && props.store.images.length > 0) {
    return props.store.images
  }
  // Fallback to single image
  return [props.store.image || 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=800']
})

const currentImage = computed(() => images.value[currentImageIndex.value])

const hasMultipleImages = computed(() => images.value.length > 1)

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

function openWhatsApp() {
  leadAction.value = 'whatsapp'
  showLeadModal.value = true
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
      const phone = props.store.whatsapp
      const message = `Olá! Sou ${leadForm.value.name}. Vi a vitrine de ${props.store.name} no TanaVitrine e gostaria de saber mais.`
      const url = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`
      window.open(url, '_blank')
    }

    // Reset form
    leadForm.value = { name: '', whatsapp: '' }
  } catch (error) {
    alert('Erro ao enviar informações. Tente novamente.')
  } finally {
    isSubmittingLead.value = false
  }
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
      url: `${window.location.origin}/loja/${props.store.slug}`
    }).catch(() => {})
  } else {
    // Fallback: copy to clipboard
    const url = `${window.location.origin}/loja/${props.store.slug}`
    navigator.clipboard.writeText(url)
    alert('Link copiado para a área de transferência!')
  }
}
</script>

<template>
  <Card
    class="overflow-hidden hover:shadow-lg transition-shadow duration-300"
    :class="{ 'ring-2 ring-primary/20': store.featured }"
  >
    <div class="grid grid-cols-1 sm:grid-cols-5 gap-0">
      <!-- Image Section with Carousel -->
      <div class="sm:col-span-2 relative group">
        <!-- Main Image -->
        <img
          :src="currentImage"
          :alt="store.name"
          class="w-full h-64 sm:h-full object-cover transition-opacity duration-300"
        />

        <!-- Featured Badge -->
        <Badge
          v-if="store.featured"
          class="absolute top-3 left-3 bg-primary text-primary-foreground shadow-lg z-10"
        >
          <Icon icon="lucide:star" class="size-3 mr-1" />
          Destaque
        </Badge>

        <!-- Navigation Arrows (only show if multiple images) -->
        <template v-if="hasMultipleImages">
          <button
            @click.prevent="prevImage"
            class="absolute left-2 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white rounded-full p-2 shadow-md opacity-0 group-hover:opacity-100 transition-opacity z-10"
            aria-label="Imagem anterior"
          >
            <Icon icon="lucide:chevron-left" class="size-5 text-foreground" />
          </button>
          <button
            @click.prevent="nextImage"
            class="absolute right-2 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white rounded-full p-2 shadow-md opacity-0 group-hover:opacity-100 transition-opacity z-10"
            aria-label="Próxima imagem"
          >
            <Icon icon="lucide:chevron-right" class="size-5 text-foreground" />
          </button>

          <!-- Image Indicators -->
          <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5 z-10">
            <button
              v-for="(_, index) in images"
              :key="index"
              @click.prevent="currentImageIndex = index"
              class="w-2 h-2 rounded-full transition-all"
              :class="currentImageIndex === index ? 'bg-white w-6' : 'bg-white/60'"
              :aria-label="`Ver imagem ${index + 1}`"
            />
          </div>
        </template>
      </div>
      <!-- Content Section -->
      <div class="sm:col-span-3 p-6 flex flex-col justify-between">
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
                <Badge variant="secondary" class="mb-2 text-xs">
                  {{ store.badge }}
                </Badge>
                <h3 class="text-xl font-bold text-foreground mb-2">
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
                @click="shareStore"
                class="p-2 hover:bg-muted rounded-lg transition-colors"
                aria-label="Compartilhar"
                title="Compartilhar loja"
              >
                <Icon icon="lucide:share-2" class="size-5 text-muted-foreground" />
              </button>
              <Link
                :href="`/loja/${store.slug}`"
                class="p-2 hover:bg-muted rounded-lg transition-colors"
                aria-label="Ver detalhes"
                title="Ver detalhes da loja"
              >
                <Icon icon="lucide:eye" class="size-5 text-muted-foreground" />
              </Link>
            </div>
          </div>

          <!-- Info Tags -->
          <div class="flex flex-wrap gap-2 mb-3 text-sm">
            <div v-if="store.minOrder" class="flex items-center gap-1 text-muted-foreground">
              <Icon icon="lucide:package" class="size-4" />
              <span>{{ store.minOrder }}</span>
            </div>
            <div v-if="store.category" class="flex items-center gap-1 text-muted-foreground">
              <Icon icon="lucide:tag" class="size-4" />
              <span>{{ store.category }}</span>
            </div>
            <div v-if="store.location" class="flex items-center gap-1 text-muted-foreground">
              <Icon icon="lucide:map-pin" class="size-4" />
              <span>{{ store.location }}</span>
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
              :as="Link"
              :href="`/loja/${store.slug}`"
            >
              <Icon icon="lucide:eye" class="size-4 mr-1" />
              Detalhes
            </Button>
            <Button
              size="sm"
              class="flex-1 bg-green-600 hover:bg-green-700 text-white cursor-pointer"
              @click="openWhatsApp"
            >
              <Icon icon="lucide:message-circle" class="size-4 mr-1" />
              WhatsApp
            </Button>
          </div>
        </div>
      </div>
    </div>
  </Card>

  <!-- Lead Capture Modal -->
  <Dialog v-model:open="showLeadModal">
    <DialogContent class="sm:max-w-[425px]">
      <DialogHeader>
        <DialogTitle>Entrar em Contato</DialogTitle>
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
          class="bg-green-600 hover:bg-green-700"
        >
          <Icon v-if="isSubmittingLead" icon="lucide:loader-2" class="mr-2 h-4 w-4 animate-spin" />
          Abrir WhatsApp
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
