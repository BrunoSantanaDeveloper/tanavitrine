<script setup>
import { computed, ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { Button } from '@/Components/shadcn/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/shadcn/ui/card'
import { Icon } from '@iconify/vue'
import { Badge } from '@/Components/shadcn/ui/badge'
import { Input } from '@/Components/shadcn/ui/input'
import { Label } from '@/Components/shadcn/ui/label'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/shadcn/ui/tabs'
import { toast } from 'vue-sonner'

const props = defineProps({
  store: {
    type: Object,
    required: true
  }
})

const fileInput = ref(null)
const uploadForm = useForm({
  photo: null
})

const videoMode = ref('url') // 'url' or 'upload'
const videoPreview = ref(props.store.video_url || null)
const videoInput = ref(null)

const videoForm = useForm({
  video_url: props.store.video_url || null,
  video: null,
})

function handleFileSelect(event) {
  const file = event.target.files[0]
  if (file) {
    uploadForm.photo = file
    uploadPhotos()
  }
}

function uploadPhotos() {
  if (!uploadForm.photo) return

  uploadForm.post(route('dashboard.stores.photos.upload', props.store.slug), {
    preserveScroll: true,
    onSuccess: () => {
      uploadForm.reset()
      if (fileInput.value) {
        fileInput.value.value = ''
      }
    }
  })
}

function deletePhoto(photoId) {
  if (confirm('Tem certeza que deseja excluir esta foto?')) {
    router.delete(route('dashboard.stores.photos.delete', { slug: props.store.slug, photo: photoId }), {
      preserveScroll: true
    })
  }
}

function handleVideoUpload(e) {
  const file = e.target.files[0]
  if (!file) return

  if (!file.type.startsWith('video/')) {
    alert('Apenas vídeos são permitidos')
    return
  }
  if (file.size > 100 * 1024 * 1024) { // 100MB max
    alert('Vídeo muito grande. Máximo 100MB.')
    return
  }

  videoForm.video = file
  videoForm.video_url = null // Clear URL if uploading file

  const reader = new FileReader()
  reader.onload = (e) => {
    videoPreview.value = e.target.result
  }
  reader.readAsDataURL(file)
}

function removeVideo() {
  videoForm.video = null
  videoForm.video_url = null
  videoPreview.value = null
  if (videoInput.value) {
    videoInput.value.value = ''
  }
}

function extractVideoId(url) {
  if (!url) return null

  // YouTube regex
  const youtubeRegex = /(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/
  const youtubeMatch = url.match(youtubeRegex)
  if (youtubeMatch && youtubeMatch[1]) {
    return { platform: 'youtube', id: youtubeMatch[1] }
  }

  // Vimeo regex
  const vimeoRegex = /vimeo\.com\/(?:.*\/)?(\d+)/
  const vimeoMatch = url.match(vimeoRegex)
  if (vimeoMatch && vimeoMatch[1]) {
    return { platform: 'vimeo', id: vimeoMatch[1] }
  }

  return null
}

const videoEmbedUrl = computed(() => {
  if (!videoForm.video_url) return null

  const videoData = extractVideoId(videoForm.video_url)
  if (videoData) {
    if (videoData.platform === 'youtube') {
      return `https://www.youtube.com/embed/${videoData.id}`
    } else if (videoData.platform === 'vimeo') {
      return `https://player.vimeo.com/video/${videoData.id}`
    }
  }

  return null
})

function submitVideo() {
  videoForm.transform((data) => ({
    ...data,
    _method: 'PUT'
  })).post(route('dashboard.stores.video.update', props.store.slug), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Vídeo atualizado com sucesso!')
    },
    onError: () => {
      toast.error('Erro ao atualizar vídeo!')
    },
  })
}
</script>

