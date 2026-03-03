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
import TabsContent from '@/Components/shadcn/ui/tabs/TabsContent.vue'
import TabsList from '@/Components/shadcn/ui/tabs/TabsList.vue'
import TabsTrigger from '@/Components/shadcn/ui/tabs/TabsTrigger.vue'
import Select from '@/Components/shadcn/ui/select/Select.vue'
import SelectContent from '@/Components/shadcn/ui/select/SelectContent.vue'
import SelectItem from '@/Components/shadcn/ui/select/SelectItem.vue'
import SelectTrigger from '@/Components/shadcn/ui/select/SelectTrigger.vue'
import SelectValue from '@/Components/shadcn/ui/select/SelectValue.vue'
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/shadcn/ui/popover'
import { Checkbox } from '@/Components/shadcn/ui/checkbox'
import { useSeoMetaTags } from '@/Composables/useSeoMetaTags.js'
import WebLayout from '@/Layouts/WebLayout.vue'
import { Icon } from '@iconify/vue'
import { ref, computed, onMounted, onUnmounted, onBeforeUnmount, watch } from 'vue'

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

// Search filters state
const searchType = ref('atacado')
const searchFilters = ref({
  categorias: [], // Changed to array for multiple selection
  tipoLoja: '',
  cidade: '',
  estado: '',
  genero: ''
})

const categorias = computed(() =>
  (searchType.value === 'atacado' ? props.categoriesAtacado : props.categoriesVarejo)
    .map(c => ({ name: c.name, count: Number(c.count || 0) }))
)
const tiposLoja = [
  { value: 'virtual', label: 'Loja Virtual' },
  { value: 'ambos', label: 'Virtual / Física' },
]
const estados = computed(() => props.states || [])
const generos = ['Masculino', 'Feminino', 'Unissex']

function toggleCategoria(categoria) {
  const index = searchFilters.value.categorias.indexOf(categoria)
  if (index > -1) {
    searchFilters.value.categorias.splice(index, 1)
  } else {
    searchFilters.value.categorias.push(categoria)
  }
}

const selectedCategoriasText = computed(() => {
  if (searchFilters.value.categorias.length === 0) return 'Selecione'
  if (searchFilters.value.categorias.length === 1) return searchFilters.value.categorias[0]
  return `${searchFilters.value.categorias.length} selecionadas`
})

watch(searchType, () => {
  searchFilters.value.categorias = []
})

function handleSearch() {
  // Redirecionar para a página apropriada (Atacado ou Varejo) com filtros
  const route = searchType.value === 'atacado' ? '/atacado' : '/varejo'
  const params = new URLSearchParams()
  const selectedType = searchFilters.value.tipoLoja
  const isVirtualOnly = selectedType === 'virtual'

  // Adicionar filtros preenchidos aos query parameters
  if (searchFilters.value.categorias.length > 0) {
    searchFilters.value.categorias.forEach(cat => params.append('categorias[]', cat))
  }
  if (selectedType) params.append('tipoLoja', selectedType)
  if (searchFilters.value.estado && !isVirtualOnly) {
    params.append('estado', searchFilters.value.estado)
    // State-based search only applies to stores with physical presence.
    if (!selectedType) params.append('tipoLoja', 'ambos')
  }
  if (searchFilters.value.cidade) params.append('cidade', searchFilters.value.cidade)
  if (searchFilters.value.genero) params.append('genero', searchFilters.value.genero)

  const queryString = params.toString()
  window.location.href = queryString ? `${route}?${queryString}` : route
}

// Selector state
const selectedListingType = ref('recentes')
const showStickySelector = ref(false)

// Infinite scroll state
const currentPage = ref({ destaques: 1, recentes: 1 })
const hasMore = ref({ destaques: true, recentes: true })
const isLoading = ref(false)
const allStores = ref({ destaques: [...props.featuredStores], recentes: [...props.recentStores] })

