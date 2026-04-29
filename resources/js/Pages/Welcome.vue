<script setup>
import FeaturesCard from '@/Components/FeaturesCard.vue'
import PricingCard from '@/Components/PricingCard.vue'
import StoreCard from '@/Components/StoreCard.vue'
import FloatingMap from '@/Components/FloatingMap.vue'
import Accordion from '@/Components/shadcn/ui/accordion/Accordion.vue'
import AccordionContent from '@/Components/shadcn/ui/accordion/AccordionContent.vue'
import AccordionItem from '@/Components/shadcn/ui/accordion/AccordionItem.vue'
import AccordionTrigger from '@/Components/shadcn/ui/accordion/AccordionTrigger.vue'
import Badge from '@/Components/shadcn/ui/badge/Badge.vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import Card from '@/Components/shadcn/ui/card/Card.vue'
import Switch from '@/Components/shadcn/ui/switch/Switch.vue'
import Terminal from '@/Components/Terminal.vue'
import Tabs from '@/Components/shadcn/ui/tabs/Tabs.vue'
import TabsList from '@/Components/shadcn/ui/tabs/TabsList.vue'
import TabsTrigger from '@/Components/shadcn/ui/tabs/TabsTrigger.vue'
import { useSeoMetaTags } from '@/Composables/useSeoMetaTags.js'
import WebLayout from '@/Layouts/WebLayout.vue'
import { Icon } from '@iconify/vue'
import { ref, computed, onMounted, onUnmounted, onBeforeUnmount } from 'vue'

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
  plans: {
    type: Array,
    default: () => [],
  },
  featuredStores: {
    type: Array,
    default: () => [],
  },
  recentStores: {
    type: Array,
    default: () => [],
  },
  categoriesAtacado: {
    type: Array,
    default: () => [],
  },
  categoriesVarejo: {
    type: Array,
    default: () => [],
  },
  states: {
    type: Array,
    default: () => [],
  },
})

useSeoMetaTags(props.seo)

// Toggle state for pricing interval (false = monthly, true = yearly)
const isYearly = ref(false)

// Computed property to get the selected interval based on toggle
const selectedInterval = computed(() => isYearly.value ? 'year' : 'month')

// Filter plans to show only the selected interval pricing
const plansWithSelectedInterval = computed(() => {
  return props.plans.map(plan => ({
    ...plan,
    intervals: plan.intervals?.filter(interval => interval.code === selectedInterval.value) || []
  }))
})

// Calculate average discount percentage for yearly plans
const averageYearlyDiscount = computed(() => {
  const plansWithBothIntervals = props.plans.filter(plan => {
    const hasMonth = plan.intervals?.some(i => i.code === 'month')
    const hasYear = plan.intervals?.some(i => i.code === 'year')
    return hasMonth && hasYear && !plan.metadata?.is_default
  })

  if (plansWithBothIntervals.length === 0) return 0

  const discounts = plansWithBothIntervals.map(plan => {
    const monthlyPrice = plan.intervals.find(i => i.code === 'month')?.price || 0
    const yearlyPrice = plan.intervals.find(i => i.code === 'year')?.price || 0
    const yearlyMonthlyEquivalent = yearlyPrice / 12
    const discount = ((monthlyPrice - yearlyMonthlyEquivalent) / monthlyPrice) * 100
    return discount
  })

  const avgDiscount = discounts.reduce((sum, d) => sum + d, 0) / discounts.length
  return Math.round(avgDiscount)
})

