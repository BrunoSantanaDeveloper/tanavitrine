<script setup>
import { computed, ref, watch } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Button } from '@/Components/shadcn/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import { Input } from '@/Components/shadcn/ui/input'
import { Label } from '@/Components/shadcn/ui/label'
import { MultiSelect } from '@/Components/shadcn/ui/multi-select'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/shadcn/ui/select'
import { Textarea } from '@/Components/shadcn/ui/textarea'
import AppLayout from '@/Layouts/AppLayout.vue'
import { formatCEP, formatPhone } from '@/utils/formatters'
import { Icon } from '@iconify/vue'
import { toast } from 'vue-sonner'
import axios from 'axios'

const props = defineProps({
  store: {
    type: Object,
    required: true,
  },
  categories: {
    type: Array,
    default: () => [],
  },
})

const logoPreview = ref(props.store.logo_url || null)
const logoInput = ref(null)

// CEP lookup state
const isCepLoading = ref(false)
const cepError = ref(null)
const cepSuccess = ref(false)

// Geocoding state
const isGeocodingLoading = ref(false)
const geocodingError = ref(null)
const geocodingSuccess = ref(false)

// Ensure subcategory is always an array
const initialSubcategory = Array.isArray(props.store.subcategory)
  ? props.store.subcategory
  : (props.store.subcategory ? [props.store.subcategory] : [])

const form = useForm({
  name: props.store.name,
  description: props.store.description,
  sale_type: props.store.sale_type,
  // Keep compatibility with legacy "fisica" records by mapping them to "ambos" in the edit UI.
  store_type: props.store.store_type === 'fisica' ? 'ambos' : props.store.store_type,
  category_id: props.store.category_id ? String(props.store.category_id) : null,
  subcategory: initialSubcategory,
  gender: props.store.gender || null,
  min_order: props.store.min_order || '',
  whatsapp: props.store.whatsapp || '',
  website: props.store.website || '',
  instagram: props.store.instagram || '',
  facebook: props.store.facebook || '',
  tiktok: props.store.tiktok || '',
  address: props.store.address || '',
  address_number: props.store.address_number || '',
  address_complement: props.store.address_complement || '',
  google_maps_url: props.store.google_maps_url || '',
  city: props.store.city || '',
  state: props.store.state || '',
  zip_code: props.store.zip_code || '',
  logo: null,
})

const subcategories = computed(() => {
  const category = props.categories.find(c => c.id === Number.parseInt(form.category_id))
  return category?.children || []
})
const showLocationSection = computed(() => form.store_type === 'ambos')

// Converte subcategories para o formato do MultiSelect
const subcategoryOptions = computed(() => {
  return subcategories.value.map(sub => ({
    value: sub.name,
    label: sub.name,
  }))
})

// Limpa subcategorias quando a categoria principal mudar
watch(() => form.category_id, (newCategoryId, oldCategoryId) => {
  if (oldCategoryId !== undefined && newCategoryId !== oldCategoryId) {
    form.subcategory = []
  }
})

function handleLogoUpload(e) {
  const file = e.target.files[0]
  if (!file) return

  if (!file.type.startsWith('image/')) {
    alert('Apenas imagens são permitidas')
    return
  }
  if (file.size > 2 * 1024 * 1024) {
    alert('Logo muito grande. Máximo 2MB.')
    return
  }

  form.logo = file

  const reader = new FileReader()
  reader.onload = (e) => {
    logoPreview.value = e.target.result
  }
  reader.readAsDataURL(file)
}

function removeLogo() {
  form.logo = null
  logoPreview.value = null
  if (logoInput.value) {
    logoInput.value.value = ''
  }
}

function handleWhatsAppInput(e) {
  form.whatsapp = formatPhone(e.target.value)
}

function handleCEPInput(e) {
  form.zip_code = formatCEP(e.target.value)

  // Clear previous states when CEP is being edited
  cepError.value = null
  cepSuccess.value = false
  geocodingError.value = null
  geocodingSuccess.value = false

  // Trigger CEP lookup only when 8 digits are complete
  const cepNumbers = e.target.value.replace(/\D/g, '')
  if (cepNumbers.length === 8) {
    lookupCep(cepNumbers)
  }
}

// CEP lookup function using ViaCEP API
async function lookupCep(cep) {
  isCepLoading.value = true
  cepError.value = null
  cepSuccess.value = false

  try {
    const response = await axios.get(`https://viacep.com.br/ws/${cep}/json/`)

    if (response.data.erro) {
      cepError.value = 'CEP não encontrado. Verifique o número digitado.'
      return
    }

    // Fill address fields automatically
    form.address = response.data.logradouro || ''
    form.city = response.data.localidade || ''
    form.state = response.data.uf || ''

    cepSuccess.value = true

    console.log('CEP lookup success:', response.data)

    // Trigger geocoding after CEP lookup
    geocodeAddress()
  }
  catch (error) {
    console.error('CEP lookup error:', error)
    cepError.value = 'Erro ao buscar CEP. Tente novamente.'
  }
  finally {
    isCepLoading.value = false
  }
}

