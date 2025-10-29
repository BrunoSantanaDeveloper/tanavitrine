<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import Input from '@/Components/shadcn/ui/input/Input.vue'
import Label from '@/Components/shadcn/ui/label/Label.vue'
import Textarea from '@/Components/shadcn/ui/textarea/Textarea.vue'
import Checkbox from '@/Components/shadcn/ui/checkbox/Checkbox.vue'
import FileUpload from '@/Components/FileUpload.vue'
import AddressForm from '@/Components/AddressForm.vue'
import SocialMediaInputs from '@/Components/SocialMediaInputs.vue'
import { formatPhone } from '@/utils/formatters'

const form = defineModel()
const emit = defineEmits(['next', 'prev'])

const serviceTypes = [
  'Consulta Veterinária',
  'Cirurgias',
  'Exames Laboratoriais',
  'Vacinas',
  'Banho e Tosa',
  'Hotel/Creche',
  'Emergência 24h',
  'Fisioterapia',
  'Acupuntura',
  'Nutrição Animal',
  'Dentista Veterinário',
  'Outros',
]

const logoPreview = ref(null)
const videoOption = ref('link') // 'link' ou 'upload'

const isValid = computed(() => {
  return (
    form.value.logo &&
    form.value.photos.length >= 5 &&
    form.value.service_types.length > 0 &&
    form.value.address.cep &&
    form.value.address.street &&
    form.value.address.number &&
    form.value.address.neighborhood &&
    form.value.address.city &&
    form.value.address.state &&
    form.value.phone
  )
})

function handleLogoUpload(e) {
  const file = e.target.files[0]
  if (file && file.type.startsWith('image/')) {
    if (file.size <= 2 * 1024 * 1024) { // 2MB max
      form.value.logo = file

      const reader = new FileReader()
      reader.onload = (e) => {
        logoPreview.value = e.target.result
      }
      reader.readAsDataURL(file)
    } else {
      alert('A logo deve ter no máximo 2MB')
    }
  }
}

function removeLogo() {
  form.value.logo = null
  logoPreview.value = null
}

function toggleServiceType(type) {
  const index = form.value.service_types.indexOf(type)
  if (index > -1) {
    form.value.service_types.splice(index, 1)
  } else {
    form.value.service_types.push(type)
  }
}

function handlePhoneInput(e) {
  form.value.phone = formatPhone(e.target.value)
}

// Restaurar preview da logo quando o componente montar
onMounted(() => {
  if (form.value.logo instanceof File) {
    const reader = new FileReader()
    reader.onload = (e) => {
      logoPreview.value = e.target.result
    }
    reader.readAsDataURL(form.value.logo)
  }

  // Se video_link estiver preenchido, selecionar opção 'link'
  if (form.value.video_link) {
    videoOption.value = 'link'
  } else if (form.value.video) {
    videoOption.value = 'upload'
  }
})

// Observar mudanças no form.logo para atualizar preview
watch(() => form.value.logo, (newLogo) => {
  if (newLogo instanceof File) {
    const reader = new FileReader()
    reader.onload = (e) => {
      logoPreview.value = e.target.result
    }
    reader.readAsDataURL(newLogo)
  } else if (!newLogo) {
    logoPreview.value = null
  }
})
</script>

