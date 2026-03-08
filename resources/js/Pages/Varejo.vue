<script setup>
import Button from '@/Components/shadcn/ui/button/Button.vue'
import Input from '@/Components/shadcn/ui/input/Input.vue'
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/shadcn/ui/popover'
import { Checkbox } from '@/Components/shadcn/ui/checkbox'
import StoreCard from '@/Components/StoreCard.vue'
import FloatingMap from '@/Components/FloatingMap.vue'
import { useSeoMetaTags } from '@/Composables/useSeoMetaTags.js'
import WebLayout from '@/Layouts/WebLayout.vue'
import { Icon } from '@iconify/vue'
import { ref, computed, onMounted, watch, onBeforeUnmount } from 'vue'

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
  categories: {
    type: Array,
    default: () => [],
  },
  subcategories: {
    type: Array,
    default: () => [],
  },
  states: {
    type: Array,
    default: () => [],
  },
  cities: {
    type: Array,
    default: () => [],
  },
})

useSeoMetaTags(props.seo)

// Search filters state (more detailed than hero)
const filters = ref({
  categorias: [], // Changed to array for multiple selection
  subcategorias: [], // Changed to array for multiple selection
  tipoLoja: '',
  estado: '',
  cidade: '',
  genero: '',
  faixaPreco: '',
  busca: ''
})

// Apply query parameters from URL on mount
onMounted(() => {
  const urlParams = new URLSearchParams(window.location.search)
  // Handle multiple categories
  if (urlParams.has('categorias[]')) {
    filters.value.categorias = urlParams.getAll('categorias[]')
  }
  if (urlParams.has('tipoLoja')) filters.value.tipoLoja = urlParams.get('tipoLoja')
  if (urlParams.has('estado')) filters.value.estado = urlParams.get('estado')
  if (urlParams.has('cidade')) filters.value.cidade = urlParams.get('cidade')
  if (urlParams.has('genero')) filters.value.genero = urlParams.get('genero')
})

watch(() => filters.value.tipoLoja, (newType) => {
  if (newType === 'virtual') {
    filters.value.estado = ''
    filters.value.cidade = ''
  }
})

const categorias = computed(() =>
  (props.categories || []).map(category => ({
    name: category.name,
    count: Number(category.count || 0),
  })),
)
const subcategorias = computed(() =>
  (props.subcategories || []).map(subcategory => ({
    name: subcategory.name,
    count: Number(subcategory.count || 0),
  })),
)
const estados = computed(() => props.states)
const cidades = computed(() => {
  // Filter cities by selected state if needed
  if (filters.value.estado) {
    // For now return all cities, could be enhanced to filter by state
    return props.cities
  }
  return props.cities
})
const generos = ['Masculino', 'Feminino', 'Unissex']
const faixasPreco = ['Até R$ 50', 'R$ 50 - R$ 100', 'R$ 100 - R$ 200', 'Acima de R$ 200']
const normalizeStoreType = value =>
  String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim()
    .toLowerCase()

// Multi-select functions
function toggleCategoria(categoria) {
  const index = filters.value.categorias.indexOf(categoria)
  if (index > -1) {
    filters.value.categorias.splice(index, 1)
  } else {
    filters.value.categorias.push(categoria)
  }
}

function toggleSubcategoria(subcategoria) {
  const index = filters.value.subcategorias.indexOf(subcategoria)
  if (index > -1) {
    filters.value.subcategorias.splice(index, 1)
  } else {
    filters.value.subcategorias.push(subcategoria)
  }
}

const selectedCategoriasText = computed(() => {
  if (filters.value.categorias.length === 0) return 'Categoria'
  if (filters.value.categorias.length === 1) return filters.value.categorias[0]
  return `${filters.value.categorias.length} selecionadas`
})

const selectedSubcategoriasText = computed(() => {
  if (filters.value.subcategorias.length === 0) return 'Subcategoria'
  if (filters.value.subcategorias.length === 1) return filters.value.subcategorias[0]
  return `${filters.value.subcategorias.length} selecionadas`
})

const activeFilter = ref(null)

function isFilterOpen(filter) {
  return activeFilter.value === filter
}

function handleFilterOpenChange(filter, nextOpen) {
  activeFilter.value = nextOpen ? filter : activeFilter.value === filter ? null : activeFilter.value
}

function closeFilters() {
  activeFilter.value = null
}

