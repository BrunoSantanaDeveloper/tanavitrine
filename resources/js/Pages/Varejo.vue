<script setup>
import Button from '@/Components/shadcn/ui/button/Button.vue'
import Select from '@/Components/shadcn/ui/select/Select.vue'
import SelectContent from '@/Components/shadcn/ui/select/SelectContent.vue'
import SelectItem from '@/Components/shadcn/ui/select/SelectItem.vue'
import SelectTrigger from '@/Components/shadcn/ui/select/SelectTrigger.vue'
import SelectValue from '@/Components/shadcn/ui/select/SelectValue.vue'
import Input from '@/Components/shadcn/ui/input/Input.vue'
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/shadcn/ui/popover'
import { Checkbox } from '@/Components/shadcn/ui/checkbox'
import StoreCard from '@/Components/StoreCard.vue'
import FloatingMap from '@/Components/FloatingMap.vue'
import { useSeoMetaTags } from '@/Composables/useSeoMetaTags.js'
import WebLayout from '@/Layouts/WebLayout.vue'
import { Icon } from '@iconify/vue'
import { ref, computed, onMounted } from 'vue'

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

const categorias = computed(() => props.categories.map(c => c.name))
const subcategorias = ['Feminino', 'Masculino', 'Infantil', 'Plus Size', 'Moda Praia', 'Lingerie']
const tiposLoja = ['fisica', 'virtual', 'ambos']
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
    filtered = filtered.filter(store =>
      store.subcategory && filters.value.subcategorias.some(sub =>
        store.subcategory.toLowerCase().includes(sub.toLowerCase())
      )
    )
  }

  // Filter by store type
  if (filters.value.tipoLoja) {
    filtered = filtered.filter(store => {
      // transformStore doesn't return store_type, need to add it
      // For now, skip this filter
      return true
    })
  }

  // Filter by state
  if (filters.value.estado) {
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
          <Popover>
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
                <div
                  v-for="cat in categorias"
                  :key="cat"
                  class="flex items-center space-x-2 cursor-pointer hover:bg-muted p-2 rounded"
                  @click="toggleCategoria(cat)"
                >
                  <Checkbox
                    :id="`cat-${cat}`"
                    :checked="filters.categorias.includes(cat)"
                  />
                  <label
                    :for="`cat-${cat}`"
                    class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 cursor-pointer flex-1"
                  >
                    {{ cat }}
                  </label>
                </div>
              </div>
            </PopoverContent>
          </Popover>

          <!-- Subcategoria -->
          <Popover>
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
                <div
                  v-for="sub in subcategorias"
                  :key="sub"
                  class="flex items-center space-x-2 cursor-pointer hover:bg-muted p-2 rounded"
                  @click="toggleSubcategoria(sub)"
                >
                  <Checkbox
                    :id="`sub-${sub}`"
                    :checked="filters.subcategorias.includes(sub)"
                  />
                  <label
                    :for="`sub-${sub}`"
                    class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 cursor-pointer flex-1"
                  >
                    {{ sub }}
                  </label>
                </div>
              </div>
            </PopoverContent>
          </Popover>

          <!-- Estado -->
          <Select v-model="filters.estado">
            <SelectTrigger class="w-full">
              <SelectValue placeholder="Estado" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="estado in estados" :key="estado" :value="estado">
                {{ estado }}
              </SelectItem>
            </SelectContent>
          </Select>

          <!-- Cidade -->
          <Select v-model="filters.cidade">
            <SelectTrigger class="w-full">
              <SelectValue placeholder="Cidade" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="cidade in cidades" :key="cidade" :value="cidade">
                {{ cidade }}
              </SelectItem>
            </SelectContent>
          </Select>

          <!-- Tipo de Loja -->
          <Select v-model="filters.tipoLoja">
            <SelectTrigger class="w-full">
              <SelectValue placeholder="Tipo Loja" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="fisica">Física</SelectItem>
              <SelectItem value="virtual">Virtual</SelectItem>
              <SelectItem value="ambos">Ambos</SelectItem>
            </SelectContent>
          </Select>

          <!-- Gênero -->
          <Select v-model="filters.genero">
            <SelectTrigger class="w-full">
              <SelectValue placeholder="Gênero" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="gen in generos" :key="gen" :value="gen">
                {{ gen }}
              </SelectItem>
            </SelectContent>
          </Select>
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
      :stores="alternatedListings"
      :hovered-store-id="hoveredStoreId"
      title="Mapa de Lojas"
      @marker-click="handleMarkerClick"
      @marker-hover="handleStoreHover"
    />
  </WebLayout>
</template>
