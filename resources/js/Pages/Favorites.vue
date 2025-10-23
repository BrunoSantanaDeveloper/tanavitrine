<script setup>
import Badge from '@/Components/shadcn/ui/badge/Badge.vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import Card from '@/Components/shadcn/ui/card/Card.vue'
import { useSeoMetaTags } from '@/Composables/useSeoMetaTags.js'
import WebLayout from '@/Layouts/WebLayout.vue'
import { Icon } from '@iconify/vue'
import { Link } from '@inertiajs/vue3'
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps({
  canLogin: {
    type: Boolean,
  },
  canRegister: {
    type: Boolean,
  },
  seo: {
    type: Object,
    default: () => null,
  },
  stores: {
    type: Array,
    default: () => [],
  },
})

useSeoMetaTags(props.seo)

const stores = ref(props.stores)

async function removeFavorite(storeSlug) {
  if (!confirm('Deseja remover esta loja dos favoritos?')) {
    return
  }

  try {
    await axios.post(`/loja/${storeSlug}/favorite`)
    // Remove from list
    stores.value = stores.value.filter(store => store.slug !== storeSlug)
  } catch (error) {
    alert('Erro ao remover favorito')
  }
}
</script>

<template>
  <WebLayout :can-login="canLogin" :can-register="canRegister">
    <!-- Header Section -->
    <section class="bg-gradient-to-br from-teal-900 to-teal-700 py-16 border-b">
      <div class="container mx-auto px-4">
        <div class="flex items-center gap-3 mb-4">
          <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-white/10">
            <Icon icon="lucide:heart" class="size-6 text-white" />
          </div>
          <div>
            <h1 class="text-3xl font-bold text-white">Meus Favoritos</h1>
            <p class="text-white/80">
              {{ stores.length }} {{ stores.length === 1 ? 'loja favorita' : 'lojas favoritas' }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Store Listings Section -->
    <section class="py-8 bg-muted/30 min-h-[60vh]">
      <div class="container mx-auto px-4">
        <!-- Empty State -->
        <div v-if="stores.length === 0" class="text-center py-16">
          <Icon icon="lucide:heart-off" class="size-20 mx-auto text-muted-foreground mb-4" />
          <h2 class="text-2xl font-bold mb-2">Nenhuma loja favorita ainda</h2>
          <p class="text-muted-foreground mb-6">
            Comece a explorar e salve suas lojas favoritas para acessá-las rapidamente
          </p>
          <div class="flex gap-4 justify-center">
            <Button as="a" href="/atacado" size="lg">
              <Icon icon="lucide:shopping-cart" class="size-4 mr-2" />
              Explorar Atacado
            </Button>
            <Button as="a" href="/varejo" variant="outline" size="lg">
              <Icon icon="lucide:store" class="size-4 mr-2" />
              Explorar Varejo
            </Button>
          </div>
        </div>

        <!-- Store Cards Grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-7xl mx-auto">
          <Card
            v-for="store in stores"
            :key="store.id"
            class="overflow-hidden hover:shadow-lg transition-shadow duration-300"
            :class="{ 'ring-2 ring-primary/20': store.featured }"
          >
            <div class="grid grid-cols-1 sm:grid-cols-5 gap-0">
              <!-- Image Section -->
              <div class="sm:col-span-2 relative">
                <img
                  :src="store.image"
                  :alt="store.name"
                  class="w-full h-64 sm:h-full object-cover"
                />
                <!-- Featured Badge -->
                <Badge
                  v-if="store.featured"
                  class="absolute top-3 left-3 bg-primary text-primary-foreground shadow-lg"
                >
                  <Icon icon="lucide:star" class="size-3 mr-1" />
                  Destaque
                </Badge>
                <!-- Favorited Date -->
                <Badge class="absolute bottom-3 left-3 bg-black/70 text-white">
                  <Icon icon="lucide:heart" class="size-3 mr-1" />
                  {{ store.favorited_at }}
                </Badge>
              </div>

              <!-- Content Section -->
              <div class="sm:col-span-3 p-6 flex flex-col justify-between">
                <!-- Header -->
                <div>
                  <div class="flex items-start justify-between mb-3">
                    <div class="flex-1">
                      <p class="text-xs text-muted-foreground mb-1">Cod.: {{ store.code }}</p>
                      <Badge variant="secondary" class="mb-2 text-xs">
                        {{ store.badge }}
                      </Badge>
                      <h3 class="text-xl font-bold text-foreground mb-2">
                        {{ store.name }}
                      </h3>
                    </div>
                    <!-- Remove Favorite Button -->
                    <button
                      @click="removeFavorite(store.slug)"
                      class="p-2 hover:bg-muted rounded-lg transition-colors"
                      title="Remover dos favoritos"
                    >
                      <Icon icon="lucide:heart-off" class="size-5 text-red-500" />
                    </button>
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
                      class="flex-1"
                      :as="Link"
                      :href="`/loja/${store.slug}`"
                    >
                      <Icon icon="lucide:eye" class="size-4 mr-1" />
                      Ver Vitrine
                    </Button>
                    <Button
                      size="sm"
                      class="flex-1 bg-green-600 hover:bg-green-700 text-white"
                      @click="() => window.open(`https://wa.me/${store.whatsapp}`, '_blank')"
                    >
                      <Icon icon="lucide:message-circle" class="size-4 mr-1" />
                      WhatsApp
                    </Button>
                  </div>
                </div>
              </div>
            </div>
          </Card>
        </div>
      </div>
    </section>
  </WebLayout>
</template>