<template>
  <Head :title="`Fotos - ${store.name}`" />

  <AppLayout :title="`Fotos - ${store.name}`">
    <div class="min-h-screen bg-gray-50 p-6">
      <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
          <div class="flex items-center gap-2 text-sm text-muted-foreground mb-2">
            <Link :href="route('dashboard')" class="hover:text-teal-600 transition-colors">
              Dashboard
            </Link>
            <Icon icon="lucide:chevron-right" class="h-4 w-4" />
            <span class="text-gray-900 font-medium">Galeria</span>
          </div>
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-2xl font-bold text-gray-900">Galeria de Fotos e Vídeo</h1>
              <p class="text-muted-foreground mt-1">{{ store.name }}</p>
            </div>
            <Button :as="Link" :href="route('dashboard')" variant="outline">
              <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
              Voltar
            </Button>
          </div>
        </div>

        <!-- Upload Section -->
        <Card class="mb-6">
          <CardHeader>
            <CardTitle>Upload de Fotos</CardTitle>
            <CardDescription>
              Você está usando {{ store.photos_count }} de {{ store.max_photos }} fotos do seu plano
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div v-if="store.can_upload_more" class="space-y-4">
              <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-teal-500 transition-colors">
                <input
                  ref="fileInput"
                  type="file"
                  accept="image/*"
                  class="hidden"
                  @change="handleFileSelect"
                />
                <Button
                  type="button"
                  variant="outline"
                  @click="fileInput?.click()"
                  class="mb-4"
                  :disabled="uploadForm.processing"
                >
                  <Icon v-if="!uploadForm.processing" icon="lucide:upload" class="mr-2 h-5 w-5" />
                  <Icon v-else icon="lucide:loader-2" class="mr-2 h-5 w-5 animate-spin" />
                  {{ uploadForm.processing ? 'Enviando...' : 'Adicionar Foto' }}
                </Button>
                <p class="text-sm text-muted-foreground">
                  PNG, JPG, JPEG ou WEBP até 5MB
                </p>
              </div>
            </div>

            <div v-else class="text-center py-4">
              <Badge variant="secondary">Limite de fotos atingido</Badge>
              <p class="text-sm text-muted-foreground mt-2">
                Faça upgrade do seu plano para adicionar mais fotos
              </p>
              <Button :as="Link" :href="route('subscriptions.index')" variant="outline" class="mt-4">
                <Icon icon="lucide:sparkles" class="mr-2 h-4 w-4" />
                Ver Planos
              </Button>
            </div>
          </CardContent>
        </Card>

        <!-- Photos Grid -->
        <Card>
          <CardHeader>
            <CardTitle>Suas Fotos ({{ store.photos_count }})</CardTitle>
          </CardHeader>
          <CardContent>
            <div v-if="store.photos.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
              <div
                v-for="photo in store.photos"
                :key="photo.id"
                class="relative group aspect-square rounded-lg overflow-hidden bg-gray-100"
              >
                <img
                  :src="photo.url"
                  :alt="photo.name"
                  class="w-full h-full object-cover"
                />
                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                  <Button
                    type="button"
                    variant="destructive"
                    size="sm"
                    @click="deletePhoto(photo.id)"
                  >
                    <Icon icon="lucide:trash-2" class="h-4 w-4" />
                  </Button>
                </div>
              </div>
            </div>

            <div v-else class="text-center py-12">
              <Icon icon="lucide:image-off" class="h-16 w-16 mx-auto text-gray-400 mb-4" />
              <p class="text-gray-600 mb-4">Nenhuma foto adicionada ainda</p>
              <Button
                v-if="store.can_upload_more"
                type="button"
                @click="fileInput?.click()"
                class="bg-teal-600 hover:bg-teal-700"
              >
                <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                Adicionar Primeira Foto
              </Button>
            </div>
          </CardContent>
        </Card>

        <!-- Vídeo -->
        <Card class="mt-6">
          <CardHeader>
            <CardTitle>Vídeo do Fornecedor</CardTitle>
            <CardDescription>Adicione um vídeo para mostrar seus produtos (opcional)</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <Tabs v-model="videoMode" class="w-full">
              <TabsList class="grid w-full grid-cols-2">
                <TabsTrigger value="url">Link do Vídeo</TabsTrigger>
                <TabsTrigger value="upload">Upload de Arquivo</TabsTrigger>
              </TabsList>

              <!-- URL Mode -->
              <TabsContent value="url" class="space-y-4">
                <div>
                  <Label for="video_url">URL do Vídeo (YouTube ou Vimeo)</Label>
                  <Input
                    id="video_url"
                    v-model="videoForm.video_url"
                    placeholder="https://www.youtube.com/watch?v=..."
                    @input="videoForm.video = null"
                  />
                  <p class="text-xs text-muted-foreground mt-2">
                    Cole o link do vídeo do YouTube ou Vimeo
                  </p>
                </div>

                <!-- Preview do vídeo por URL -->
                <div v-if="videoEmbedUrl" class="mt-4">
                  <Label class="mb-2 block">Pré-visualização</Label>
                  <div class="aspect-video rounded-lg overflow-hidden bg-black">
                    <iframe
                      :src="videoEmbedUrl"
                      class="w-full h-full"
                      frameborder="0"
                      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                      allowfullscreen
                    />
                  </div>
                  <div class="flex gap-2 mt-3">
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      @click="removeVideo"
                    >
                      <Icon icon="lucide:trash-2" class="mr-2 h-4 w-4" />
                      Remover Vídeo
                    </Button>
                    <Button
                      type="button"
                      size="sm"
                      class="bg-teal-600 hover:bg-teal-700"
                      @click="submitVideo"
                      :disabled="videoForm.processing"
                    >
                      <Icon v-if="videoForm.processing" icon="lucide:loader-2" class="mr-2 h-4 w-4 animate-spin" />
                      Salvar Vídeo
                    </Button>
                  </div>
                </div>

                <Button
                  v-else-if="videoForm.video_url"
                  type="button"
                  size="sm"
                  class="bg-teal-600 hover:bg-teal-700"
                  @click="submitVideo"
                  :disabled="videoForm.processing"
                >
                  <Icon v-if="videoForm.processing" icon="lucide:loader-2" class="mr-2 h-4 w-4 animate-spin" />
                  Salvar Vídeo
                </Button>
              </TabsContent>

              <!-- Upload Mode -->
              <TabsContent value="upload" class="space-y-4">
                <div>
                  <Label for="video_upload">Upload de Vídeo</Label>
                  <div class="mt-2">
                    <input
                      ref="videoInput"
                      type="file"
                      id="video_upload"
                      accept="video/mp4,video/mov,video/webm,video/avi"
                      class="hidden"
                      @change="handleVideoUpload"
                    />
                    <Button
                      type="button"
                      variant="outline"
                      class="w-full"
                      @click="videoInput?.click()"
                    >
                      <Icon icon="lucide:upload" class="mr-2 h-4 w-4" />
                      Escolher Arquivo de Vídeo
                    </Button>
                  </div>
                  <p class="text-xs text-muted-foreground mt-2">
                    Formatos aceitos: MP4, MOV, WEBM, AVI (máx. 100MB)
                  </p>
                </div>

                <!-- Preview do vídeo por upload -->
                <div v-if="videoForm.video || videoPreview" class="mt-4">
                  <Label class="mb-2 block">Pré-visualização</Label>
                  <div class="aspect-video rounded-lg overflow-hidden bg-black">
                    <video
                      v-if="videoPreview"
                      :src="videoPreview"
                      class="w-full h-full"
                      controls
                      preload="metadata"
                    >
                      Seu navegador não suporta a reprodução de vídeos.
                    </video>
                  </div>
                  <div class="flex gap-2 mt-3">
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      @click="removeVideo"
                    >
                      <Icon icon="lucide:trash-2" class="mr-2 h-4 w-4" />
                      Remover Vídeo
                    </Button>
                    <Button
                      type="button"
                      size="sm"
                      class="bg-teal-600 hover:bg-teal-700"
                      @click="submitVideo"
                      :disabled="videoForm.processing"
                    >
                      <Icon v-if="videoForm.processing" icon="lucide:loader-2" class="mr-2 h-4 w-4 animate-spin" />
                      Salvar Vídeo
                    </Button>
                  </div>
                </div>
              </TabsContent>
            </Tabs>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
