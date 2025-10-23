<script setup>
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
import Input from '@/Components/shadcn/ui/input/Input.vue'
import Label from '@/Components/shadcn/ui/label/Label.vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'

const form = defineModel()
const emit = defineEmits(['next', 'prev'])

const saleTypes = [
  { value: 'atacado', label: 'Atacado', icon: 'lucide:shopping-cart', description: 'Vendo produtos no atacado para lojistas' },
  { value: 'varejo', label: 'Varejo', icon: 'lucide:store', description: 'Vendo produtos direto para o consumidor final' },
  { value: 'ambos', label: 'Ambos', icon: 'lucide:package', description: 'Trabalho tanto com atacado quanto varejo' },
]

const isValid = computed(() => {
  return form.value.store_name && form.value.store_name.trim().length >= 3 && form.value.sale_type
})
</script>

<template>
  <div class="space-y-8">
    <div class="text-center mb-8">
      <div class="w-16 h-16 bg-teal-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <Icon icon="lucide:store" class="h-8 w-8 text-teal-600" />
      </div>
      <h2 class="text-2xl font-bold mb-2">Vamos criar sua vitrine!</h2>
      <p class="text-muted-foreground">
        Primeiro, conte-nos sobre sua loja 🏪
      </p>
    </div>

    <div>
      <Label for="store-name" class="text-lg">Nome da Loja ou Marca *</Label>
      <Input
        id="store-name"
        v-model="form.store_name"
        type="text"
        placeholder="Ex: Moda Bella Atacado"
        class="mt-2 text-lg h-12"
        autofocus
      />
      <p class="text-xs text-muted-foreground mt-2">
        Este será o nome da sua vitrine no TanaVitrine
      </p>
    </div>

    <div>
      <Label class="text-lg mb-4 block">Tipo de Negócio *</Label>
      <div class="space-y-3">
        <label
          v-for="type in saleTypes"
          :key="type.value"
          class="flex items-start gap-4 p-4 border-2 rounded-lg cursor-pointer transition-all hover:border-teal-300 hover:bg-teal-50"
          :class="{
            'border-teal-600 bg-teal-50': form.sale_type === type.value,
            'border-gray-200': form.sale_type !== type.value
          }"
        >
          <input
            v-model="form.sale_type"
            type="radio"
            :value="type.value"
            class="mt-1"
          />
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-1">
              <Icon :icon="type.icon" class="h-5 w-5 text-teal-600" />
              <span class="font-semibold text-base">{{ type.label }}</span>
            </div>
            <p class="text-sm text-muted-foreground">{{ type.description }}</p>
          </div>
        </label>
      </div>
    </div>

    <div class="flex justify-end pt-4">
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