// Geocoding function using Nominatim (OpenStreetMap)
async function geocodeAddress() {
  const { zip_code, address, address_number, city, state } = form

  // Check if at least city and state are filled
  if (!city || !state) {
    return
  }

  isGeocodingLoading.value = true
  geocodingError.value = null
  geocodingSuccess.value = false

  try {
    // Build address query with maximum detail available
    let query = ''

    const addressWithNumber = [address, address_number].filter(Boolean).join(', ')

    if (zip_code && addressWithNumber) {
      // Most precise: use CEP and address
      query = `${addressWithNumber}, ${zip_code}, ${city}, ${state}, Brazil`
    }
    else if (addressWithNumber) {
      // Use address with city and state
      query = `${addressWithNumber}, ${city}, ${state}, Brazil`
    }
    else {
      // Fallback: city and state only (less precise)
      query = `${city}, ${state}, Brazil`
    }

    // Call Nominatim API
    const response = await axios.get('https://nominatim.openstreetmap.org/search', {
      params: {
        q: query,
        format: 'json',
        limit: 1,
        countrycodes: 'br',
        addressdetails: 1,
      },
      headers: {
        'User-Agent': 'TanaVitrine/1.0',
      },
    })

    if (response.data && response.data.length > 0) {
      geocodingSuccess.value = true

      toast.success('Localização atualizada! Sua loja aparecerá no mapa.')

      console.log('Geocoding success:', {
        query,
        lat: response.data[0].lat,
        lng: response.data[0].lon,
        precision: addressWithNumber ? 'street-level' : 'city-level',
      })
    }
    else {
      geocodingError.value = 'Localização não encontrada no mapa.'
    }
  }
  catch (error) {
    console.error('Geocoding error:', error)
    geocodingError.value = 'Erro ao buscar localização no mapa.'
  }
  finally {
    isGeocodingLoading.value = false
  }
}

function submit() {
  // Inertia requires using POST with _method for file uploads
  form.transform((data) => ({
    ...data,
    // Convert empty strings and undefined to null for optional fields
    gender: data.gender || null,
    min_order: data.min_order || null,
    website: data.website || null,
    instagram: data.instagram || null,
    facebook: data.facebook || null,
    tiktok: data.tiktok || null,
    address: data.address || null,
    address_number: data.address_number || null,
    address_complement: data.address_complement || null,
    google_maps_url: data.google_maps_url || null,
    zip_code: data.zip_code || null,
    _method: 'PUT'
  })).post(route('dashboard.stores.update', props.store.slug), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Salvo com Sucesso!')
    },
    onError: () => {
        toast.error('Erro ao salvar, verifique os campos e tente novamente!')
    },
  })

}
</script>

