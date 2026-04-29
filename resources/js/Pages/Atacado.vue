<script setup>
import Button from '@/Components/shadcn/ui/button/Button.vue'
import Input from '@/Components/shadcn/ui/input/Input.vue'
import FilterDropdown from '@/Components/FilterDropdown.vue'
import { Checkbox } from '@/Components/shadcn/ui/checkbox'
import StoreCard from '@/Components/StoreCard.vue'
import { useExclusivePopoverGroup } from '@/Composables/useExclusivePopoverGroup.js'
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
  fabricante: '',
  estado: '',
  cidade: '',
  genero: '',
  pedidoMinimo: '',
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
  const fabricanteParam = urlParams.get('fabricante') ?? urlParams.get('manufacturer')
  if (fabricanteParam) filters.value.fabricante = normalizeManufacturerFilter(fabricanteParam)
  if (urlParams.has('estado')) filters.value.estado = urlParams.get('estado')
  if (urlParams.has('cidade')) filters.value.cidade = urlParams.get('cidade')
  if (urlParams.has('genero')) filters.value.genero = urlParams.get('genero')
  if (urlParams.has('busca')) filters.value.busca = urlParams.get('busca') || ''
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
const pedidosMinimos = ['Até 20 peças', '20-50 peças', '50-100 peças', 'Acima de 100 peças']
const normalizeStoreType = value =>
  String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim()
    .toLowerCase()
const normalizeGender = value =>
  String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim()
    .toLowerCase()
const normalizeText = value =>
  String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim()
    .toLowerCase()

function getSearchTokens(value) {
  return normalizeText(value)
    .split(/\s+/)
    .filter(Boolean)
}

function expandTokenVariants(token) {
  const variants = new Set([token])

  if (token.endsWith('s') && token.length > 3) {
    variants.add(token.slice(0, -1))
  } else if (token.length > 3) {
    variants.add(`${token}s`)
  }

  return [...variants]
}
function normalizeManufacturerFilter(value) {
  const normalized = String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim()
    .toLowerCase()

  if (['fabricante', 'manufacturer', 'sim', 'yes', '1', 'true'].includes(normalized)) {
    return 'manufacturer'
  }

  if (['nao-fabricante', 'nao_fabricante', 'nao fabricante', 'non-manufacturer', 'non_manufacturer', 'false', '0', 'nao', 'not'].includes(normalized)) {
    return 'non_manufacturer'
  }

  return ''
}
function isStoreManufacturer(store) {
  const value = store?.is_manufacturer

  if (typeof value === 'boolean') return value
  if (value === 1 || value === '1') return true
  if (typeof value === 'string') return value.toLowerCase() === 'true'

  return false
}

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

const {
  isOpen: isFilterOpen,
  setOpen: setFilterOpen,
  closeAll: closeFilters,
} = useExclusivePopoverGroup()

function handleFilterOpenChange(filter, nextOpen) {
  setFilterOpen(filter, nextOpen)
}

const selectedEstadoText = computed(() => filters.value.estado || 'Estado')
const selectedCidadeText = computed(() => filters.value.cidade || 'Cidade')
const selectedTipoLojaText = computed(() => {
  if (filters.value.tipoLoja === 'virtual') return 'Loja Virtual'
  if (filters.value.tipoLoja === 'ambos') return 'Virtual / Física'
  return 'Tipo Loja'
})
const selectedFabricanteText = computed(() => {
  if (filters.value.fabricante === 'manufacturer') return 'Fabricante'
  if (filters.value.fabricante === 'non_manufacturer') return 'Não fabricante'
  return 'Fabricante'
})
const selectedGeneroText = computed(() => filters.value.genero || 'Gênero')
const filterTriggerClass =
  'flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm font-normal ring-offset-background outline-none focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0 hover:bg-accent hover:text-accent-foreground disabled:cursor-not-allowed disabled:opacity-50'
