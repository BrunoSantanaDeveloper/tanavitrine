<script setup>
import Button from '@/Components/shadcn/ui/button/Button.vue'
import StoreCard from '@/Components/StoreCard.vue'
import { useSeoMetaTags } from '@/Composables/useSeoMetaTags.js'
import WebLayout from '@/Layouts/WebLayout.vue'
import { Icon } from '@iconify/vue'
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
      <div class="site-shell">
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
      <div class="site-shell">
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
        <div v-else class="store-grid">
          <div v-for="store in stores" :key="store.id" class="flex min-w-0 flex-col">
            <StoreCard :store="store" :show-favorite-button="false" />
            <div class="mt-2 flex items-center justify-between gap-2 border-t border-border pt-2">
              <span class="text-[11px] text-muted-foreground">Salva em {{ store.favorited_at }}</span>
              <Button size="sm" variant="ghost" class="text-red-600 hover:text-red-700" @click="removeFavorite(store.slug)">
                <Icon icon="lucide:heart-off" class="mr-1 size-3.5" aria-hidden="true" />
                Remover
              </Button>
            </div>
          </div>
        </div>
      </div>
    </section>
  </WebLayout>
</template>