const selectedEstadoText = computed(() => filters.value.estado || 'Estado')
const selectedCidadeText = computed(() => filters.value.cidade || 'Cidade')
const selectedTipoLojaText = computed(() => {
  if (filters.value.tipoLoja === 'virtual') return 'Loja Virtual'
  if (filters.value.tipoLoja === 'ambos') return 'Virtual / Física'
  return 'Tipo Loja'
})
const selectedGeneroText = computed(() => filters.value.genero || 'Gênero')

function setSingleFilterValue(key, value) {
  filters.value[key] = value
  closeFilters()
}

function handleTipoLojaSelect(value) {
  filters.value.tipoLoja = value
  if (value === 'virtual') {
    filters.value.estado = ''
    filters.value.cidade = ''
  }
  closeFilters()
}

// Client-side filtering
const alternatedListings = computed(() => {
  let filtered = props.stores

  // Filter by search term (name)
  if (filters.value.busca) {
    const searchTerm = filters.value.busca.toLowerCase()
    filtered = filtered.filter(store =>
      store.name.toLowerCase().includes(searchTerm) ||
      store.description.toLowerCase().includes(searchTerm)
    )
  }

  // Filter by categories (multiple)
  if (filters.value.categorias.length > 0) {
    filtered = filtered.filter(store =>
      filters.value.categorias.includes(store.category)
    )
  }

  // Filter by subcategories (multiple)
  if (filters.value.subcategorias.length > 0) {
    filtered = filtered.filter(store => {
      if (!store.subcategory) return false

      // Se subcategory for array, verifica se tem interseção com o filtro
      if (Array.isArray(store.subcategory)) {
        return filters.value.subcategorias.some(filterSub =>
          store.subcategory.some(storeSub =>
            storeSub.toLowerCase().includes(filterSub.toLowerCase())
          )
        )
      }

      // Se for string (retrocompatibilidade), verifica se contém alguma subcategoria filtrada
      return filters.value.subcategorias.some(sub =>
        store.subcategory.toLowerCase().includes(sub.toLowerCase())
      )
    })
  }

  // Filter by store type
  if (filters.value.tipoLoja) {
    const selectedType = normalizeStoreType(filters.value.tipoLoja)
    filtered = filtered.filter((store) => {
      const storeType = normalizeStoreType(store.storeType)
      if (selectedType === 'virtual') {
        return storeType === 'virtual' || storeType === 'online'
      }
      if (selectedType === 'ambos' || selectedType === 'fisica') {
        return storeType === 'ambos' || storeType === 'fisica'
      }
      return true
    })
  }

  // Filter by state
  if (filters.value.estado) {
    filtered = filtered.filter(store => {
      const storeType = normalizeStoreType(store.storeType)
      return storeType === 'ambos' || storeType === 'fisica'
    })
    filtered = filtered.filter(store => {
      const storeState = store.location?.split(' - ')[1]
      return storeState === filters.value.estado
    })
  }

  // Filter by city
  if (filters.value.cidade) {
    filtered = filtered.filter(store => {
      const storeCity = store.location?.split(' - ')[0]
      return storeCity === filters.value.cidade
    })
  }

  // Filter by gender
  if (filters.value.genero) {
    filtered = filtered.filter(store => {
      // transformStore doesn't return gender, need to add it
      // For now, skip this filter
      return true
    })
  }

  return filtered
})

// Stores for map - only those with show_on_map = true
const storesForMap = computed(() => {
  return alternatedListings.value.filter((store) => {
    const storeType = normalizeStoreType(store.storeType)
    const hasPhysicalPresence = storeType === 'ambos' || storeType === 'fisica'
    return store.show_on_map === true && hasPhysicalPresence
  })
})

function handleSearch() {
  // Filters are reactive, no need to do anything
}

function clearFilters() {
  filters.value = {
    categorias: [],
    subcategorias: [],
    tipoLoja: '',
    estado: '',
    cidade: '',
    genero: '',
    faixaPreco: '',
    busca: ''
  }
  closeFilters()
}

// Store hover state for map interaction
const hoveredStoreId = ref(null)

// Handle store card hover
function handleStoreHover(storeId) {
  hoveredStoreId.value = storeId
}

// Handle store card leave
function handleStoreLeave() {
  hoveredStoreId.value = null
}

