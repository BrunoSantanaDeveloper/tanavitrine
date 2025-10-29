<script setup>
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import Input from '@/Components/shadcn/ui/input/Input.vue'
import Label from '@/Components/shadcn/ui/label/Label.vue'
import AddressForm from '@/Components/AddressForm.vue'
import Select from '@/Components/shadcn/ui/select/Select.vue'
import SelectContent from '@/Components/shadcn/ui/select/SelectContent.vue'
import SelectItem from '@/Components/shadcn/ui/select/SelectItem.vue'
import SelectTrigger from '@/Components/shadcn/ui/select/SelectTrigger.vue'
import SelectValue from '@/Components/shadcn/ui/select/SelectValue.vue'
import { formatPhone } from '@/utils/formatters'

const form = defineModel()
const emit = defineEmits(['next', 'prev'])

const photoPreviews = ref([])
const logoPreview = ref(null)

// Restaurar previews quando o componente for montado
onMounted(() => {
  // Restaurar preview do logo
  if (form.value.logo && form.value.logo instanceof File) {
    const reader = new FileReader()
    reader.onload = (e) => {
      logoPreview.value = e.target.result
    }
    reader.readAsDataURL(form.value.logo)
  }

  // Restaurar previews das fotos
  if (form.value.photos && form.value.photos.length > 0) {
    form.value.photos.forEach((file) => {
      if (file instanceof File) {
        const reader = new FileReader()
        reader.onload = (e) => {
          photoPreviews.value.push({
            url: e.target.result,
            name: file.name
          })
        }
        reader.readAsDataURL(file)
      }
    })
  }
})

const states = [
  'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
  'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
  'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'
]

const isValid = computed(() => {
  return (
    form.value.logo &&
    form.value.photos.length >= 3 &&
    form.value.address.city &&
    form.value.address.state &&
    form.value.whatsapp
  )
})

function handleLogoUpload(e) {
  const file = e.target.files[0]
  if (!file) return

  if (!file.type.startsWith('image/')) {
    alert('Apenas imagens são permitidas')
    return
  }
  if (file.size > 2 * 1024 * 1024) { // 2MB max
    alert('Logo muito grande. Máximo 2MB.')
    return
  }

  form.value.logo = file

  const reader = new FileReader()
  reader.onload = (e) => {
    logoPreview.value = e.target.result
  }
  reader.readAsDataURL(file)
}

function removeLogo() {
  form.value.logo = null
  logoPreview.value = null
}

function handlePhotosUpload(e) {
  const files = Array.from(e.target.files)
  const validFiles = files.filter(file => {
    if (!file.type.startsWith('image/')) {
      alert('Apenas imagens são permitidas')
      return false
    }
    if (file.size > 5 * 1024 * 1024) { // 5MB max
      alert(`${file.name} é muito grande. Máximo 5MB por foto.`)
      return false
    }
    return true
  })

  // Limitar a 10 fotos no total
  const remainingSlots = 10 - form.value.photos.length
  const filesToAdd = validFiles.slice(0, remainingSlots)

  filesToAdd.forEach(file => {
    form.value.photos.push(file)

    const reader = new FileReader()
    reader.onload = (e) => {
      photoPreviews.value.push({
        url: e.target.result,
        name: file.name
      })
    }
    reader.readAsDataURL(file)
  })

  if (validFiles.length > remainingSlots) {
    alert(`Você pode adicionar no máximo 10 fotos. ${validFiles.length - remainingSlots} foto(s) foram ignoradas.`)
  }
}

function removePhoto(index) {
  form.value.photos.splice(index, 1)
  photoPreviews.value.splice(index, 1)
}

function handleWhatsAppInput(e) {
  form.value.whatsapp = formatPhone(e.target.value)
}

function handlePhoneInput(e) {
  form.value.phone = formatPhone(e.target.value)
}
</script>

