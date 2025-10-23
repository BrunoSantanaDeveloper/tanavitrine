<script setup>
import Button from '@/Components/shadcn/ui/button/Button.vue'
import Select from '@/Components/shadcn/ui/select/Select.vue'
import SelectContent from '@/Components/shadcn/ui/select/SelectContent.vue'
import SelectItem from '@/Components/shadcn/ui/select/SelectItem.vue'
import SelectTrigger from '@/Components/shadcn/ui/select/SelectTrigger.vue'
import SelectValue from '@/Components/shadcn/ui/select/SelectValue.vue'
import Input from '@/Components/shadcn/ui/input/Input.vue'
import StoreCard from '@/Components/StoreCard.vue'
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
  categoria: '',
  subcategoria: '',
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
  if (urlParams.has('categoria')) filters.value.categoria = urlParams.get('categoria')
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

  // Filter by category
  if (filters.value.categoria) {
    filtered = filtered.filter(store =>
      store.category === filters.value.categoria
    )
  }

  // Filter by subcategory
  if (filters.value.subcategoria) {
    filtered = filtered.filter(store =>
      store.subcategory && store.subcategory.toLowerCase().includes(filters.value.subcategoria.toLowerCase())
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
    categoria: '',
    subcategoria: '',
    tipoLoja: '',
    estado: '',
    cidade: '',
    genero: '',
    faixaPreco: '',
    busca: ''
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
          <Select v-model="filters.categoria">
            <SelectTrigger class="w-full">
              <SelectValue placeholder="Categoria" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="cat in categorias" :key="cat" :value="cat">
                {{ cat }}
              </SelectItem>
            </SelectContent>
          </Select>

          <!-- Subcategoria -->
          <Select v-model="filters.subcategoria">
            <SelectTrigger class="w-full">
              <SelectValue placeholder="Subcategoria" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="sub in subcategorias" :key="sub" :value="sub">
                {{ sub }}
              </SelectItem>
            </SelectContent>
          </Select>

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
          <StoreCard
            v-for="store in alternatedListings"
            :key="store.id"
            :store="store"
          />
        </div>
      </div>
    </section>
  </WebLayout>
</template>
