<script setup>
import { computed, ref, watch } from 'vue'
import axios from 'axios'
import { Icon } from '@iconify/vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import Input from '@/Components/shadcn/ui/input/Input.vue'
import Label from '@/Components/shadcn/ui/label/Label.vue'
import Select from '@/Components/shadcn/ui/select/Select.vue'
import SelectContent from '@/Components/shadcn/ui/select/SelectContent.vue'
import SelectItem from '@/Components/shadcn/ui/select/SelectItem.vue'
import SelectTrigger from '@/Components/shadcn/ui/select/SelectTrigger.vue'
import SelectValue from '@/Components/shadcn/ui/select/SelectValue.vue'
import { formatCEP, formatPhone } from '@/utils/formatters'

const form = defineModel()
const emit = defineEmits(['next', 'prev'])

// Geocoding state
const isGeocodingLoading = ref(false)
const geocodingError = ref(null)
const geocodingSuccess = ref(false)

// CEP lookup state
const isCepLoading = ref(false)
const cepError = ref(null)
const cepSuccess = ref(false)

const states = [
  'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
  'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
  'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
]

const requiresLocation = computed(() => form.value.store_type !== 'virtual')

const isValid = computed(() => {
  if (!form.value.whatsapp) return false

  if (!requiresLocation.value) return true

  return (
    form.value.address.cep
    && form.value.address.street
    && form.value.address.city
    && form.value.address.state
  )
})

function handleWhatsAppInput(e) {
  form.value.whatsapp = formatPhone(e.target.value)
}

function handleCepInput(e) {
  form.value.address.cep = formatCEP(e.target.value)

  // Clear previous states when CEP is being edited
  cepError.value = null
  cepSuccess.value = false
  geocodingError.value = null
  geocodingSuccess.value = false

  // Trigger CEP lookup when 8 digits are entered
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
    form.value.address.street = response.data.logradouro || ''
    form.value.address.neighborhood = response.data.bairro || ''
    form.value.address.city = response.data.localidade || ''
    form.value.address.state = response.data.uf || ''

    cepSuccess.value = true

    console.log('CEP lookup success:', response.data)

    // Trigger geocoding automatically after successful CEP lookup
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
  const { cep, street, number, city, state } = form.value.address

  // Check if at least city and state are filled
  if (!city || !state) {
    return
  }

  isGeocodingLoading.value = true
  geocodingError.value = null
  geocodingSuccess.value = false

  try {
    // Build address query with maximum detail available
    // Priority: CEP + Street > Street + City > City only
    let query = ''

    const streetWithNumber = [street, number].filter(Boolean).join(', ')

    if (cep && streetWithNumber) {
      // Most precise: use CEP and street
      query = `${streetWithNumber}, ${cep}, ${city}, ${state}, Brazil`
    }
    else if (streetWithNumber) {
      // Use street with city and state
      query = `${streetWithNumber}, ${city}, ${state}, Brazil`
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
        countrycodes: 'br', // Limit to Brazil
        addressdetails: 1, // Get detailed address info
      },
      headers: {
        'User-Agent': 'TanaVitrine/1.0', // Required by Nominatim
      },
    })

    if (response.data && response.data.length > 0) {
      const location = response.data[0]

      // Save coordinates to form
      form.value.latitude = Number.parseFloat(location.lat)
      form.value.longitude = Number.parseFloat(location.lon)

      geocodingSuccess.value = true

      console.log('Geocoding success:', {
        query,
        lat: form.value.latitude,
        lng: form.value.longitude,
        precision: streetWithNumber ? 'street-level' : 'city-level',
      })
    }
    else {
      geocodingError.value = 'Localização não encontrada. Verifique o endereço informado.'
    }
  }
  catch (error) {
    console.error('Geocoding error:', error)
    geocodingError.value = 'Erro ao buscar localização. Tente novamente.'
  }
  finally {
    isGeocodingLoading.value = false
  }
}

// Watch for changes in address fields to trigger geocoding
const geocodingTimeout = ref(null)