// Load more stores
async function loadMoreStores() {
  if (isLoading.value || !hasMore.value[selectedListingType.value]) return

  isLoading.value = true

  try {
    const response = await fetch(
      `/api/stores/load-more?type=${selectedListingType.value}&page=${currentPage.value[selectedListingType.value] + 1}`
    )
    const data = await response.json()

    if (data.stores && data.stores.length > 0) {
      allStores.value[selectedListingType.value].push(...data.stores)
      currentPage.value[selectedListingType.value] = data.nextPage
      hasMore.value[selectedListingType.value] = data.hasMore
    } else {
      hasMore.value[selectedListingType.value] = false
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
  <WebLayout :can-login="canLogin" :can-register="canRegister">
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
          src="/images/bg-hero.png"
          alt=""
          class="h-full w-full object-cover"
        />
      </div>

      <!-- Overlay with transparency -->
      <div class="absolute inset-0 -z-10 bg-gradient-to-r from-teal-900/90 via-teal-700/80 to-teal-950/90" />

      <div class="container mx-auto px-4 text-center relative z-10">
        <!-- Badge -->
        <div class="mb-8 inline-flex justify-center">
          <Badge variant="outline" class="rounded-full border border-yellow-500 bg-primary/10 px-4 py-1 text-xs text-white sm:text-sm">
            <Icon icon="lucide:award" class="size-4" aria-hidden="true" /> Fornecedores Verificados
          </Badge>
        </div>

        <!-- Main Heading -->
        <div class="mx-auto max-w-4xl">
          <h1
            class="text-4xl font-extrabold tracking-tight sm:text-5xl md:text-6xl lg:text-7xl"
            :style="{ contain: 'layout paint' }"
          >
            <span class="block text-white">Compre direto com</span>
            <span
              class="mt-2 block bg-linear-to-r from-yellow-500 via-rose-400 to-amber-500 bg-clip-text text-transparent"
            >
              Os Melhores Fornecedores
            </span>
          </h1>
        </div>

        <!-- Subtitle - Add priority hint -->
        <p
          class="mx-auto mt-6 max-w-2xl text-center text-base text-white sm:text-lg md:text-xl"
          :style="{ contain: 'layout paint' }"
          fetchpriority="high"
        >
        Conecte-se com as melhores lojas disponíveis no maior catálogo de fornecedores atacadista de moda  do Brasil.
        </p>

        <!-- Search Tool -->
        <div class="mt-10 mx-auto max-w-4xl">
          <Card class="bg-white/95 backdrop-blur-sm shadow-2xl">
            <Tabs v-model="searchType" default-value="atacado" class="w-full">
              <TabsList class="grid w-full grid-cols-2 mb-6">
                <TabsTrigger value="atacado" class="text-base flex items-center justify-center gap-2 whitespace-nowrap">
                  <Icon icon="lucide:shopping-cart" class="size-4" />
                  <span>Atacado</span>
                </TabsTrigger>
                <TabsTrigger value="varejo" class="text-base flex items-center justify-center gap-2 whitespace-nowrap">
                  <Icon icon="lucide:store" class="size-4" />
                  <span>Varejo</span>
                </TabsTrigger>
              </TabsList>

              <TabsContent value="atacado" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 px-6">
                  <!-- Categoria -->
                  <div class="space-y-2">
                    <label class="text-sm font-medium text-foreground">Categoria</label>
                    <Popover>
                      <PopoverTrigger as-child>
                        <button
                          type="button"
                          role="combobox"
                          class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm font-normal ring-offset-background data-placeholder:text-muted-foreground outline-none focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                          {{ selectedCategoriasText }}
                          <Icon icon="lucide:chevron-down" class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                        </button>
                      </PopoverTrigger>
                      <PopoverContent class="w-[var(--radix-popover-trigger-width)] p-0" align="start">
                        <div class="max-h-64 overflow-y-auto p-4 space-y-2">
                          <div
                            v-for="cat in categorias"
                            :key="cat.name"
                            class="flex items-center space-x-2 cursor-pointer hover:bg-muted p-2 rounded"
                            @click="toggleCategoria(cat.name)"
                          >
                            <Checkbox
                              :id="`cat-atacado-${cat.name}`"
                              :checked="searchFilters.categorias.includes(cat.name)"
                            />
                            <label
                              :for="`cat-atacado-${cat.name}`"
                              class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 cursor-pointer flex-1"
                            >
                              {{ cat.name }} ({{ cat.count }})
                            </label>
                          </div>
                        </div>
                      </PopoverContent>
                    </Popover>
                  </div>

                  <!-- Tipo de Loja -->
                  <div class="space-y-2">
                    <label class="text-sm font-medium text-foreground">Tipo de Loja</label>
                    <Select v-model="searchFilters.tipoLoja">
                      <SelectTrigger>
                        <SelectValue placeholder="Selecione" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem v-for="tipo in tiposLoja" :key="tipo.value" :value="tipo.value">
                          {{ tipo.label }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <!-- Localização -->
                  <div class="space-y-2">
                    <label class="text-sm font-medium text-foreground">Localização</label>
                    <Select v-model="searchFilters.estado">
                      <SelectTrigger :disabled="searchFilters.tipoLoja === 'virtual'">
                        <SelectValue placeholder="Estado" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem v-for="estado in estados" :key="estado" :value="estado">
                          {{ estado }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <!-- Gênero -->
                  <div class="space-y-2">
                    <label class="text-sm font-medium text-foreground">Gênero</label>
                    <Select v-model="searchFilters.genero">
                      <SelectTrigger>
                        <SelectValue placeholder="Selecione" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem v-for="gen in generos" :key="gen" :value="gen">
                          {{ gen }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>

                <div class="px-6 pb-6">
                  <Button @click="handleSearch" size="lg" class="w-full cursor-pointer">
                    <Icon icon="lucide:search" class="size-4 mr-2" />
                    Buscar Fornecedores
                  </Button>
                </div>
              </TabsContent>

              <TabsContent value="varejo" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 px-6">
                  <!-- Categoria -->
                  <div class="space-y-2">
                    <label class="text-sm font-medium text-foreground">Categoria</label>
                    <Popover>
                      <PopoverTrigger as-child>
                        <button
                          type="button"
                          role="combobox"
                          class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm font-normal ring-offset-background data-placeholder:text-muted-foreground outline-none focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                          {{ selectedCategoriasText }}
                          <Icon icon="lucide:chevron-down" class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                        </button>
                      </PopoverTrigger>
                      <PopoverContent class="w-[var(--radix-popover-trigger-width)] p-0" align="start">
                        <div class="max-h-64 overflow-y-auto p-4 space-y-2">
                          <div
                            v-for="cat in categorias"
                            :key="cat.name"
                            class="flex items-center space-x-2 cursor-pointer hover:bg-muted p-2 rounded"
                            @click="toggleCategoria(cat.name)"
                          >
                            <Checkbox
                              :id="`cat-varejo-${cat.name}`"
                              :checked="searchFilters.categorias.includes(cat.name)"
                            />
                            <label
                              :for="`cat-varejo-${cat.name}`"
                              class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 cursor-pointer flex-1"
                            >
                              {{ cat.name }} ({{ cat.count }})
                            </label>
                          </div>
                        </div>
                      </PopoverContent>
                    </Popover>
                  </div>

                  <!-- Tipo de Loja -->
                  <div class="space-y-2">
                    <label class="text-sm font-medium text-foreground">Tipo de Loja</label>
                    <Select v-model="searchFilters.tipoLoja">
                      <SelectTrigger>
                        <SelectValue placeholder="Selecione" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem v-for="tipo in tiposLoja" :key="tipo.value" :value="tipo.value">
                          {{ tipo.label }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <!-- Localização -->
                  <div class="space-y-2">
                    <label class="text-sm font-medium text-foreground">Localização</label>
                    <Select v-model="searchFilters.estado">
                      <SelectTrigger :disabled="searchFilters.tipoLoja === 'virtual'">
                        <SelectValue placeholder="Estado" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem v-for="estado in estados" :key="estado" :value="estado">
                          {{ estado }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <!-- Gênero -->
                  <div class="space-y-2">
                    <label class="text-sm font-medium text-foreground">Gênero</label>
                    <Select v-model="searchFilters.genero">
                      <SelectTrigger>
                        <SelectValue placeholder="Selecione" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem v-for="gen in generos" :key="gen" :value="gen">
                          {{ gen }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>

                <div class="px-6 pb-6">
                  <Button @click="handleSearch" size="lg" class="w-full cursor-pointer">
                    <Icon icon="lucide:search" class="size-4 mr-2" />
                    Buscar Lojas
                  </Button>
                </div>
              </TabsContent>
            </Tabs>
          </Card>
        </div>

        <!-- Trust Badges -->
        <div class="mt-8 sm:mt-12">
          <p class="text-sm text-yellow-500">
            As Melhores marcas no atacado
          </p>
        </div>
      </div>

      <!-- Background Effects -->
      <div
        class="absolute inset-0 -z-10 h-full w-full bg-[linear-gradient(to_right,#4f4f4f2e_1px,transparent_1px),linear-gradient(to_bottom,#4f4f4f2e_1px,transparent_1px)] bg-[size:14px_24px]"
      />
      <div
        class="absolute left-0 right-0 top-0 -z-10 m-auto h-[310px] w-[310px] rounded-full bg-primary/20 opacity-20 blur-[100px]"
      />
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
          <StoreCard
            v-for="store in filteredListings"
            :key="store.id"
            :store="store"
          />
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
    :stores="storesForMap"
    title="Mapa de Lojas Físicas"
  />
</template>