// Handle marker click - scroll to store card
function handleMarkerClick(store) {
  const element = document.getElementById(`store-${store.id}`)
  if (element) {
    element.scrollIntoView({ behavior: 'smooth', block: 'center' })
    // Highlight the card briefly
    element.classList.add('ring-2', 'ring-primary', 'transition-all')
    setTimeout(() => {
      element.classList.remove('ring-2', 'ring-primary')
    }, 2000)
  }
}

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
    <!-- Sticky Filter Section -->
    <section class="sticky top-16 z-30 bg-background border-b border-border shadow-md">
      <div class="container mx-auto px-4 py-4">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary">
              <Icon icon="lucide:store" class="size-5 text-primary-foreground" />
            </div>
            <div>
              <h1 class="text-xl font-bold">Varejo</h1>
              <p class="text-sm text-muted-foreground">
                {{ alternatedListings.length }} lojas encontradas
              </p>
            </div>
          </div>
          <Button variant="outline" size="sm" @click="clearFilters">
            <Icon icon="lucide:x" class="size-4 mr-2" />
            Limpar Filtros
          </Button>
        </div>

        <!-- Filters -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-8 gap-3">
          <!-- Busca -->
          <div class="col-span-2">
            <Input
              v-model="filters.busca"
              placeholder="Buscar por nome..."
              class="w-full"
            >
              <template #prefix>
                <Icon icon="lucide:search" class="size-4" />
              </template>
            </Input>
          </div>

          <!-- Categoria -->
          <Popover :open="isFilterOpen('categoria')" @update:open="(open) => handleFilterOpenChange('categoria', open)">
            <PopoverTrigger as-child>
              <Button
                variant="outline"
                role="combobox"
                class="w-full justify-between font-normal"
              >
                {{ selectedCategoriasText }}
                <Icon icon="lucide:chevron-down" class="ml-2 h-4 w-4 shrink-0 opacity-50" />
              </Button>
            </PopoverTrigger>
            <PopoverContent class="w-[200px] p-0" align="start">
              <div class="max-h-64 overflow-y-auto p-4 space-y-2">
                <button
                  v-for="cat in categorias"
                  :key="cat.name"
                  type="button"
                  class="flex w-full items-center space-x-2 rounded p-2 text-left hover:bg-muted"
                  @click="toggleCategoria(cat.name)"
                >
                  <Checkbox
                    :id="undefined"
                    :checked="filters.categorias.includes(cat.name)"
                  />
                  <span class="text-sm font-medium leading-none flex-1">
                    {{ cat.name }} ({{ cat.count }})
                  </span>
                </button>
              </div>
            </PopoverContent>
          </Popover>

          <!-- Subcategoria -->
          <Popover :open="isFilterOpen('subcategoria')" @update:open="(open) => handleFilterOpenChange('subcategoria', open)">
            <PopoverTrigger as-child>
              <Button
                variant="outline"
                role="combobox"
                class="w-full justify-between font-normal"
              >
                {{ selectedSubcategoriasText }}
                <Icon icon="lucide:chevron-down" class="ml-2 h-4 w-4 shrink-0 opacity-50" />
              </Button>
            </PopoverTrigger>
            <PopoverContent class="w-[200px] p-0" align="start">
              <div class="max-h-64 overflow-y-auto p-4 space-y-2">
                <button
                  v-for="sub in subcategorias"
                  :key="sub.name"
                  type="button"
                  class="flex w-full items-center space-x-2 rounded p-2 text-left hover:bg-muted"
                  @click="toggleSubcategoria(sub.name)"
                >
                  <Checkbox
                    :id="undefined"
                    :checked="filters.subcategorias.includes(sub.name)"
                  />
                  <span class="text-sm font-medium leading-none flex-1">
                    {{ sub.name }} ({{ sub.count }})
                  </span>
                </button>
              </div>
            </PopoverContent>
          </Popover>

          <!-- Estado -->
          <Popover :open="isFilterOpen('estado')" @update:open="(open) => handleFilterOpenChange('estado', open)">
            <PopoverTrigger as-child>
              <Button
                variant="outline"
                role="combobox"
                class="w-full justify-between font-normal"
                :disabled="filters.tipoLoja === 'virtual'"
              >
                {{ selectedEstadoText }}
                <Icon icon="lucide:chevron-down" class="ml-2 h-4 w-4 shrink-0 opacity-50" />
              </Button>
            </PopoverTrigger>
            <PopoverContent class="w-[200px] p-0" align="start">
              <div class="max-h-64 overflow-y-auto p-2">
                <button
                  v-for="(estado, index) in estados"
                  :key="estado"
                  type="button"
                  :class="[
                    'flex w-full items-center rounded px-3 py-2 text-left text-sm hover:bg-muted',
                    index === 0 ? 'bg-muted' : '',
                  ]"
                  @click="setSingleFilterValue('estado', estado)"
                >
                  {{ estado }}
                </button>
              </div>
            </PopoverContent>
          </Popover>

          <!-- Cidade -->
          <Popover :open="isFilterOpen('cidade')" @update:open="(open) => handleFilterOpenChange('cidade', open)">
            <PopoverTrigger as-child>
              <Button
                variant="outline"
                role="combobox"
                class="w-full justify-between font-normal"
                :disabled="filters.tipoLoja === 'virtual'"
              >
                {{ selectedCidadeText }}
                <Icon icon="lucide:chevron-down" class="ml-2 h-4 w-4 shrink-0 opacity-50" />
              </Button>
            </PopoverTrigger>
            <PopoverContent class="w-[200px] p-0" align="start">
              <div class="max-h-64 overflow-y-auto p-2">
                <button
                  v-for="(cidade, index) in cidades"
                  :key="cidade"
                  type="button"
                  :class="[
                    'flex w-full items-center rounded px-3 py-2 text-left text-sm hover:bg-muted',
                    index === 0 ? 'bg-muted' : '',
                  ]"
                  @click="setSingleFilterValue('cidade', cidade)"
                >
                  {{ cidade }}
                </button>
              </div>
            </PopoverContent>
          </Popover>

          <!-- Tipo de Loja -->
          <Popover :open="isFilterOpen('tipoLoja')" @update:open="(open) => handleFilterOpenChange('tipoLoja', open)">
            <PopoverTrigger as-child>
              <Button
                variant="outline"
                role="combobox"
                class="w-full justify-between font-normal"
              >
                {{ selectedTipoLojaText }}
                <Icon icon="lucide:chevron-down" class="ml-2 h-4 w-4 shrink-0 opacity-50" />
              </Button>
            </PopoverTrigger>
            <PopoverContent class="w-[200px] p-0" align="start">
              <div class="p-2">
                <button
                  type="button"
                  class="flex w-full items-center rounded bg-muted px-3 py-2 text-left text-sm hover:bg-muted"
                  @click="handleTipoLojaSelect('virtual')"
                >
                  Loja Virtual
                </button>
                <button
                  type="button"
                  class="flex w-full items-center rounded px-3 py-2 text-left text-sm hover:bg-muted"
                  @click="handleTipoLojaSelect('ambos')"
                >
                  Virtual / Física
                </button>
              </div>
            </PopoverContent>
          </Popover>

          <!-- Gênero -->
          <Popover :open="isFilterOpen('genero')" @update:open="(open) => handleFilterOpenChange('genero', open)">
            <PopoverTrigger as-child>
              <Button
                variant="outline"
                role="combobox"
                class="w-full justify-between font-normal"
              >
                {{ selectedGeneroText }}
                <Icon icon="lucide:chevron-down" class="ml-2 h-4 w-4 shrink-0 opacity-50" />
              </Button>
            </PopoverTrigger>
            <PopoverContent class="w-[200px] p-0" align="start">
              <div class="p-2">
                <button
                  v-for="(gen, index) in generos"
                  :key="gen"
                  type="button"
                  :class="[
                    'flex w-full items-center rounded px-3 py-2 text-left text-sm hover:bg-muted',
                    index === 0 ? 'bg-muted' : '',
                  ]"
                  @click="setSingleFilterValue('genero', gen)"
                >
                  {{ gen }}
                </button>
              </div>
            </PopoverContent>
          </Popover>
        </div>
      </div>
    </section>

    <!-- Store Listings Section -->
    <section class="py-8 bg-muted/30">
      <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-7xl mx-auto">
          <div
            v-for="store in alternatedListings"
            :key="store.id"
            :id="`store-${store.id}`"
            @mouseenter="handleStoreHover(store.id)"
            @mouseleave="handleStoreLeave"
          >
            <StoreCard :store="store" />
          </div>
        </div>
      </div>
    </section>

    <!-- Floating Map Widget -->
    <FloatingMap
      :stores="storesForMap"
      :hovered-store-id="hoveredStoreId"
      title="Mapa de Lojas Físicas"
      @marker-click="handleMarkerClick"
      @marker-hover="handleStoreHover"
    />
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