// Function to open WhatsApp chat
function openWhatsAppChat() {
  const phone = '556231900204'
  const message = 'Olá! Gostaria de falar com um especialista sobre a Tanavitrine.'
  const url = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`
  window.open(url, '_blank')
}

// Search state
const searchType = ref('atacado')
const searchQuery = ref('')

function handleSearch() {
  // Redireciona para a página apropriada (Atacado ou Varejo) com busca simples.
  const route = searchType.value === 'atacado' ? '/atacado' : '/varejo'
  const params = new URLSearchParams()
  const normalizedQuery = searchQuery.value.trim()

  if (normalizedQuery) params.append('busca', normalizedQuery)

  const queryString = params.toString()
  window.location.href = queryString ? `${route}?${queryString}` : route
}

// Selector state
const selectedListingType = ref('destaques')
const showStickySelector = ref(false)

// Infinite scroll state
const currentPage = ref({ destaques: 1, recentes: 1 })
const hasMore = ref({ destaques: false, recentes: props.recentStores.length === 6 })
const isLoading = ref(false)
const allStores = ref({ destaques: [...props.featuredStores], recentes: [...props.recentStores] })

// Load more stores
async function loadMoreStores() {
  const listingType = selectedListingType.value
  if (listingType === 'destaques') return
  if (isLoading.value || !hasMore.value[listingType]) return

  isLoading.value = true

  try {
    const nextPage = currentPage.value[listingType] + 1
    const response = await fetch(
      `/api/stores/load-more?type=${listingType}&page=${nextPage}`
    )
    const data = await response.json()

    if (data.stores && data.stores.length > 0) {
      allStores.value[listingType].push(...data.stores)
      currentPage.value[listingType] = nextPage
      hasMore.value[listingType] = Boolean(data.hasMore)
    } else {
      hasMore.value[listingType] = false
    }
  } catch (error) {
    console.error('Error loading more stores:', error)
  } finally {
    isLoading.value = false
  }
}

// Handle scroll to show/hide sticky selector and infinite scroll
const handleScroll = () => {
  const selectorSection = document.getElementById('selector-section')
  if (selectorSection) {
    const rect = selectorSection.getBoundingClientRect()
    // Show sticky selector when the section goes above the viewport (considering header height)
    showStickySelector.value = rect.bottom < 100
  }

  // Check if user has scrolled near the bottom
  const scrollPosition = window.innerHeight + window.scrollY
  const threshold = document.documentElement.scrollHeight - 500

  if (scrollPosition >= threshold && !isLoading.value && hasMore.value[selectedListingType.value]) {
    loadMoreStores()
  }
}

// Lifecycle hooks for scroll listener
onMounted(() => {
  window.addEventListener('scroll', handleScroll)
  handleScroll() // Initial check
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})

// Filter listings based on selected type
const filteredListings = computed(() => {
  return allStores.value[selectedListingType.value] || []
})

function normalizeStoreType(type) {
  return String(type || '')
    .trim()
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
}

function hasPhysicalPresence(type) {
  const normalized = normalizeStoreType(type)
  return [
    'ambos',
    'fisica',
    'virtual / fisica',
    'virtual/fisica',
    'virtual e fisica',
    'fisica e virtual',
  ].includes(normalized)
}

const storesForMap = computed(() => {
  return filteredListings.value.filter((store) => {
    const physicalStore = hasPhysicalPresence(store.storeType)
    const hasCoordinates = store.latitude !== null && store.longitude !== null
    return store.show_on_map === true && physicalStore && hasCoordinates
  })
})

const showBackToTop = ref(false)
const lastScrollTop = ref(0)

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

onMounted(() => {
  window.addEventListener('scroll', handleBackToTopVisibility, { passive: true })
  document.addEventListener('scroll', handleBackToTopVisibility, { passive: true, capture: true })
  handleBackToTopVisibility()
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', handleBackToTopVisibility)
  document.removeEventListener('scroll', handleBackToTopVisibility, true)
})

</script>

<template>
  <WebLayout :can-login="canLogin" :can-register="canRegister" :show-floating-whats-app="false">
    <!-- Sticky Selector Buttons (shown only when section is out of view) -->
    <template #sticky-selector>
      <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-2"
      >
        <div v-show="showStickySelector" class="py-4 bg-background/95 backdrop-blur-sm supports-backdrop-filter:bg-background/60 shadow-md">
        <div class="mx-auto max-w-3xl">
          <div class="flex flex-row items-center justify-center gap-3 px-4">
            <!-- Destaques Button -->
            <div class="flex items-center">
              <div class="flex h-16 w-16 sm:h-20 sm:w-20 items-center justify-center rounded-2xl bg-green-200 z-10 flex-shrink-0">
                <Icon icon="lucide:trophy" class="size-8 sm:size-10 text-foreground" aria-hidden="true" />
              </div>
              <Button
                size="lg"
                class="-ml-4 sm:-ml-6 font-bold flex-1 min-w-0"
                :variant="selectedListingType === 'destaques' ? 'default' : 'outline'"
                @click="selectedListingType = 'destaques'"
              >
                DESTAQUES
              </Button>
            </div>

            <!-- Recentes Button -->
            <div class="flex items-center">
              <div class="flex h-16 w-16 sm:h-20 sm:w-20 items-center justify-center rounded-2xl bg-green-200 z-10 flex-shrink-0">
                <Icon icon="lucide:badge-plus" class="size-8 sm:size-10 text-foreground" aria-hidden="true" />
              </div>
              <Button
                size="lg"
                class="-ml-4 sm:-ml-6 font-bold flex-1 min-w-0"
                :variant="selectedListingType === 'recentes' ? 'default' : 'outline'"
                @click="selectedListingType = 'recentes'"
              >
                RECENTES
              </Button>
            </div>
          </div>
        </div>
        </div>
      </Transition>
    </template>

    <!-- Hero Section -->
    <section class="relative overflow-hidden border-b border-orange-200 py-10 sm:py-20">
      <!-- Background Image -->
      <div class="absolute inset-0 -z-10">
        <img
          src="/images/home_index.png"
          alt=""
          class="h-full w-full object-cover"
        />
      </div>
      <div class="absolute inset-0 -z-10 bg-gradient-to-b from-teal-950/60 via-teal-900/35 to-teal-950/65" />

      <div class="container mx-auto px-4 text-center relative z-10">
        <!-- Badge -->
        <div class="mb-8 inline-flex justify-center">
          <Badge variant="outline" class="rounded-full border border-yellow-400/80 bg-teal-900/40 px-4 py-1 text-xs text-white sm:text-sm">
            <Icon icon="lucide:award" class="size-4" aria-hidden="true" /> Fornecedores Verificados
          </Badge>
        </div>

        <!-- Main Heading -->
        <div class="mx-auto max-w-4xl">
          <h1
            class="text-4xl font-extrabold tracking-tight sm:text-5xl md:text-6xl lg:text-[5.2rem]"
            :style="{ contain: 'layout paint' }"
          >
            <span class="block text-white">Compre direto com</span>
            <span
              class="mt-2 block bg-linear-to-r from-yellow-400 via-amber-300 to-orange-300 bg-clip-text text-transparent"
            >
              Os Melhores Fornecedores
            </span>
          </h1>
        </div>

        <!-- Subtitle - Add priority hint -->
        <p
          class="mx-auto mt-7 max-w-xl text-center text-base text-white/90 sm:text-lg md:text-xl"
          :style="{ contain: 'layout paint' }"
          fetchpriority="high"
        >
        Conecte-se com as melhores lojas disponíveis no maior catálogo de fornecedores atacadista de moda  do Brasil.
        </p>

        <!-- Search Tool -->
        <div class="mt-10 mx-auto max-w-4xl">
          <Card class="border border-teal-200/35 bg-teal-900/35 backdrop-blur-md shadow-[0_20px_40px_rgba(0,0,0,0.35)]">
            <div class="space-y-4 px-6 py-6 sm:px-8 sm:py-8">
              <Tabs v-model="searchType" default-value="atacado" class="w-full">
                <TabsList class="grid w-full grid-cols-2 rounded-xl border border-teal-200/30 bg-teal-950/40 p-1">
                  <TabsTrigger
                    value="atacado"
                    class="h-10 rounded-lg border border-transparent text-sm font-semibold flex items-center justify-center gap-2 whitespace-nowrap text-teal-100/85 transition-all duration-200 data-[state=active]:border-teal-400 data-[state=active]:bg-teal-500 data-[state=active]:text-white"
                  >
                    <Icon icon="lucide:shopping-cart" class="size-4" />
                    <span>Atacado</span>
                  </TabsTrigger>
                  <TabsTrigger
                    value="varejo"
                    class="h-10 rounded-lg border border-transparent text-sm font-semibold flex items-center justify-center gap-2 whitespace-nowrap text-teal-100/85 transition-all duration-200 data-[state=active]:border-teal-400 data-[state=active]:bg-teal-500 data-[state=active]:text-white"
                  >
                    <Icon icon="lucide:store" class="size-4" />
                    <span>Varejo</span>
                  </TabsTrigger>
                </TabsList>
              </Tabs>
              <p class="text-xs font-medium text-teal-100/90">
                Você está buscando em:
                <span class="font-semibold text-yellow-300">{{ searchType === 'atacado' ? 'Atacado' : 'Varejo' }}</span>
              </p>

              <form class="space-y-3" @submit.prevent="handleSearch">
                <div class="relative">
                  <Icon icon="lucide:search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-teal-100/80" />
                  <input
                    v-model="searchQuery"
                    type="text"
                    class="h-11 w-full rounded-lg border border-teal-200/35 bg-white/95 pl-10 pr-4 text-sm text-teal-900 outline-none placeholder:text-teal-700/70 focus:border-yellow-400 focus:ring-1 focus:ring-yellow-300/50"
                    placeholder="Busque lojas e fornecedores de moda"
                  >
                </div>
                <Button type="submit" size="lg" class="h-11 w-full cursor-pointer rounded-lg bg-yellow-400 text-teal-900 hover:bg-yellow-300">
                  Buscar
                </Button>
              </form>
            </div>
          </Card>
        </div>

      </div>

    </section>

        <!-- Selector Section -->
        <section id="selector-section" class="relative">
      <div class="container relative z-10 mx-auto px-4 pt-16 sm:pt-24 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-12">
          <p class="text-sm font-medium tracking-wider text-muted-foreground uppercase mb-2">
            FORNECEDORES VERIFICADOS E SELECIONADOS
          </p>
          <h2 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
            Confira as nossas Vitrines!
          </h2>
        </div>

        <!-- Selector Buttons (in section) -->
        <div class="py-4">
          <div class="mx-auto max-w-3xl">
            <div class="flex flex-row items-center justify-center gap-3 px-4">
              <!-- Destaques Button -->
              <div class="flex items-center">
                <div class="flex h-16 w-16 sm:h-20 sm:w-20 items-center justify-center rounded-2xl bg-green-200 z-10 flex-shrink-0">
                  <Icon icon="lucide:trophy" class="size-8 sm:size-10 text-foreground" aria-hidden="true" />
                </div>
                <Button
                  size="lg"
                  class="-ml-4 sm:-ml-6 font-bold flex-1 min-w-0"
                  :variant="selectedListingType === 'destaques' ? 'default' : 'outline'"
                  @click="selectedListingType = 'destaques'"
                >
                  DESTAQUES
                </Button>
              </div>

              <!-- Recentes Button -->
              <div class="flex items-center">
                <div class="flex h-16 w-16 sm:h-20 sm:w-20 items-center justify-center rounded-2xl bg-green-200 z-10 flex-shrink-0">
                  <Icon icon="lucide:badge-plus" class="size-8 sm:size-10 text-foreground" aria-hidden="true" />
                </div>
                <Button
                  size="lg"
                  class="-ml-4 sm:-ml-6 font-bold flex-1 min-w-0"
                  :variant="selectedListingType === 'recentes' ? 'default' : 'outline'"
                  @click="selectedListingType = 'recentes'"
                >
                  RECENTES
                </Button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Store Listings Section -->
    <section class="py-16 bg-background">
      <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-7xl mx-auto">
          <div
            v-for="store in filteredListings"
            :key="store.id"
            class="h-full"
          >
            <StoreCard :store="store" />
          </div>
        </div>

        <!-- Loading Indicator -->
        <div v-if="isLoading" class="flex justify-center items-center py-8">
          <div class="flex items-center gap-2 text-muted-foreground">
            <Icon icon="lucide:loader-2" class="size-6 animate-spin" />
            <span>Carregando mais lojas...</span>
          </div>
        </div>

        <!-- No more stores message -->
        <div v-else-if="!hasMore[selectedListingType] && filteredListings.length > 0" class="flex justify-center py-8">
          <p class="text-muted-foreground text-sm">
            Todas as lojas foram carregadas
          </p>
        </div>

        <!-- No stores message -->
        <div v-else-if="filteredListings.length === 0" class="flex justify-center py-16">
          <div class="text-center">
            <Icon icon="lucide:store" class="size-16 mx-auto text-muted-foreground mb-4" />
            <p class="text-muted-foreground text-lg">
              Nenhuma loja encontrada
            </p>
          </div>
        </div>
      </div>
    </section>

  </WebLayout>
  <button
    v-show="showBackToTop"
    type="button"
    class="fixed bottom-[calc(6rem+env(safe-area-inset-bottom))] left-1/2 z-40 -translate-x-1/2 inline-flex items-center gap-2 rounded-full border border-green-700 bg-green-600 px-4 py-2.5 shadow-lg transition hover:bg-green-700 hover:shadow-xl md:bottom-6"
    aria-label="Voltar ao topo"
    title="Voltar ao topo"
    @click="scrollToTop"
  >
    <Icon icon="lucide:arrow-up" class="size-4 text-white" />
    <span class="text-sm font-medium text-white">Voltar ao topo</span>
  </button>

  <FloatingMap
    v-if="storesForMap.length > 0"
    :stores="storesForMap"
    title="Mapa de Lojas Físicas"
  />

</template>
