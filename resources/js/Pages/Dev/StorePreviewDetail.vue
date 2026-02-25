<script setup>
import Button from '@/Components/shadcn/ui/button/Button.vue'
import { Icon } from '@iconify/vue'
import { computed } from 'vue'

const props = defineProps({
  slug: {
    type: String,
    required: true,
  },
})

const mockStores = {
  'bella-moda-preview': {
    name: 'Bella Moda Feminina',
    code: 'TV-001',
    badge: 'Varejo',
    location: 'Goiânia, GO',
    category: 'Moda Feminina',
    description: 'Pagina fake de detalhes para testar navegacao do botao "Detalhes" sem depender do backend.',
    images: ['/images/brand-image.png', '/images/dashboard-light.webp'],
  },
  'urban-jeans-preview': {
    name: 'Urban Jeans',
    code: 'TV-002',
    badge: 'Ambos',
    location: 'São Paulo, SP',
    category: 'Jeans',
    description: 'Simulacao local da pagina de anuncio. Serve para validar clique, layout e fluxo de navegacao.',
    images: ['/images/room-vetfun.jpg', '/images/room-vetfun2.png'],
  },
  'kids-style-preview': {
    name: 'Kids Style',
    code: 'TV-003',
    badge: 'Varejo',
    location: 'Belo Horizonte, MG',
    category: 'Moda Infantil',
    description: 'Exemplo sem depender do banco. Voce pode editar esta pagina depois para ficar mais parecida com a real.',
    images: ['/images/notification.png'],
  },
}

const store = computed(() => mockStores[props.slug] || {
  name: 'Anuncio Preview',
  code: 'TV-000',
  badge: 'Varejo',
  location: 'Local',
  category: 'Categoria',
  description: 'Anuncio de preview local.',
  images: ['/images/brand-image.png'],
})
</script>

<template>
  <div class="min-h-screen bg-background">
    <div class="container mx-auto px-4 py-8 space-y-6">
      <div class="flex items-center justify-between gap-3">
        <div>
          <p class="text-xs text-muted-foreground">Preview local</p>
          <h1 class="text-2xl font-bold">{{ store.name }}</h1>
          <p class="text-sm text-muted-foreground">Cod.: {{ store.code }} • {{ store.badge }}</p>
        </div>
        <Button as="a" href="/dev/preview-anuncios" variant="outline">
          <Icon icon="lucide:arrow-left" class="size-4 mr-2" />
          Voltar aos anuncios
        </Button>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="space-y-4">
          <img
            :src="store.images[0]"
            :alt="store.name"
            class="w-full h-[420px] object-cover rounded-xl border border-border"
          >
          <div class="grid grid-cols-3 gap-3">
            <img
              v-for="(img, index) in store.images"
              :key="`${img}-${index}`"
              :src="img"
              :alt="`${store.name} ${index + 1}`"
              class="h-24 w-full object-cover rounded-lg border border-border"
            >
          </div>
        </div>

        <div class="rounded-xl border border-border bg-card p-6 space-y-4">
          <div class="flex flex-wrap gap-2">
            <span class="inline-flex items-center rounded-full bg-muted px-3 py-1 text-xs">{{ store.badge }}</span>
            <span class="inline-flex items-center rounded-full bg-muted px-3 py-1 text-xs">{{ store.category }}</span>
          </div>

          <div class="flex items-center gap-2 text-sm text-muted-foreground">
            <Icon icon="lucide:map-pin" class="size-4" />
            <span>{{ store.location }}</span>
          </div>

          <p class="text-sm leading-6 text-muted-foreground">
            {{ store.description }}
          </p>

          <div class="pt-4 border-t border-border grid grid-cols-1 sm:grid-cols-2 gap-3">
            <Button variant="outline">
              <Icon icon="lucide:eye" class="size-4 mr-2" />
              Simulacao de detalhes
            </Button>
            <Button class="bg-green-600 hover:bg-green-700 text-white">
              <Icon icon="lucide:message-circle" class="size-4 mr-2" />
              WhatsApp (mock)
            </Button>
          </div>

          <p class="text-xs text-muted-foreground">
            Esta pagina existe apenas no ambiente local para testar a navegacao do botao "Detalhes".
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