<template>
  <div class="space-y-6">
    <div class="text-center mb-8">
      <div class="w-16 h-16 bg-teal-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <Icon icon="lucide:image" class="h-8 w-8 text-teal-600" />
      </div>
      <h2 class="text-2xl font-bold mb-2">Fotos e Contato</h2>
      <p class="text-muted-foreground">
        Mostre seus produtos e facilite o contato com seus clientes
      </p>
    </div>

    <div class="space-y-6">
      <!-- Upload de Logo -->
      <div>
        <Label class="text-base mb-2 block">Logo da Loja *</Label>
        <div class="flex gap-4 items-start">
          <div class="flex-shrink-0">
            <div v-if="logoPreview" class="relative group">
              <img :src="logoPreview" alt="Logo preview" class="w-32 h-32 object-cover rounded-lg border-2 border-gray-300" />
              <button
                type="button"
                @click="removeLogo"
                class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
              >
                <Icon icon="lucide:x" class="h-4 w-4" />
              </button>
            </div>
            <div v-else class="w-32 h-32 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center cursor-pointer hover:border-teal-500 transition-colors">
              <label for="logo-upload" class="cursor-pointer text-center">
                <Icon icon="lucide:image-plus" class="h-8 w-8 text-gray-400 mx-auto mb-1" />
                <span class="text-xs text-gray-500">Adicionar logo</span>
                <input
                  type="file"
                  id="logo-upload"
                  accept="image/*"
                  class="hidden"
                  @change="handleLogoUpload"
                />
              </label>
            </div>
          </div>
          <div class="flex-1">
            <p class="text-sm text-gray-700 mb-2">
              Faça upload do logo da sua loja. Este logo aparecerá no topo da sua vitrine.
            </p>
            <ul class="text-xs text-muted-foreground space-y-1">
              <li>• Formato: PNG, JPG ou WEBP</li>
              <li>• Tamanho máximo: 2MB</li>
              <li>• Recomendado: imagem quadrada (ex: 512x512)</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Upload de Fotos -->
      <div class="border-t pt-6">
        <Label class="text-base mb-2 block">Fotos dos Produtos * (mínimo 3, máximo 10)</Label>
        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-teal-500 transition-colors">
          <input
            type="file"
            id="photos-upload"
            multiple
            accept="image/*"
            class="hidden"
            @change="handlePhotosUpload"
          />
          <label for="photos-upload" class="cursor-pointer">
            <Icon icon="lucide:upload-cloud" class="h-12 w-12 text-gray-400 mx-auto mb-2" />
            <p class="text-sm font-medium text-gray-700 mb-1">
              Clique para adicionar fotos
            </p>
            <p class="text-xs text-muted-foreground">
              PNG, JPG ou WEBP (máx. 5MB cada)
            </p>
          </label>
        </div>

        <!-- Preview das fotos -->
        <div v-if="photoPreviews.length > 0" class="grid grid-cols-3 gap-4 mt-4">
          <div
            v-for="(photo, index) in photoPreviews"
            :key="index"
            class="relative group aspect-square rounded-lg overflow-hidden border"
          >
            <img :src="photo.url" :alt="photo.name" class="w-full h-full object-cover" />
            <button
              type="button"
              @click="removePhoto(index)"
              class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
            >
              <Icon icon="lucide:x" class="h-4 w-4" />
            </button>
          </div>
        </div>
        <p class="text-xs text-muted-foreground mt-2">
          {{ form.photos.length }} / 10 fotos adicionadas
        </p>
      </div>

      <!-- Localização -->
      <div class="border-t pt-6">
        <h3 class="font-semibold text-lg mb-4">Localização</h3>
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

        <div class="grid grid-cols-2 gap-4 mt-4">
          <div>
            <Label for="cep">CEP (opcional)</Label>
            <Input
              id="cep"
              v-model="form.address.cep"
              placeholder="00000-000"
              class="mt-2"
            />
          </div>
          <div>
            <Label for="street">Rua (opcional)</Label>
            <Input
              id="street"
              v-model="form.address.street"
              placeholder="Nome da rua"
              class="mt-2"
            />
          </div>
        </div>
      </div>

      <!-- Contato -->
      <div class="border-t pt-6">
        <h3 class="font-semibold text-lg mb-4">Informações de Contato</h3>
        <div class="space-y-4">
          <div>
            <Label for="whatsapp">WhatsApp * <span class="text-teal-600">(Principal forma de contato)</span></Label>
            <Input
              id="whatsapp"
              v-model="form.whatsapp"
              type="tel"
              placeholder="(00) 00000-0000"
              class="mt-2"
              @input="handleWhatsAppInput"
            />
          </div>

          <div>
            <Label for="phone">Telefone Fixo (opcional)</Label>
            <Input
              id="phone"
              v-model="form.phone"
              type="tel"
              placeholder="(00) 0000-0000"
              class="mt-2"
              @input="handlePhoneInput"
            />
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
        @click="emit('next')"
        :disabled="!isValid"
      >
        Continuar
        <Icon icon="lucide:arrow-right" class="ml-2 h-5 w-5" />
      </Button>
    </div>
  </div>
</template>
