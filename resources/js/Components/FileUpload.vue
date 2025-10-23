<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import Label from '@/Components/shadcn/ui/label/Label.vue'

const props = defineProps({
  minFiles: {
    type: Number,
    default: 5,
  },
  maxFiles: {
    type: Number,
    default: 20,
  },
  accept: {
    type: String,
    default: 'image/*',
  },
  label: {
    type: String,
    default: 'Fotos da Clínica',
  },
})

const files = defineModel({ default: [] })
const fileInput = ref(null)
const isDragging = ref(false)
const previews = ref([])

const isValid = computed(() => files.value.length >= props.minFiles)
const canAddMore = computed(() => files.value.length < props.maxFiles)

function handleDrop(e) {
  isDragging.value = false
  if (!canAddMore.value) return

  const droppedFiles = Array.from(e.dataTransfer.files)
  addFiles(droppedFiles)
}

function handleFileInput(e) {
  if (!canAddMore.value) return

  const selectedFiles = Array.from(e.target.files)
  addFiles(selectedFiles)

  // Reset input
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

function addFiles(newFiles) {
  const remainingSlots = props.maxFiles - files.value.length
  const filesToAdd = newFiles.slice(0, remainingSlots)
  console.log(filesToAdd)

  filesToAdd.forEach(file => {
    // Validar se é imagem
    if (file.type.startsWith('image/')) {
      // Validar tamanho (máx 5MB)
      if (file.size <= 5 * 1024 * 1024) {
        files.value.push(file)

        const reader = new FileReader()
        reader.onload = (e) => {
          previews.value.push(e.target.result)
        }
        reader.readAsDataURL(file)
      } else {
        console.warn(`Arquivo ${file.name} excede 5MB`)
      }
    }
  })
}

function removeFile(index) {
  files.value.splice(index, 1)
  previews.value.splice(index, 1)
}

function triggerFileInput() {
  if (canAddMore.value && fileInput.value) {
    fileInput.value.click()
  }
}

// Gerar previews para arquivos já existentes (restaurados do localStorage)
function generatePreviews() {
  previews.value = []
  files.value.forEach(file => {
    if (file instanceof File) {
      const reader = new FileReader()
      console.log(previews.value)
      reader.onload = (e) => {
        previews.value.push(e.target.result)
      }
      reader.readAsDataURL(file)
    }
  })
}

// Restaurar previews quando o componente montar
onMounted(() => {
    console.log('onMounted')
  if (files.value.length > 0 && previews.value.length === 0) {
    generatePreviews()
  }
})

// Observar mudanças no TAMANHO do array (não deep watch)

</script>

<template>
  <div>
    <Label class="mb-2">{{ label }} (mínimo {{ minFiles }})</Label>

    <!-- Drop zone -->
    <div
      class="border-2 border-dashed rounded-lg p-8 text-center transition-colors"
      :class="[
        isDragging ? 'border-primary bg-primary/5' : 'border-gray-300',
        !canAddMore ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
      ]"
      @drop.prevent="handleDrop"
      @dragover.prevent="isDragging = true"
      @dragleave="isDragging = false"
      @click="triggerFileInput"
    >
      <Icon icon="lucide:upload-cloud" class="h-12 w-12 mx-auto mb-4 text-muted-foreground" />
      <p class="mb-2">{{ canAddMore ? 'Arraste fotos aqui ou' : `Máximo de ${maxFiles} fotos atingido` }}</p>
      <Button
        v-if="canAddMore"
        variant="outline"
        type="button"
        @click.stop="triggerFileInput"
      >
        Selecionar Arquivos
      </Button>
      <input
        ref="fileInput"
        type="file"
        multiple
        :accept="accept"
        class="hidden"
        :disabled="!canAddMore"
        @change="handleFileInput"
      />
    </div>

    <!-- Preview grid -->
    <div v-if="previews.length > 0" class="grid grid-cols-3 gap-4 mt-4">
      <div
        v-for="(preview, index) in previews"
        :key="index"
        class="relative group"
      >
        <img :src="preview" class="w-full h-32 object-cover rounded-lg" :alt="`Preview ${index + 1}`" />
        <button
          type="button"
          @click="removeFile(index)"
          class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
        >
          <Icon icon="lucide:x" class="h-4 w-4" />
        </button>
      </div>
    </div>

    <!-- Status -->
    <div class="flex items-center justify-between mt-2">
      <p class="text-sm text-muted-foreground">
        {{ files.length }} de {{ minFiles }}+ fotos enviadas
      </p>
      <p v-if="!isValid" class="text-sm text-destructive">
        Adicione pelo menos {{ minFiles - files.length }} foto(s)
      </p>
      <p v-else class="text-sm text-green-600 flex items-center gap-1">
        <Icon icon="lucide:check-circle" class="h-4 w-4" />
        Requisito atendido
      </p>
    </div>
  </div>
</template>
