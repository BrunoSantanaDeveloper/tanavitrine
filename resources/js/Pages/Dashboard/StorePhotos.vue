<script setup>
import { computed, ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { Button } from '@/Components/shadcn/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/shadcn/ui/card'
import { Icon } from '@iconify/vue'
import { Badge } from '@/Components/shadcn/ui/badge'
import { Input } from '@/Components/shadcn/ui/input'
import { Label } from '@/Components/shadcn/ui/label'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/shadcn/ui/tabs'
import Textarea from '@/Components/shadcn/ui/textarea/Textarea.vue'
import { toast } from 'vue-sonner'

const props = defineProps({
  store: {
    type: Object,
    required: true,
  },
})

const featuredFileInput = ref(null)
const collectionPhotoInput = ref(null)
const uploadingCollectionId = ref(null)

const uploadFeaturedForm = useForm({
  photos: [],
})

const createCollectionForm = useForm({
  name: '',
  description: '',
})

const editingCollectionId = ref(null)
const editCollectionForm = useForm({
  name: '',
  description: '',
})

const videoMode = ref('url') // 'url' or 'upload'
const videoPreview = ref(props.store.video_url || null)
const videoInput = ref(null)

const videoForm = useForm({
  video_url: props.store.video_url || null,
  video: null,
})

const hasCollections = computed(() => (props.store.collections?.length || 0) > 0)

function handleFeaturedFileSelect(event) {
  const files = Array.from(event.target.files || [])
  if (!files.length)
    return

  uploadFeaturedForm.photos = files
  uploadFeaturedPhotos()
}

function uploadFeaturedPhotos() {
  if (!uploadFeaturedForm.photos.length)
    return

  uploadFeaturedForm.post(route('dashboard.stores.photos.upload', props.store.slug), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      uploadFeaturedForm.reset()
      if (featuredFileInput.value)
        featuredFileInput.value.value = ''
    },
  })
}

function deleteFeaturedPhoto(photoId) {
  if (!confirm('Tem certeza que deseja excluir esta foto de destaque?'))
    return

  router.delete(route('dashboard.stores.photos.delete', { slug: props.store.slug, photo: photoId }), {
    preserveScroll: true,
  })
}

function setPrimaryFeaturedPhoto(photoId) {
  router.put(route('dashboard.stores.photos.primary', {
    slug: props.store.slug,
    photo: photoId,
  }), {}, {
    preserveScroll: true,
    onSuccess: () => toast.success('Foto principal atualizada!'),
    onError: () => toast.error('Erro ao definir foto principal!'),
  })
}

function createCollection() {
  createCollectionForm.post(route('dashboard.stores.collections.create', props.store.slug), {
    preserveScroll: true,
    onSuccess: () => {
      createCollectionForm.reset()
      toast.success('Coleção criada com sucesso!')
    },
    onError: () => {
      toast.error('Erro ao criar coleção!')
    },
  })
}

function startEditCollection(collection) {
  editingCollectionId.value = collection.id
  editCollectionForm.name = collection.name || ''
  editCollectionForm.description = collection.description || ''
}

function cancelEditCollection() {
  editingCollectionId.value = null
  editCollectionForm.reset()
}

function saveCollection(collectionId) {
  editCollectionForm.transform((data) => ({
    ...data,
    _method: 'PUT',
  })).post(route('dashboard.stores.collections.update', {
    slug: props.store.slug,
    collection: collectionId,
  }), {
    preserveScroll: true,
    onSuccess: () => {
      editingCollectionId.value = null
      toast.success('Coleção atualizada com sucesso!')
    },
    onError: () => {
      toast.error('Erro ao atualizar coleção!')
    },
  })
}

function deleteCollection(collectionId) {
  if (!confirm('Tem certeza que deseja excluir esta coleção e todas as fotos dela?'))
    return

  router.delete(route('dashboard.stores.collections.delete', {
    slug: props.store.slug,
    collection: collectionId,
  }), {
    preserveScroll: true,
    onSuccess: () => toast.success('Coleção removida com sucesso!'),
    onError: () => toast.error('Erro ao remover coleção!'),
  })
}

function setFeaturedCollection(collectionId) {
  router.post(route('dashboard.stores.collections.featured', {
    slug: props.store.slug,
    collection: collectionId,
  }), { _method: 'PUT' }, {
    preserveScroll: true,
    onSuccess: () => toast.success('Coleção em destaque atualizada!'),
    onError: () => toast.error('Erro ao atualizar coleção em destaque!'),
  })
}

function triggerCollectionPhotoUpload(collectionId) {
  uploadingCollectionId.value = collectionId
  collectionPhotoInput.value?.click()
}