watch(
  () => [form.value.address.cep, form.value.address.street, form.value.address.city, form.value.address.state],
  ([newCep, newStreet, newCity, newState], [oldCep, oldStreet, oldCity, oldState]) => {
    // Only geocode if city and state are filled, CEP is complete (8 digits), and something changed
    const cepNumbers = newCep?.replace(/\D/g, '') || ''
    const isCepComplete = cepNumbers.length === 8
    const hasChanged = newCep !== oldCep || newStreet !== oldStreet || newCity !== oldCity || newState !== oldState

    // Don't trigger geocoding if there's a CEP error or if CEP lookup is still loading
    if (cepError.value || isCepLoading.value) {
      return
    }

    // Only trigger geocoding if CEP is complete, city/state filled, fields changed, and CEP was found successfully
    // This prevents geocoding from running on incomplete or invalid CEPs
    if (newCity && newState && isCepComplete && hasChanged && cepSuccess.value) {
      // Debounce: wait 1 second after user stops typing
      clearTimeout(geocodingTimeout.value)
      geocodingTimeout.value = setTimeout(() => {
        geocodeAddress()
      }, 1000)
    }
  },
)
</script>

<template>
  <div class="space-y-6">
    <div class="text-center mb-8">
      <div class="w-16 h-16 bg-teal-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <Icon icon="lucide:map-pin" class="h-8 w-8 text-teal-600" />
      </div>
      <h2 class="text-2xl font-bold mb-2">
        Localização e Contato
      </h2>
      <p class="text-muted-foreground">
        Facilite que seus clientes te encontrem
      </p>
    </div>

    <div class="space-y-6">
      <!-- Localização -->
      <div v-if="requiresLocation">
        <h3 class="font-semibold text-lg mb-4">
          Localização
        </h3>

        <!-- CEP Field (First) -->
        <div class="mb-4">
          <Label for="cep">
            CEP *
            <span class="text-xs text-teal-600 font-medium">(Digite o CEP para preenchimento automático)</span>
          </Label>
          <Input
            id="cep"
            v-model="form.address.cep"
            placeholder="00000-000"
            maxlength="9"
            class="mt-2"
            @input="handleCepInput"
          />

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

        <!-- Street and Number -->
        <div class="grid grid-cols-3 gap-4 mb-4">
          <div class="col-span-2">
            <Label for="street">Rua *</Label>
            <Input
              id="street"
              v-model="form.address.street"
              placeholder="Nome da rua"
              class="mt-2"
            />
          </div>
          <div>
            <Label for="number">Número</Label>
            <Input
              id="number"
              v-model="form.address.number"
              placeholder="123"
              class="mt-2"
            />
          </div>
        </div>

        <!-- Complement -->
        <div class="mb-4">
          <Label for="complement">Complemento</Label>
          <Input
            id="complement"
            v-model="form.address.complement"
            placeholder="Ex: Sala 3, Bloco B"
            class="mt-2"
          />
        </div>

        <!-- City and State -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <Label for="city">Cidade *</Label>
            <Input
              id="city"
              v-model="form.address.city"
              placeholder="Ex: São Paulo"
              class="mt-2"
            />
          </div>
          <div>
            <Label for="state">Estado *</Label>
            <Select v-model="form.address.state">
              <SelectTrigger class="mt-2">
                <SelectValue placeholder="UF" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="state in states" :key="state" :value="state">
                  {{ state }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>

        <div class="mt-4">
          <Label for="google_maps_url">Link do Google Maps (opcional)</Label>
          <Input
            id="google_maps_url"
            v-model="form.google_maps_url"
            type="url"
            placeholder="https://maps.app.goo.gl/... ou https://www.google.com/maps/..."
            class="mt-2"
          />
          <p class="text-xs text-muted-foreground mt-1">
            Cole o link de compartilhamento da sua loja no Google Maps para direcionar clientes com maior precisão.
          </p>
        </div>

        <!-- Geocoding Feedback -->
        <div class="mt-4">
          <!-- Geocoding Loading State -->
          <div v-if="isGeocodingLoading" class="flex items-center gap-2 text-sm text-blue-600">
            <Icon icon="lucide:loader-2" class="h-4 w-4 animate-spin" />
            <span>Localizando no mapa...</span>
          </div>

          <!-- Geocoding Success State -->
          <div v-else-if="geocodingSuccess" class="flex items-center gap-2 text-sm text-green-600">
            <Icon icon="lucide:map-pin" class="h-4 w-4" />
            <span>Sua loja aparecerá no mapa com localização {{ cepSuccess && form.address.cep && form.address.street ? 'precisa' : 'aproximada' }}!</span>
          </div>

          <!-- Geocoding Error State -->
          <div v-else-if="geocodingError" class="flex items-center gap-2 text-sm text-red-600">
            <Icon icon="lucide:alert-circle" class="h-4 w-4" />
            <span>{{ geocodingError }}</span>
          </div>

          <!-- Hidden fields for lat/lng -->
          <input v-model="form.latitude" type="hidden">
          <input v-model="form.longitude" type="hidden">
        </div>
      </div>

      <div v-else class="rounded-lg border border-dashed border-teal-300 bg-teal-50/60 p-4">
        <div class="flex items-start gap-3">
          <Icon icon="lucide:monitor" class="h-5 w-5 text-teal-700 mt-0.5" />
          <div>
            <p class="font-medium text-teal-900">Loja virtual selecionada</p>
            <p class="text-sm text-teal-800/90">
              A etapa de localização foi ocultada. Você poderá informar apenas os canais de contato.
            </p>
          </div>
        </div>
      </div>

      <!-- Contato -->
      <div class="border-t pt-6">
        <h3 class="font-semibold text-lg mb-4">
          Informações de Contato
        </h3>
        <div class="space-y-4">
          <div>
            <Label for="whatsapp">WhatsApp * <span class="text-teal-600">(Principal forma de contato)</span></Label>
            <Input
              id="whatsapp"
              v-model="form.whatsapp"
              type="tel"
              placeholder="(00) 00000-0000"
              maxlength="15"
              class="mt-2"
              @input="handleWhatsAppInput"
            />
            <p class="text-xs text-muted-foreground mt-1">
              Formato: (00) 00000-0000
            </p>
          </div>

          <div>
            <Label for="instagram">Instagram (opcional)</Label>
            <div class="flex gap-2 mt-2">
              <span class="flex items-center px-3 bg-gray-100 border border-r-0 rounded-l-md text-sm text-gray-600">
                @
              </span>
              <Input
                id="instagram"
                v-model="form.social_media.instagram"
                placeholder="seu_usuario"
                class="rounded-l-none"
              />
            </div>
          </div>

          <div>
            <Label for="facebook">Facebook (opcional)</Label>
            <Input
              id="facebook"
              v-model="form.social_media.facebook"
              type="url"
              placeholder="https://facebook.com/..."
              class="mt-2"
            />
          </div>

          <div>
            <Label for="tiktok">TikTok (opcional)</Label>
            <div class="flex gap-2 mt-2">
              <span class="flex items-center px-3 bg-gray-100 border border-r-0 rounded-l-md text-sm text-gray-600">
                @
              </span>
              <Input
                id="tiktok"
                v-model="form.social_media.tiktok"
                placeholder="seu_usuario"
                class="rounded-l-none"
              />
            </div>
          </div>

          <div>
            <Label for="website">Website (opcional)</Label>
            <Input
              id="website"
              v-model="form.social_media.website"
              type="url"
              placeholder="https://..."
              class="mt-2"
            />
          </div>
        </div>
      </div>
    </div>

    <div class="flex justify-between pt-6">
      <Button
        variant="outline"
        size="lg"
        @click="emit('prev')"
      >
        <Icon icon="lucide:arrow-left" class="mr-2 h-5 w-5" />
        Voltar
      </Button>
      <Button
        size="lg"
        class="bg-teal-600 hover:bg-teal-700"
        :disabled="!isValid"
        @click="emit('next')"
      >
        Continuar
        <Icon icon="lucide:arrow-right" class="ml-2 h-5 w-5" />
      </Button>
    </div>
  </div>
</template>
