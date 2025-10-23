<script setup>
import { Icon } from '@iconify/vue'
import Input from '@/Components/shadcn/ui/input/Input.vue'
import Label from '@/Components/shadcn/ui/label/Label.vue'

const socialMedia = defineModel({
  default: () => ({
    instagram: '',
    facebook: '',
    website: '',
  })
})

function formatInstagram(value) {
  // Remove @ se existir e adiciona automaticamente
  const cleaned = value.replace(/^@/, '')
  return cleaned ? `@${cleaned}` : ''
}

function formatFacebook(value) {
  // Remove facebook.com/ se existir, mantém apenas o username
  return value.replace(/^(https?:\/\/)?(www\.)?facebook\.com\//, '')
}

function handleInstagramInput(e) {
  socialMedia.value.instagram = formatInstagram(e.target.value)
}

function handleFacebookInput(e) {
  socialMedia.value.facebook = formatFacebook(e.target.value)
}
</script>

<template>
  <div class="space-y-4">
    <div>
      <Label for="instagram" class="flex items-center gap-2">
        <Icon icon="mdi:instagram" class="h-5 w-5 text-pink-600" />
        Instagram
      </Label>
      <div class="relative mt-1">
        <Input
          id="instagram"
          v-model="socialMedia.instagram"
          placeholder="@suaclinica"
          @input="handleInstagramInput"
        />
      </div>
      <p class="text-xs text-muted-foreground mt-1">
        Digite apenas o usuário (ex: @clinicavet)
      </p>
    </div>

    <div>
      <Label for="facebook" class="flex items-center gap-2">
        <Icon icon="mdi:facebook" class="h-5 w-5 text-blue-600" />
        Facebook
      </Label>
      <div class="relative mt-1">
        <Input
          id="facebook"
          v-model="socialMedia.facebook"
          placeholder="suaclinica"
          @input="handleFacebookInput"
        />
      </div>
      <p class="text-xs text-muted-foreground mt-1">
        Digite apenas o nome da página (ex: clinicavet)
      </p>
    </div>

    <div>
      <Label for="website" class="flex items-center gap-2">
        <Icon icon="lucide:globe" class="h-5 w-5 text-primary" />
        Website
      </Label>
      <Input
        id="website"
        v-model="socialMedia.website"
        type="url"
        placeholder="https://suaclinica.com.br"
        class="mt-1"
      />
      <p class="text-xs text-muted-foreground mt-1">
        URL completa do seu site (opcional)
      </p>
    </div>
  </div>
</template>
