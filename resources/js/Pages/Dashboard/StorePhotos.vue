<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { Button } from '@/Components/shadcn/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/shadcn/ui/card'
import { Icon } from '@iconify/vue'
import { Badge } from '@/Components/shadcn/ui/badge'

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
</script>

<template>
  <Head :title="`Fotos - ${store.name}`" />

  <AppLayout :title="`Fotos - ${store.name}`">
    <div class="min-h-screen bg-gray-50 p-6">
      <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Gerenciar Fotos</h1>
            <p class="text-muted-foreground">{{ store.name }}</p>
          </div>
          <Button :as="Link" :href="route('dashboard')" variant="outline">
            <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
            Voltar
          </Button>
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
      </div>
    </div>
  </AppLayout>
</template>