const filterContentClass = 'w-[200px] p-0'

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

  // Filter by search term across multiple fields.
  if (filters.value.busca) {
    const searchTokens = getSearchTokens(filters.value.busca)

    filtered = filtered.filter(store => {
      const searchableText = normalizeText([
        store.name,
        store.description,
        store.category,
        store.gender,
        store.saleType,
        ...(Array.isArray(store.subcategory) ? store.subcategory : [store.subcategory]),
      ].filter(Boolean).join(' '))

      return searchTokens.every(token => {
        const variants = expandTokenVariants(token)
        return variants.some(variant => searchableText.includes(variant))
      })
    })
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
      const storeType = normalizeStoreType(store.storeType)
      return storeType === 'ambos' || storeType === 'fisica'
    })
    filtered = filtered.filter(store => {
      const storeCity = store.location?.split(' - ')[0]
      return storeCity === filters.value.cidade
    })
  }

  // Filter by gender
  if (filters.value.genero) {
    const selectedGender = normalizeGender(filters.value.genero)
    filtered = filtered.filter(store => normalizeGender(store.gender) === selectedGender)
  }

  // Filter by manufacturer flag
  if (filters.value.fabricante) {
    const targetIsManufacturer = filters.value.fabricante === 'manufacturer'
    filtered = filtered.filter(store => isStoreManufacturer(store) === targetIsManufacturer)
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
    fabricante: '',
    estado: '',
    cidade: '',
    genero: '',
    pedidoMinimo: '',
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
  <WebLayout :can-login="canLogin" :can-register="canRegister" :show-floating-whats-app="false">
    <!-- Sticky Filter Section -->
    <section class="sticky top-16 z-30 bg-background border-b border-border shadow-md">
      <div class="container mx-auto px-4 py-4">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary">
              <Icon icon="lucide:shopping-cart" class="size-5 text-primary-foreground" />
            </div>
            <div>
              <h1 class="text-xl font-bold">Atacado</h1>
              <p class="text-sm text-muted-foreground">
                {{ alternatedListings.length }} fornecedores encontrados
              </p>
            </div>
          </div>
          <Button variant="outline" size="sm" @click="clearFilters">
            <Icon icon="lucide:x" class="size-4 mr-2" />
            Limpar Filtros
          </Button>
        </div>

        <!-- Filters -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-10 gap-3">
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
          <FilterDropdown
            :open="isFilterOpen('categoria')"
            :trigger-class="filterTriggerClass"
            :content-class="filterContentClass"
            @update:open="(open) => handleFilterOpenChange('categoria', open)"
          >
            <template #trigger>
                {{ selectedCategoriasText }}
                <Icon icon="lucide:chevron-down" class="ml-2 h-4 w-4 shrink-0 opacity-50" />
            </template>

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
          </FilterDropdown>

          <!-- Subcategoria -->
          <FilterDropdown
            :open="isFilterOpen('subcategoria')"
            :trigger-class="filterTriggerClass"
            :content-class="filterContentClass"
            @update:open="(open) => handleFilterOpenChange('subcategoria', open)"
          >
            <template #trigger>
                {{ selectedSubcategoriasText }}
                <Icon icon="lucide:chevron-down" class="ml-2 h-4 w-4 shrink-0 opacity-50" />
            </template>

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
          </FilterDropdown>

          <!-- Estado -->
          <FilterDropdown
            :open="isFilterOpen('estado')"
            :disabled="filters.tipoLoja === 'virtual'"
            :trigger-class="filterTriggerClass"
            :content-class="filterContentClass"
            @update:open="(open) => handleFilterOpenChange('estado', open)"
          >
            <template #trigger>
                {{ selectedEstadoText }}
                <Icon icon="lucide:chevron-down" class="ml-2 h-4 w-4 shrink-0 opacity-50" />
            </template>

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
          </FilterDropdown>

          <!-- Cidade -->
          <FilterDropdown
            :open="isFilterOpen('cidade')"
            :disabled="filters.tipoLoja === 'virtual'"
            :trigger-class="filterTriggerClass"
            :content-class="filterContentClass"
            @update:open="(open) => handleFilterOpenChange('cidade', open)"
          >
            <template #trigger>
                {{ selectedCidadeText }}
                <Icon icon="lucide:chevron-down" class="ml-2 h-4 w-4 shrink-0 opacity-50" />
            </template>

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
          </FilterDropdown>

          <!-- Tipo de Loja -->
          <FilterDropdown
            :open="isFilterOpen('tipoLoja')"
            :trigger-class="filterTriggerClass"
            :content-class="filterContentClass"
            @update:open="(open) => handleFilterOpenChange('tipoLoja', open)"
          >
            <template #trigger>
                {{ selectedTipoLojaText }}
                <Icon icon="lucide:chevron-down" class="ml-2 h-4 w-4 shrink-0 opacity-50" />
            </template>

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
          </FilterDropdown>

          <!-- Gênero -->
          <FilterDropdown
            :open="isFilterOpen('genero')"
            :trigger-class="filterTriggerClass"
            :content-class="filterContentClass"
            @update:open="(open) => handleFilterOpenChange('genero', open)"
          >
            <template #trigger>
                {{ selectedGeneroText }}
                <Icon icon="lucide:chevron-down" class="ml-2 h-4 w-4 shrink-0 opacity-50" />
            </template>

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
          </FilterDropdown>

          <!-- Fabricante -->
          <FilterDropdown
            :open="isFilterOpen('fabricante')"
            :trigger-class="filterTriggerClass"
            :content-class="filterContentClass"
            @update:open="(open) => handleFilterOpenChange('fabricante', open)"
          >
            <template #trigger>
                {{ selectedFabricanteText }}
                <Icon icon="lucide:chevron-down" class="ml-2 h-4 w-4 shrink-0 opacity-50" />
            </template>

            <div class="p-2 space-y-1">
              <button
                type="button"
                class="flex w-full items-center rounded px-3 py-2 text-left text-sm hover:bg-muted"
                :class="filters.fabricante === 'manufacturer' ? 'bg-muted' : ''"
                @click="setSingleFilterValue('fabricante', 'manufacturer')"
              >
                Fabricante
              </button>
              <button
                type="button"
                class="flex w-full items-center rounded px-3 py-2 text-left text-sm hover:bg-muted"
                :class="filters.fabricante === 'non_manufacturer' ? 'bg-muted' : ''"
                @click="setSingleFilterValue('fabricante', 'non_manufacturer')"
              >
                Não fabricante
              </button>
              <button
                type="button"
                class="flex w-full items-center rounded px-3 py-2 text-left text-sm hover:bg-muted"
                :class="filters.fabricante === '' ? 'bg-muted' : ''"
                @click="setSingleFilterValue('fabricante', '')"
              >
                Todos
              </button>
            </div>
          </FilterDropdown>
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
            class="h-full"
            @mouseenter="handleStoreHover(store.id)"
            @mouseleave="handleStoreLeave"
          >
            <StoreCard :store="store" />
          </div>
        </div>
      </div>
    </section>

    <button
      v-show="showBackToTop"
      type="button"
      class="fixed bottom-[calc(6rem+env(safe-area-inset-bottom))] left-1/2 z-[60] -translate-x-1/2 inline-flex items-center gap-2 rounded-full border border-green-700 bg-green-600 px-4 py-2.5 shadow-lg transition hover:bg-green-700 hover:shadow-xl md:bottom-6"
      aria-label="Voltar ao topo"
      title="Voltar ao topo"
      @click="scrollToTop"
    >
      <Icon icon="lucide:arrow-up" class="size-4 text-white" />
      <span class="text-sm font-medium text-white">Voltar ao topo</span>
    </button>
  </WebLayout>
</template>