function handleCollectionFileSelect(event) {
  const files = Array.from(event.target.files || [])
  const collectionId = uploadingCollectionId.value

  if (!files.length || !collectionId)
    return

  router.post(route('dashboard.stores.collections.photos.upload', {
    slug: props.store.slug,
    collection: collectionId,
  }), {
    photos: files,
  }, {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      toast.success(files.length > 1 ? 'Fotos adicionadas na coleção!' : 'Foto adicionada na coleção!')
      if (collectionPhotoInput.value)
        collectionPhotoInput.value.value = ''
      uploadingCollectionId.value = null
    },
    onError: () => {
      toast.error('Erro ao adicionar foto na coleção!')
    },
  })
}

function deleteCollectionPhoto(collectionId, photoId) {
  if (!confirm('Tem certeza que deseja excluir esta foto da coleção?'))
    return

  router.delete(route('dashboard.stores.collections.photos.delete', {
    slug: props.store.slug,
    collection: collectionId,
    photo: photoId,
  }), {
    preserveScroll: true,
    onSuccess: () => toast.success('Foto removida da coleção!'),
    onError: () => toast.error('Erro ao remover foto da coleção!'),
  })
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

function deleteSavedVideo() {
  if (!confirm('Tem certeza que deseja excluir o vídeo salvo da loja?'))
    return

  router.delete(route('dashboard.stores.video.delete', props.store.slug), {
    preserveScroll: true,
    onSuccess: () => {
      removeVideo()
      toast.success('Vídeo excluído com sucesso!')
    },
    onError: () => {
      toast.error('Erro ao excluir vídeo!')
    },
  })
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
    _method: 'PUT',
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
  <Head :title="`Galeria - ${store.name}`" />

  <AppLayout :title="`Galeria - ${store.name}`">
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
              <h1 class="text-2xl font-bold text-gray-900">Galeria da Loja</h1>
              <p class="text-muted-foreground mt-1">{{ store.name }}</p>
            </div>
            <Button :as="Link" :href="route('dashboard')" variant="outline">
              <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
              Voltar
            </Button>
          </div>
        </div>

        <!-- Fotos em Destaque -->
        <Card class="mb-6">
          <CardHeader>
            <CardTitle>Fotos em Destaque</CardTitle>
            <CardDescription>
              Gerencie as fotos de destaque da sua vitrine.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-teal-500 transition-colors">
                <input
                  ref="featuredFileInput"
                  type="file"
                  accept="image/*"
                  multiple
                  class="hidden"
                  @change="handleFeaturedFileSelect"
                >
                <Button
                  type="button"
                  variant="outline"
                  @click="featuredFileInput?.click()"
                  class="mb-4"
                  :disabled="uploadFeaturedForm.processing"
                >
                  <Icon v-if="!uploadFeaturedForm.processing" icon="lucide:upload" class="mr-2 h-5 w-5" />
                  <Icon v-else icon="lucide:loader-2" class="mr-2 h-5 w-5 animate-spin" />
                  {{ uploadFeaturedForm.processing ? 'Enviando...' : 'Adicionar Fotos em Destaque' }}
                </Button>
                <p class="text-sm text-muted-foreground">
                  PNG, JPG, JPEG ou WEBP até 5MB por arquivo. Você pode selecionar várias fotos.
                </p>
              </div>
            </div>

            <div v-if="store.featured_photos.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mt-6">
              <div
                v-for="photo in store.featured_photos"
                :key="photo.id"
                class="relative group aspect-square rounded-lg overflow-hidden bg-gray-100"
              >
                <img
                  :src="photo.url"
                  :alt="photo.name"
                  class="w-full h-full object-cover"
                >
                <Badge
                  v-if="photo.is_primary"
                  class="absolute top-2 left-2 bg-yellow-500 text-white border-0 z-10"
                >
                  Principal
                </Badge>
                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                  <div class="flex items-center gap-2">
                    <Button
                      v-if="!photo.is_primary"
                      type="button"
                      variant="outline"
                      size="sm"
                      class="bg-white/90"
                      @click="setPrimaryFeaturedPhoto(photo.id)"
                    >
                      <Icon icon="lucide:star" class="h-4 w-4 mr-1" />
                      Principal
                    </Button>
                    <Button
                      type="button"
                      variant="destructive"
                      size="sm"
                      @click="deleteFeaturedPhoto(photo.id)"
                    >
                      <Icon icon="lucide:trash-2" class="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              </div>
            </div>

            <div v-else class="text-center py-8 text-muted-foreground">
              Nenhuma foto de destaque adicionada ainda.
            </div>
          </CardContent>
        </Card>

        <!-- Coleções -->
        <Card class="mb-6">
          <CardHeader>
            <CardTitle>Coleções</CardTitle>
            <CardDescription>
              Crie coleções para organizar sua galeria e marque uma coleção em destaque.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div class="border rounded-lg p-4 mb-6 bg-white">
              <h3 class="font-semibold text-gray-900 mb-4">Nova Coleção</h3>
              <div class="grid gap-4 md:grid-cols-2">
                <div>
                  <Label for="collection-name">Nome da coleção</Label>
                  <Input
                    id="collection-name"
                    v-model="createCollectionForm.name"
                    placeholder="Ex: Vestidos"
                  />
                </div>
                <div>
                  <Label for="collection-description">Descrição (opcional)</Label>
                  <Textarea
                    id="collection-description"
                    v-model="createCollectionForm.description"
                    rows="2"
                    placeholder="Descreva rapidamente esta coleção"
                  />
                </div>
              </div>
              <div class="mt-4 flex justify-end">
                <Button
                  type="button"
                  class="bg-teal-600 hover:bg-teal-700"
                  :disabled="createCollectionForm.processing || !createCollectionForm.name"
                  @click="createCollection"
                >
                  <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                  Criar Coleção
                </Button>
              </div>
            </div>

            <div v-if="hasCollections" class="space-y-6">
              <div
                v-for="collection in store.collections"
                :key="collection.id"
                class="border rounded-lg p-4 bg-white"
              >
                <div class="flex items-start justify-between gap-4 mb-4">
                  <div>
                    <div class="flex items-center gap-2 mb-1">
                      <h3 class="text-lg font-semibold text-gray-900">{{ collection.name }}</h3>
                      <Badge v-if="collection.is_featured" class="bg-teal-600 text-white">
                        Coleção em Destaque
                      </Badge>
                    </div>
                    <p class="text-sm text-muted-foreground">
                      {{ collection.description || 'Sem descrição' }}
                    </p>
                    <p class="text-xs text-muted-foreground mt-1">
                      {{ collection.photos_count }} foto(s)
                    </p>
                  </div>

                  <div class="flex flex-wrap gap-2 justify-end">
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      @click="startEditCollection(collection)"
                    >
                      <Icon icon="lucide:pencil" class="mr-2 h-4 w-4" />
                      Editar
                    </Button>
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      @click="setFeaturedCollection(collection.id)"
                    >
                      <Icon icon="lucide:star" class="mr-2 h-4 w-4" />
                      Destacar
                    </Button>
                    <Button
                      type="button"
                      variant="destructive"
                      size="sm"
                      @click="deleteCollection(collection.id)"
                    >
                      <Icon icon="lucide:trash-2" class="mr-2 h-4 w-4" />
                      Excluir
                    </Button>
                  </div>
                </div>

                <div
                  v-if="editingCollectionId === collection.id"
                  class="border rounded-md p-3 mb-4 bg-gray-50"
                >
                  <div class="grid gap-3 md:grid-cols-2">
                    <div>
                      <Label>Nome da coleção</Label>
                      <Input v-model="editCollectionForm.name" />
                    </div>
                    <div>
                      <Label>Descrição</Label>
                      <Textarea v-model="editCollectionForm.description" rows="2" />
                    </div>
                  </div>
                  <div class="mt-3 flex justify-end gap-2">
                    <Button type="button" variant="outline" size="sm" @click="cancelEditCollection">
                      Cancelar
                    </Button>
                    <Button
                      type="button"
                      size="sm"
                      class="bg-teal-600 hover:bg-teal-700"
                      :disabled="editCollectionForm.processing || !editCollectionForm.name"
                      @click="saveCollection(collection.id)"
                    >
                      Salvar
                    </Button>
                  </div>
                </div>

                <div class="flex items-center justify-between mb-3">
                  <p class="text-sm font-medium text-gray-700">Fotos da coleção</p>
                  <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    @click="triggerCollectionPhotoUpload(collection.id)"
                  >
                    <Icon icon="lucide:image-plus" class="mr-2 h-4 w-4" />
                    Adicionar Foto
                  </Button>
                </div>

                <div v-if="collection.photos.length > 0" class="grid grid-cols-2 md:grid-cols-4 gap-3">
                  <div
                    v-for="photo in collection.photos"
                    :key="photo.id"
                    class="relative group aspect-square rounded-lg overflow-hidden bg-gray-100"
                  >
                    <img :src="photo.url" :alt="photo.name" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                      <Button
                        type="button"
                        variant="destructive"
                        size="sm"
                        @click="deleteCollectionPhoto(collection.id, photo.id)"
                      >
                        <Icon icon="lucide:trash-2" class="h-4 w-4" />
                      </Button>
                    </div>
                  </div>
                </div>
                <p v-else class="text-sm text-muted-foreground">Esta coleção ainda não tem fotos.</p>
              </div>
            </div>

            <div v-else class="text-center py-8 text-muted-foreground">
              Nenhuma coleção criada ainda.
            </div>

            <input
              ref="collectionPhotoInput"
              type="file"
              accept="image/*"
              multiple
              class="hidden"
              @change="handleCollectionFileSelect"
            >
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
                    >
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

            <div v-if="store.video_url" class="pt-2">
              <Button
                type="button"
                variant="destructive"
                size="sm"
                @click="deleteSavedVideo"
              >
                <Icon icon="lucide:trash-2" class="mr-2 h-4 w-4" />
                Excluir vídeo salvo
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