<template>
  <Head :title="`Editar ${store.name}`" />

  <AppLayout :title="`Editar ${store.name}`">
    <div class="min-h-screen bg-gray-50 p-6">
      <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
          <div class="flex items-center gap-2 text-sm text-muted-foreground mb-2">
            <Link :href="route('dashboard')" class="hover:text-teal-600 transition-colors">
              Dashboard
            </Link>
            <Icon icon="lucide:chevron-right" class="h-4 w-4" />
            <span class="text-gray-900 font-medium">Editar Vitrine</span>
          </div>
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-2xl font-bold text-gray-900">Editar Vitrine</h1>
              <p class="text-muted-foreground mt-1">{{ store.name }}</p>
            </div>
            <Button :as="Link" :href="route('dashboard')" variant="outline">
              <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
              Voltar
            </Button>
          </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
          <!-- Informações Básicas -->
          <Card>
            <CardHeader>
              <CardTitle>Informações Básicas</CardTitle>
              <CardDescription>Informações principais da sua vitrine</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="grid gap-4">
                <!-- Logo Upload -->
                <div>
                  <Label class="text-base mb-2 block">Logo da Loja</Label>
                  <div class="flex gap-4 items-start">
                    <div class="flex-shrink-0">
                      <div v-if="logoPreview" class="relative">
                        <img :src="logoPreview" alt="Logo preview" class="w-32 h-32 object-cover rounded-lg border-2 border-gray-300" />
                        <button type="button" @click="removeLogo" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1.5 shadow-lg hover:bg-red-600 transition-colors">
                          <Icon icon="lucide:x" class="h-4 w-4" />
                        </button>
                      </div>
                      <div v-else class="w-32 h-32 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center cursor-pointer hover:border-teal-500 transition-colors">
                        <label for="logo-upload" class="cursor-pointer text-center">
                          <Icon icon="lucide:image-plus" class="h-8 w-8 text-gray-400 mx-auto mb-1" />
                          <span class="text-xs text-gray-500">Adicionar logo</span>
                        </label>
                      </div>
                      <input ref="logoInput" type="file" id="logo-upload" accept="image/*" class="hidden" @change="handleLogoUpload" />
                    </div>
                    <div class="flex-1">
                      <p class="text-sm text-muted-foreground">
                        Faça upload do logo da sua loja. Recomendamos uma imagem quadrada de pelo menos 200x200px.
                      </p>
                      <p class="text-xs text-muted-foreground mt-2">
                        Formatos aceitos: JPG, PNG, WEBP (máx. 2MB)
                      </p>
                      <div v-if="logoPreview" class="mt-3">
                        <Button type="button" variant="outline" size="sm" @click="logoInput?.click()">
                          <Icon icon="lucide:upload" class="mr-2 h-4 w-4" />
                          Trocar Logo
                        </Button>
                      </div>
                    </div>
                  </div>
                </div>

                <div>
                  <Label for="name">Nome da Loja *</Label>
                  <Input id="name" v-model="form.name" required />
                </div>

                <div>
                  <Label for="description">Descrição *</Label>
                  <Textarea id="description" v-model="form.description" rows="4" required />
                </div>

                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <Label for="sale_type">Tipo de Venda *</Label>
                    <Select v-model="form.sale_type">
                      <SelectTrigger>
                        <SelectValue placeholder="Selecione" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="atacado">Atacado</SelectItem>
                        <SelectItem value="varejo">Varejo</SelectItem>
                        <SelectItem value="ambos">Ambos</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <div>
                    <Label for="store_type">Tipo de Loja *</Label>
                    <Select v-model="form.store_type">
                      <SelectTrigger>
                        <SelectValue placeholder="Selecione" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="virtual">Loja Virtual</SelectItem>
                        <SelectItem value="ambos">Virtual / Física</SelectItem>
                      </SelectContent>
                    </Select>
                    <p class="text-xs text-muted-foreground mt-2">
                      Loja Virtual: atende por redes sociais, WhatsApp ou site. Virtual / Física: atende online e também em loja física.
                    </p>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Categoria -->
          <Card>
            <CardHeader>
              <CardTitle>Categoria</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <Label for="category_id">Categoria *</Label>
                  <Select v-model="form.category_id">
                    <SelectTrigger>
                      <SelectValue placeholder="Selecione" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="cat in categories" :key="cat.id" :value="String(cat.id)">
                        {{ cat.name }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div class="col-span-2">
                  <Label for="subcategory">Subcategorias</Label>
                  <MultiSelect
                    v-if="subcategories.length > 0"
                    id="subcategory"
                    v-model="form.subcategory"
                    :options="subcategoryOptions"
                    placeholder="Selecione uma ou mais subcategorias..."
                  />
                  <p v-else class="text-sm text-muted-foreground mt-2">
                    Selecione uma categoria para ver as subcategorias disponíveis
                  </p>
                  <p v-if="subcategories.length > 0" class="text-xs text-muted-foreground mt-2">
                    Selecione as subcategorias que representam seus produtos
                  </p>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <Label for="gender">Gênero (Opcional)</Label>
                  <Select v-model="form.gender">
                    <SelectTrigger>
                      <SelectValue placeholder="Não informado" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="masculino">Masculino</SelectItem>
                      <SelectItem value="feminino">Feminino</SelectItem>
                      <SelectItem value="unissex">Unissex</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div>
                  <Label for="min_order">Pedido Mínimo</Label>
                  <Input id="min_order" v-model="form.min_order" placeholder="Ex: 6 peças" />
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Contato -->
          <Card>
            <CardHeader>
              <CardTitle>Contato</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div>
                <Label for="whatsapp">WhatsApp *</Label>
                <Input id="whatsapp" v-model="form.whatsapp" placeholder="(62) 99999-9999" maxlength="15" required @input="handleWhatsAppInput" />
              </div>
            </CardContent>
          </Card>

          <!-- Redes Sociais -->
          <Card>
            <CardHeader>
              <CardTitle>Redes Sociais</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <Label for="website">Website</Label>
                  <Input id="website" v-model="form.website" placeholder="https://..." />
                </div>

                <div>
                  <Label for="instagram">Instagram</Label>
                  <Input id="instagram" v-model="form.instagram" placeholder="@usuario" />
                </div>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <Label for="facebook">Facebook</Label>
                  <Input id="facebook" v-model="form.facebook" placeholder="facebook.com/..." />
                </div>

                <div>
                  <Label for="tiktok">TikTok</Label>
                  <Input id="tiktok" v-model="form.tiktok" placeholder="@usuario" />
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Localização -->
          <Card v-if="showLocationSection">
            <CardHeader>
              <CardTitle>Localização</CardTitle>
              <CardDescription>Preencha o CEP para carregar automaticamente o endereço e atualizar a localização no mapa</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
              <!-- CEP Field (First) -->
              <div>
                <Label for="zip_code">
                  CEP *
                  <span class="text-xs text-teal-600 font-medium">(Digite o CEP para preenchimento automático)</span>
                </Label>
                <Input id="zip_code" v-model="form.zip_code" placeholder="00000-000" maxlength="9" required @input="handleCEPInput" />

                <!-- CEP Lookup Feedback -->
                <div class="mt-2">
                  <!-- CEP Loading -->
                  <div v-if="isCepLoading" class="flex items-center gap-2 text-xs text-blue-600">
                    <Icon icon="lucide:loader-2" class="h-3 w-3 animate-spin" />
                    <span>Buscando endereço...</span>
                  </div>

                  <!-- CEP Success -->
                  <div v-else-if="cepSuccess" class="flex items-center gap-2 text-xs text-green-600">
                    <Icon icon="lucide:check-circle" class="h-3 w-3" />
                    <span>Endereço encontrado!</span>
                  </div>

                  <!-- CEP Error -->
                  <div v-else-if="cepError" class="flex items-center gap-2 text-xs text-red-600">
                    <Icon icon="lucide:alert-circle" class="h-3 w-3" />
                    <span>{{ cepError }}</span>
                  </div>
                </div>
              </div>

              <!-- Address -->
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <Label for="address">Rua *</Label>
                  <Input id="address" v-model="form.address" placeholder="Ex: Rua Augusta" required />
                </div>
                <div>
                  <Label for="address_number">Número</Label>
                  <Input id="address_number" v-model="form.address_number" placeholder="Ex: 123" />
                </div>
              </div>

              <div>
                <Label for="address_complement">Complemento</Label>
                <Input id="address_complement" v-model="form.address_complement" placeholder="Ex: Sala 3, Bloco B" />
              </div>

              <div>
                <Label for="google_maps_url">Link do Google Maps (opcional)</Label>
                <Input
                  id="google_maps_url"
                  v-model="form.google_maps_url"
                  type="url"
                  placeholder="https://maps.app.goo.gl/... ou https://www.google.com/maps/..."
                />
              </div>

              <!-- City and State -->
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <Label for="city">Cidade *</Label>
                  <Input id="city" v-model="form.city" placeholder="Ex: São Paulo" required />
                </div>

                <div>
                  <Label for="state">Estado (UF) *</Label>
                  <Input id="state" v-model="form.state" maxlength="2" placeholder="SP" required />
                </div>
              </div>

              <!-- Geocoding Feedback -->
              <div v-if="isGeocodingLoading || geocodingSuccess || geocodingError" class="mt-2">
                <!-- Geocoding Loading -->
                <div v-if="isGeocodingLoading" class="flex items-center gap-2 text-sm text-blue-600">
                  <Icon icon="lucide:loader-2" class="h-4 w-4 animate-spin" />
                  <span>Localizando no mapa...</span>
                </div>

                <!-- Geocoding Success -->
                <div v-else-if="geocodingSuccess" class="flex items-center gap-2 text-sm text-green-600">
                  <Icon icon="lucide:map-pin" class="h-4 w-4" />
                  <span>Sua loja aparecerá no mapa com localização {{ cepSuccess && form.zip_code && form.address ? 'precisa' : 'aproximada' }}!</span>
                </div>

                <!-- Geocoding Error -->
                <div v-else-if="geocodingError" class="flex items-center gap-2 text-sm text-red-600">
                  <Icon icon="lucide:alert-circle" class="h-4 w-4" />
                  <span>{{ geocodingError }}</span>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Actions -->
          <div class="flex justify-end gap-4">
            <Button type="button" variant="outline" :as="Link" :href="route('dashboard')">
              Cancelar
            </Button>
            <Button type="submit" :disabled="form.processing" class="bg-teal-600 hover:bg-teal-700">
              <Icon v-if="form.processing" icon="lucide:loader-2" class="mr-2 h-4 w-4 animate-spin" />
              Salvar Alterações
            </Button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
