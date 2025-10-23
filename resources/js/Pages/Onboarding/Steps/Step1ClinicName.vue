<script setup>
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
import Input from '@/Components/shadcn/ui/input/Input.vue'
import Label from '@/Components/shadcn/ui/label/Label.vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'

const form = defineModel()
const emit = defineEmits(['next', 'prev'])

const isValid = computed(() => {
  return form.value.establishment_name && form.value.establishment_name.trim().length >= 3
})
</script>

<template>
  <div class="space-y-6">
    <div class="text-center mb-8">
      <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
        <Icon icon="lucide:store" class="h-8 w-8 text-primary" />
      </div>
      <h2 class="text-2xl font-bold mb-2">Qual o nome da sua clínica?</h2>
      <p class="text-muted-foreground">
        Vamos personalizar tudo especialmente para você! 🎉
      </p>
    </div>

    <div>
      <Label for="clinic-name" class="text-lg">Nome da Clínica ou Petshop *</Label>
      <Input
        id="clinic-name"
        v-model="form.establishment_name"
        type="text"
        placeholder="Ex: Clínica Veterinária Vida Animal"
        class="mt-2 text-lg h-12"
        autofocus
        @keyup.enter="isValid && emit('next')"
      />
      <p class="text-xs text-muted-foreground mt-2">
        Este nome aparecerá na sua TV e no painel de controle
      </p>
    </div>

    <div class="flex justify-end pt-4">
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