<template>
  <div class="space-y-8">
    <div class="text-center mb-6">
      <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
        <Icon icon="lucide:palette" class="h-8 w-8 text-primary" />
      </div>
      <h2 class="text-2xl font-bold mb-2">Personalize sua Clínica</h2>
      <p class="text-muted-foreground">
        Essas informações nos ajudarão a criar sua programação inicial
      </p>
    </div>

    <!-- Upload da Logo -->
    <div>
      <Label class="text-base mb-3 block">Logo da Clínica *</Label>
      <div class="flex flex-col items-center gap-4">
        <div
          v-if="!logoPreview"
          class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center w-full hover:border-primary transition-colors cursor-pointer"
          @click="$refs.logoInput.click()"
        >
          <Icon icon="lucide:image-plus" class="h-12 w-12 mx-auto mb-4 text-muted-foreground" />
          <p class="mb-2">Clique para fazer upload da logo</p>
          <p class="text-xs text-muted-foreground">PNG, JPG ou WEBP - Máximo 2MB</p>
          <input
            ref="logoInput"
            type="file"
            accept="image/png,image/jpeg,image/jpg,image/webp"
            class="hidden"
            @change="handleLogoUpload"
          />
        </div>

        <div v-else class="relative w-full max-w-xs">
          <img :src="logoPreview" class="w-full h-48 object-contain bg-gray-100 rounded-lg border-2 border-primary" alt="Logo preview" />
          <button
            type="button"
            @click="removeLogo"
            class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-2 hover:bg-red-600 transition-colors"
          >
            <Icon icon="lucide:x" class="h-4 w-4" />
          </button>
          <p class="text-xs text-center text-muted-foreground mt-2">
            Logo carregada com sucesso ✓
          </p>
        </div>
      </div>
    </div>

    <!-- Upload de Fotos -->
    <div>
      <FileUpload
        v-model="form.photos"
        :min-files="5"
        :max-files="20"
        label="Fotos da Clínica"
      />
    </div>

    <!-- Tipos de Atendimento -->
    <div>
      <Label class="text-base mb-3 block">Tipos de Atendimento Oferecidos *</Label>
      <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
        <label
          v-for="type in serviceTypes"
          :key="type"
          class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer transition-colors hover:bg-muted"
          :class="form.service_types.includes(type) ? 'border-primary bg-primary/5' : 'border-gray-300'"
        >
          <Checkbox
            :checked="form.service_types.includes(type)"
            @update:checked="toggleServiceType(type)"
          />
          <span class="text-sm">{{ type }}</span>
        </label>
      </div>
      <p class="text-xs text-muted-foreground mt-2">
        Selecione todos os serviços que sua clínica oferece
      </p>
    </div>

    <!-- Endereço -->
    <div>
      <Label class="text-base mb-3 block">Endereço da Clínica *</Label>
      <AddressForm v-model="form.address" />
    </div>

    <!-- Telefone -->
    <div>
      <Label for="phone">Telefone de Contato *</Label>
      <Input
        id="phone"
        v-model="form.phone"
        type="tel"
        placeholder="(62) 99999-9999"
        class="mt-1"
        @input="handlePhoneInput"
        maxlength="15"
      />
    </div>

    <!-- Redes Sociais e Website (Opcional) -->
    <div>
      <Label class="text-base mb-3 block">Redes Sociais e Website (Opcional)</Label>
      <SocialMediaInputs v-model="form.social_media" />
    </div>

    <!-- Vídeo Institucional (Opcional) -->
    <div>
      <Label class="text-base mb-3 block">Vídeo Institucional (Opcional)</Label>

      <!-- Toggle entre Link e Upload -->
      <div class="flex gap-2 mb-4">
        <Button
          type="button"
          :variant="videoOption === 'link' ? 'default' : 'outline'"
          @click="videoOption = 'link'; form.video = null"
          class="flex-1"
        >
          <Icon icon="lucide:link" class="mr-2 h-4 w-4" />
          Link do Vídeo
        </Button>
        <Button
          type="button"
          :variant="videoOption === 'upload' ? 'default' : 'outline'"
          @click="videoOption = 'upload'; form.video_link = ''"
          class="flex-1"
        >
          <Icon icon="lucide:upload" class="mr-2 h-4 w-4" />
          Fazer Upload
        </Button>
      </div>

      <!-- Campo de Link -->
      <div v-if="videoOption === 'link'">
        <Input
          id="video-link"
          v-model="form.video_link"
          type="url"
          placeholder="https://www.youtube.com/watch?v=... ou https://vimeo.com/..."
          class="mt-1"
        />
        <p class="text-xs text-muted-foreground mt-1">
          Cole o link do YouTube, Vimeo ou outro serviço de vídeo
        </p>
      </div>

      <!-- Campo de Upload -->
      <div v-else>
        <Input
          id="video"
          type="file"
          accept="video/mp4,video/mov,video/avi"
          class="mt-1"
          @change="e => form.video = e.target.files[0]"
        />
        <p class="text-xs text-muted-foreground mt-1">
          Máximo 50MB - Formatos: MP4, MOV, AVI
        </p>
      </div>
    </div>

    <!-- Slogan/Mensagem (Opcional) -->
    <div>
      <Label for="slogan">Slogan ou Mensagem da Clínica (Opcional)</Label>
      <Textarea
        id="slogan"
        v-model="form.slogan"
        placeholder="Ex: Cuidando do seu pet com amor e dedicação"
        rows="3"
        class="mt-1"
        maxlength="200"
      />
      <p class="text-xs text-muted-foreground mt-1">
        {{ form.slogan?.length || 0 }}/200 caracteres
      </p>
    </div>

    <!-- Botões -->
    <div class="flex justify-between pt-4">
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
        @click="emit('next')"
        :disabled="!isValid"
      >
        Continuar
        <Icon icon="lucide:arrow-right" class="ml-2 h-5 w-5" />
      </Button>
    </div>
  </div>
</template>
