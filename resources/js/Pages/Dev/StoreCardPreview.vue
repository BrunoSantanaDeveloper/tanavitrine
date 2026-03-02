<script setup>
import StoreCard from '@/Components/StoreCard.vue'

const mockStores = [
  {
    slug: 'bella-moda-preview',
    previewDetailUrl: '/dev/preview-anuncios/bella-moda-preview',
    code: 'TV-001',
    name: 'Bella Moda Feminina',
    badge: 'Varejo',
    featured: true,
    can_favorite: false,
    minOrder: 'Pedido mínimo: 12 peças',
    category: 'Moda Feminina',
    location: 'Goiânia, GO',
    description: 'Loja especializada em moda feminina casual e social, com peças de alta rotatividade e envio para todo o Brasil.',
    whatsapp: '(62) 99999-0001',
    logo: '/tanavitrine_light_icon1.png',
    image: '/images/brand-image.png',
    images: [
      '/images/brand-image.png',
      '/images/dashboard-light.webp',
      '/images/dashboard-dark.webp',
    ],
  },
  {
    slug: 'urban-jeans-preview',
    previewDetailUrl: '/dev/preview-anuncios/urban-jeans-preview',
    code: 'TV-002',
    name: 'Urban Jeans',
    badge: 'Ambos',
    featured: false,
    can_favorite: false,
    minOrder: 'Pedido mínimo: 20 peças',
    category: 'Jeans',
    location: 'São Paulo, SP',
    description: 'Linha completa de jeans masculino e feminino, com fabricação própria e pronta entrega.',
    whatsapp: '(11) 98888-0002',
    logo: null,
    image: '/images/room-vetfun.jpg',
    images: [
      '/images/room-vetfun.jpg',
      '/images/room-vetfun2.png',
    ],
  },
  {
    slug: 'kids-style-preview',
    previewDetailUrl: '/dev/preview-anuncios/kids-style-preview',
    code: 'TV-003',
    name: 'Kids Style',
    badge: 'Varejo',
    featured: false,
    can_favorite: false,
    minOrder: null,
    category: 'Moda Infantil',
    location: 'Belo Horizonte, MG',
    description: 'Coleções infantis com foco em conforto e estampas exclusivas. Este card está sem imagem para testar o placeholder.',
    whatsapp: '(31) 97777-0003',
    logo: '/tanavitrine_light_icon1.png',
    image: null,
    images: [],
  },
]
</script>

<template>
  <div class="min-h-screen bg-background">
    <div class="container mx-auto px-4 py-8 space-y-6">
      <div class="rounded-xl border border-border bg-card p-4">
        <h1 class="text-xl font-bold">Preview de Anuncios (Local)</h1>
        <p class="text-sm text-muted-foreground mt-1">
          Pagina de teste com dados falsos para ajustar o visual dos cards sem depender do banco.
        </p>
        <p class="text-xs text-muted-foreground mt-2">
          Rota local: <code>/dev/preview-anuncios</code>
        </p>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <StoreCard
          v-for="store in mockStores"
          :key="store.slug"
          :store="store"
          :show-favorite-button="false"
        />
      </div>
    </div>
  </div>
</template>
