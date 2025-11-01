<script setup>
import { computed, watch } from 'vue'
import { Icon } from '@iconify/vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import Input from '@/Components/shadcn/ui/input/Input.vue'
import Label from '@/Components/shadcn/ui/label/Label.vue'
import { MultiSelect } from '@/Components/shadcn/ui/multi-select'
import Select from '@/Components/shadcn/ui/select/Select.vue'
import SelectContent from '@/Components/shadcn/ui/select/SelectContent.vue'
import SelectItem from '@/Components/shadcn/ui/select/SelectItem.vue'
import SelectTrigger from '@/Components/shadcn/ui/select/SelectTrigger.vue'
import SelectValue from '@/Components/shadcn/ui/select/SelectValue.vue'
import Textarea from '@/Components/shadcn/ui/textarea/Textarea.vue'

const props = defineProps({
  categories: {
    type: Array,
    default: () => [],
  },
})

const form = defineModel()
const emit = defineEmits(['next', 'prev'])

const genders = [
  { value: 'masculino', label: 'Masculino' },
  { value: 'feminino', label: 'Feminino' },
  { value: 'unissex', label: 'Unissex' },
  { value: 'infantil', label: 'Infantil' },
]

const subcategories = computed(() => {
  const categoryId = Number.parseInt(form.value.category_id)
  const category = props.categories.find(c => c.id === categoryId)
  return category?.children || []
})

// Converte subcategories para o formato do MultiSelect
const subcategoryOptions = computed(() => {
  return subcategories.value.map(sub => ({
    value: sub.name,
    label: sub.name,
  }))
})

// Inicializa subcategories como array se não existir
if (!Array.isArray(form.value.subcategory)) {
  form.value.subcategory = form.value.subcategory ? [form.value.subcategory] : []
}

// Limpa subcategorias quando a categoria principal mudar
watch(() => form.value.category_id, (newCategoryId, oldCategoryId) => {
  // Só limpa se realmente mudou de categoria (não na primeira carga)
  if (oldCategoryId !== undefined && newCategoryId !== oldCategoryId) {
    form.value.subcategory = []
  }
})

const isValid = computed(() => {
  return (
    form.value.category_id
    && Array.isArray(form.value.subcategory)
    && form.value.subcategory.length > 0
    && form.value.description
    && form.value.description.trim().length >= 50
  )
})
</script>

<template>
  <div class="space-y-6">
    <div class="text-center mb-8">
      <div class="w-16 h-16 bg-teal-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <Icon icon="lucide:tag" class="h-8 w-8 text-teal-600" />
      </div>
      <h2 class="text-2xl font-bold mb-2">Categorias e Produtos</h2>
      <p class="text-muted-foreground">
        Ajude seus clientes a encontrar seus produtos
      </p>
    </div>

    <div class="space-y-5">
      <div>
        <Label for="category" class="text-base">Categoria Principal *</Label>
        <Select v-model="form.category_id">
          <SelectTrigger class="mt-2 h-12">
            <SelectValue placeholder="Selecione a categoria" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="category in categories" :key="category.id" :value="String(category.id)">
              {{ category.name }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <div v-if="subcategories.length > 0">
        <Label for="subcategory" class="text-base">Subcategorias *</Label>
        <MultiSelect
          id="subcategory"
          v-model="form.subcategory"
          :options="subcategoryOptions"
          placeholder="Selecione uma ou mais subcategorias..."
          class="mt-2"
        />
        <p class="text-xs text-muted-foreground mt-2">
          Selecione pelo menos uma subcategoria que representa seus produtos
        </p>
      </div>

      <div>
        <Label for="gender" class="text-base">Público-alvo</Label>
        <Select v-model="form.gender">
          <SelectTrigger class="mt-2 h-12">
            <SelectValue placeholder="Selecione o público (opcional)" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="gender in genders" :key="gender.value" :value="gender.value">
              {{ gender.label }}
            </SelectItem>
          </SelectContent>
        </Select>
        <p class="text-xs text-muted-foreground mt-1">
          Para quem são seus produtos?
        </p>
      </div>

      <div>
        <Label for="description" class="text-base">Descrição da Loja *</Label>
        <Textarea
          id="description"
          v-model="form.description"
          placeholder="Descreva seus produtos, diferenciais, formas de pagamento, prazos de entrega... Capriche! Esta descrição aparecerá na sua vitrine."
          rows="5"
          class="mt-2"
        />
        <p class="text-xs text-muted-foreground mt-1">
          {{ form.description?.length || 0 }} / 50 caracteres (mínimo)
        </p>
      </div>

      <div v-if="form.sale_type === 'atacado' || form.sale_type === 'ambos'">
        <Label for="min_order" class="text-base">Pedido Mínimo</Label>
        <Input
          id="min_order"
          v-model="form.min_order"
          type="text"
          placeholder="Ex: 50 peças, R$ 500,00"
          class="mt-2 h-12"
        />
        <p class="text-xs text-muted-foreground mt-1">
          Informe o pedido mínimo para atacado (opcional)
        </p>
      </div>
    </div>

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
