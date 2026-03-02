<script setup>
import { computed, onMounted, ref } from 'vue'
import { Icon } from '@iconify/vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import Input from '@/Components/shadcn/ui/input/Input.vue'
import Label from '@/Components/shadcn/ui/label/Label.vue'
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from '@/Components/shadcn/ui/tabs'

const form = defineModel()
const emit = defineEmits(['next', 'prev'])

const photoPreviews = ref([])
const logoPreview = ref(null)
const videoMode = ref('url') // 'url' or 'upload'
const videoPreview = ref(null)

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
            name: file.name,
          })
        }
        reader.readAsDataURL(file)
      }
    })
  }
})

function handleLogoUpload(e) {
  const file = e.target.files[0]
  if (!file)
    return

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
  const validFiles = files.filter((file) => {
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

  filesToAdd.forEach((file) => {
    form.value.photos.push(file)

    const reader = new FileReader()
    reader.onload = (e) => {
      photoPreviews.value.push({
        url: e.target.result,
        name: file.name,
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

// Video functions
function handleVideoUpload(e) {
  const file = e.target.files[0]
  if (!file)
    return

  if (!file.type.startsWith('video/')) {
    alert('Apenas vídeos são permitidos')
    return
  }
  if (file.size > 100 * 1024 * 1024) { // 100MB max
    alert('Vídeo muito grande. Máximo 100MB.')
    return
  }

  form.value.video_file = file
  form.value.video_url = null // Clear URL if file is uploaded

  const reader = new FileReader()
  reader.onload = (e) => {
    videoPreview.value = e.target.result
  }
  reader.readAsDataURL(file)
}

function removeVideo() {
  form.value.video_file = null
  form.value.video_url = null
  videoPreview.value = null
}

function extractVideoId(url) {
  // YouTube
  const youtubeRegex = /(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/
  const youtubeMatch = url.match(youtubeRegex)
  if (youtubeMatch)
    return { platform: 'youtube', id: youtubeMatch[1] }

  // Vimeo
  const vimeoRegex = /vimeo\.com\/(?:.*\/)?(\d+)/
  const vimeoMatch = url.match(vimeoRegex)
  if (vimeoMatch)
    return { platform: 'vimeo', id: vimeoMatch[1] }

  return null
}

const videoEmbedUrl = computed(() => {
  if (!form.value.video_url)
    return null

  const videoInfo = extractVideoId(form.value.video_url)
  if (!videoInfo)
    return null

  if (videoInfo.platform === 'youtube') {
    return `https://www.youtube.com/embed/${videoInfo.id}`
  }
  if (videoInfo.platform === 'vimeo') {
    return `https://player.vimeo.com/video/${videoInfo.id}`
  }

  return null
})

const isValid = () => {
  return form.value.logo && form.value.photos.length >= 3
}
</script>

<template>
  <div class="space-y-6">
    <div class="text-center mb-8">
      <div class="w-16 h-16 bg-teal-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <Icon icon="lucide:image" class="h-8 w-8 text-teal-600" />
      </div>
      <h2 class="text-2xl font-bold mb-2">
        Fotos da Loja
      </h2>
      <p class="text-muted-foreground">
        Mostre seus produtos com fotos de qualidade
      </p>
    </div>

    <div class="space-y-6">
      <!-- Upload de Logo -->
      <div>
        <Label class="text-base mb-2 block">Logo da Loja *</Label>
        <div class="flex gap-4 items-start">
          <div class="flex-shrink-0">
            <div v-if="logoPreview" class="relative group">
              <img :src="logoPreview" alt="Logo preview" class="w-32 h-32 object-cover rounded-lg border-2 border-gray-300">
              <button
                type="button"
                class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
                @click="removeLogo"
              >
                <Icon icon="lucide:x" class="h-4 w-4" />
              </button>
            </div>
            <div v-else class="w-32 h-32 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center cursor-pointer hover:border-teal-500 transition-colors">
              <label for="logo-upload" class="cursor-pointer text-center">
                <Icon icon="lucide:image-plus" class="h-8 w-8 text-gray-400 mx-auto mb-1" />
                <span class="text-xs text-gray-500">Adicionar logo</span>
                <input
                  id="logo-upload"
                  type="file"
                  accept="image/*"
                  class="hidden"
                  @change="handleLogoUpload"
                >
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
        <Label class="text-base mb-2 block">Fotos de Destaque da Loja * (mínimo 3, máximo 10)</Label>
        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-teal-500 transition-colors">
          <input
            id="photos-upload"
            type="file"
            multiple
            accept="image/*"
            class="hidden"
            @change="handlePhotosUpload"
          >
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
            <img :src="photo.url" :alt="photo.name" class="w-full h-full object-cover">
            <button
              type="button"
              class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
              @click="removePhoto(index)"
            >
              <Icon icon="lucide:x" class="h-4 w-4" />
            </button>
          </div>
        </div>
        <p class="text-xs text-muted-foreground mt-2">
          {{ form.photos.length }} / 10 fotos adicionadas
        </p>
      </div>

      <!-- Upload de Vídeo -->
      <div class="border-t pt-6">
        <Label class="text-base mb-2 block">Vídeo da Loja (opcional)</Label>
        <p class="text-sm text-gray-700 mb-4">
          Adicione um vídeo para mostrar mais sobre seus produtos. Pode ser um link do YouTube/Vimeo ou fazer upload.
        </p>

        <Tabs v-model="videoMode" class="w-full">
          <TabsList class="grid w-full grid-cols-2">
            <TabsTrigger value="url">
              <Icon icon="lucide:link" class="mr-2 h-4 w-4" />
              Link do Vídeo
            </TabsTrigger>
            <TabsTrigger value="upload">
              <Icon icon="lucide:upload" class="mr-2 h-4 w-4" />
              Upload de Arquivo
            </TabsTrigger>
          </TabsList>

          <!-- URL Tab -->
          <TabsContent value="url" class="space-y-4">
            <div>
              <Label for="video-url">URL do YouTube ou Vimeo</Label>
              <Input
                id="video-url"
                v-model="form.video_url"
                type="url"
                placeholder="https://www.youtube.com/watch?v=..."
                class="mt-2"
                @input="form.video_file = null"
              />
              <p class="text-xs text-muted-foreground mt-1">
                Cole o link completo do vídeo do YouTube ou Vimeo
              </p>
            </div>

            <!-- Video Preview (YouTube/Vimeo) -->
            <div v-if="videoEmbedUrl" class="relative aspect-video rounded-lg overflow-hidden bg-gray-100">
              <iframe
                :src="videoEmbedUrl"
                class="w-full h-full"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
              />
              <button
                type="button"
                class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-2"
                @click="removeVideo"
              >
                <Icon icon="lucide:x" class="h-4 w-4" />
              </button>
            </div>
          </TabsContent>

          <!-- Upload Tab -->
          <TabsContent value="upload" class="space-y-4">
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-teal-500 transition-colors">
              <input
                id="video-upload"
                type="file"
                accept="video/*"
                class="hidden"
                @change="handleVideoUpload"
              >
              <label for="video-upload" class="cursor-pointer">
                <Icon icon="lucide:video" class="h-12 w-12 text-gray-400 mx-auto mb-2" />
                <p class="text-sm font-medium text-gray-700 mb-1">
                  Clique para adicionar vídeo
                </p>
                <p class="text-xs text-muted-foreground">
                  MP4, MOV ou WEBM (máx. 100MB)
                </p>
              </label>
            </div>

            <!-- Video Preview (Uploaded File) -->
            <div v-if="form.video_file" class="relative aspect-video rounded-lg overflow-hidden bg-gray-100">
              <video :src="videoPreview" controls class="w-full h-full" />
              <button
                type="button"
                class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-2"
                @click="removeVideo"
              >
                <Icon icon="lucide:x" class="h-4 w-4" />
              </button>
            </div>
          </TabsContent>
        </Tabs>
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
        :disabled="!isValid()"
        @click="emit('next')"
      >
        Continuar
        <Icon icon="lucide:arrow-right" class="ml-2 h-5 w-5" />
      </Button>
    </div>
  </div>
</template>
