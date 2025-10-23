<script setup>
import { Button } from '@/Components/shadcn/ui/button'
import { Card, CardContent } from '@/Components/shadcn/ui/card'
import Input from '@/Components/shadcn/ui/input/Input.vue'
import Label from '@/Components/shadcn/ui/label/Label.vue'
import Textarea from '@/Components/shadcn/ui/textarea/Textarea.vue'
import Select from '@/Components/shadcn/ui/select/Select.vue'
import SelectContent from '@/Components/shadcn/ui/select/SelectContent.vue'
import SelectItem from '@/Components/shadcn/ui/select/SelectItem.vue'
import SelectTrigger from '@/Components/shadcn/ui/select/SelectTrigger.vue'
import SelectValue from '@/Components/shadcn/ui/select/SelectValue.vue'
import { Progress } from '@/Components/shadcn/ui/progress'
import { Icon } from '@iconify/vue'
import { router, useForm } from '@inertiajs/vue3'
import { computed, inject, ref } from 'vue'

const route = inject('route')

const props = defineProps({
  categories: {
    type: Array,
    required: true
  }
})

const currentStep = ref(1)
const totalSteps = 5

const logoPreview = ref(null)
const photosPreview = ref([])

const form = useForm({
  // Step 1: Como conheceu
  referralSource: '',

  // Step 2: Tipo de negócio
  sale_type: '',

  // Step 3: Categoria, Logo e Fotos
  category_id: '',
  subcategory: '',
  gender: '',
  logo: null,
  photos: [],

  // Step 4: Informações da loja
  name: '',
  description: '',
  store_type: '',
  min_order: '',

  // Step 5: Localização e Contato
  city: '',
  state: '',
  whatsapp: '',
  email: '',
})

const referralSources = [
  'Google',
  'Instagram',
  'Facebook',
  'TikTok',
  'YouTube',
  'Indicação de amigo',
  'Evento/Feira',
  'Anúncio online',
  'Outro',
]

const saleTypes = [
  { value: 'atacado', label: 'Atacado', icon: 'lucide:shopping-cart' },
  { value: 'varejo', label: 'Varejo', icon: 'lucide:store' },
  { value: 'ambos', label: 'Atacado e Varejo', icon: 'lucide:package' },
]

const storeTypes = [
  { value: 'fisica', label: 'Loja Física' },
  { value: 'virtual', label: 'Loja Virtual' },
  { value: 'ambos', label: 'Física e Virtual' },
]

const genders = [
  { value: 'masculino', label: 'Masculino' },
  { value: 'feminino', label: 'Feminino' },
  { value: 'unissex', label: 'Unissex' },
]

const states = [
  'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
  'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
  'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'
]

const subcategories = computed(() => {
  const category = props.categories.find(c => c.id === form.category_id)
  return category?.children || []
})

function handleNext() {
  if (currentStep.value < totalSteps) {
    currentStep.value++
    window.scrollTo(0, 0)
  }
  else {
    form.post(route('onboarding.store'), {
      onSuccess: () => {
        router.visit('/dashboard')
      },
    })
  }
}

function handleBack() {
  if (currentStep.value > 1)
    currentStep.value--
}

function skipOnboarding() {
  router.visit('/dashboard')
}

const isStepValid = computed(() => {
  if (currentStep.value === 1)
    return !!form.referralSource
  if (currentStep.value === 2)
    return !!form.sale_type
  if (currentStep.value === 3)
    return !!form.category_id && !!form.subcategory && !!form.gender
  if (currentStep.value === 4) {
    return (
      form.name.trim() !== ''
      && form.description.trim() !== ''
      && !!form.store_type
    )
  }
  if (currentStep.value === 5) {
    return (
      form.city.trim() !== ''
      && !!form.state
      && (form.whatsapp.trim() !== '' || form.email.trim() !== '')
    )
  }
  return false
})
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-teal-50 to-orange-50 py-12 px-4">
    <div class="max-w-3xl mx-auto">
      <!-- Header -->
      <div class="text-center mb-8">
        <img src="/tanavitrine_light_icon.png" alt="TanaVitrine" class="h-20 mx-auto mb-4">
        <h1 class="text-3xl font-bold mb-2">
          Bem-vindo ao Tá na Vitrine!
        </h1>
        <p class="text-gray-600">
          Vamos criar sua primeira vitrine para começar a vender
        </p>
      </div>

      <!-- Progress -->
      <div class="mb-8">
        <Progress :value="(currentStep / totalSteps) * 100" class="h-2" />
        <p class="text-sm text-gray-500 mt-2 text-center">
          Passo {{ currentStep }} de {{ totalSteps }}
        </p>
      </div>

      <Card>
        <CardContent class="p-8">
          <form @submit.prevent="handleNext">
            <!-- Step 1: Como conheceu -->
            <div v-if="currentStep === 1">
              <div class="flex items-center gap-2 mb-4">
                <Icon icon="lucide:wave-hand" class="size-6 text-teal-600" />
                <h2 class="text-xl font-semibold">
                  Como você conheceu o Tá na Vitrine?
                </h2>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <label v-for="source in referralSources" :key="source" class="flex items-center gap-2 cursor-pointer">
                  <input v-model="form.referralSource" type="radio" :value="source" class="text-teal-600 focus:ring-teal-500">
                  {{ source }}
                </label>
              </div>
            </div>

            <!-- Step 2: Tipo de negócio -->
            <div v-if="currentStep === 2">
              <div class="flex items-center gap-2 mb-6">
                <Icon icon="lucide:store" class="size-6 text-teal-600" />
                <h2 class="text-xl font-semibold">
                  Qual o tipo de negócio da sua loja?
                </h2>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <button
                  v-for="type in saleTypes"
                  :key="type.value"
                  type="button"
                  @click="form.sale_type = type.value"
                  :class="[
                    'p-6 border-2 rounded-lg transition-all cursor-pointer',
                    form.sale_type === type.value
                      ? 'border-teal-600 bg-teal-50 shadow-md'
                      : 'border-gray-200 hover:border-teal-300'
                  ]"
                >
                  <Icon :icon="type.icon" class="size-12 mx-auto mb-3 text-teal-600" />
                  <p class="font-semibold">{{ type.label }}</p>
                </button>
              </div>
            </div>

            <!-- Step 3: Categoria -->
            <div v-if="currentStep === 3">
              <div class="flex items-center gap-2 mb-6">
                <Icon icon="lucide:tag" class="size-6 text-teal-600" />
                <h2 class="text-xl font-semibold">
                  Categoria e Público-alvo
                </h2>
              </div>
              <div class="space-y-4">
                <div>
                  <Label for="category">Categoria</Label>
                  <Select v-model="form.category_id">
                    <SelectTrigger id="category">
                      <SelectValue placeholder="Selecione a categoria" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="category in categories"
                        :key="category.id"
                        :value="category.id"
                      >
                        {{ category.name }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div v-if="subcategories.length > 0">
                  <Label for="subcategory">Subcategoria</Label>
                  <Select v-model="form.subcategory">
                    <SelectTrigger id="subcategory">
                      <SelectValue placeholder="Selecione a subcategoria" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="sub in subcategories"
                        :key="sub.id"
                        :value="sub.name"
                      >
                        {{ sub.name }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div>
                  <Label for="gender">Gênero</Label>
                  <Select v-model="form.gender">
                    <SelectTrigger id="gender">
                      <SelectValue placeholder="Selecione o gênero" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="gender in genders"
                        :key="gender.value"
                        :value="gender.value"
                      >
                        {{ gender.label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
            </div>

            <!-- Step 4: Informações da loja -->
            <div v-if="currentStep === 4">
              <div class="flex items-center gap-2 mb-6">
                <Icon icon="lucide:shopping-bag" class="size-6 text-teal-600" />
                <h2 class="text-xl font-semibold">
                  Informações da Loja
                </h2>
              </div>
              <div class="space-y-4">
                <div>
                  <Label for="name">Nome da Loja *</Label>
                  <Input
                    id="name"
                    v-model="form.name"
                    placeholder="Ex: Moda Bella Atacado"
                  />
                </div>

                <div>
                  <Label for="description">Descrição *</Label>
                  <Textarea
                    id="description"
                    v-model="form.description"
                    placeholder="Descreva sua loja, produtos e diferenciais..."
                    rows="4"
                  />
                </div>

                <div>
                  <Label for="store_type">Tipo de Loja *</Label>
                  <Select v-model="form.store_type">
                    <SelectTrigger id="store_type">
                      <SelectValue placeholder="Selecione o tipo" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="type in storeTypes"
                        :key="type.value"
                        :value="type.value"
                      >
                        {{ type.label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div v-if="form.sale_type === 'atacado' || form.sale_type === 'ambos'">
                  <Label for="min_order">Pedido Mínimo</Label>
                  <Input
                    id="min_order"
                    v-model="form.min_order"
                    placeholder="Ex: 50 peças"
                  />
                </div>
              </div>
            </div>

            <!-- Step 5: Localização e Contato -->
            <div v-if="currentStep === 5">
              <div class="flex items-center gap-2 mb-6">
                <Icon icon="lucide:map-pin" class="size-6 text-teal-600" />
                <h2 class="text-xl font-semibold">
                  Localização e Contato
                </h2>
              </div>
              <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <Label for="city">Cidade *</Label>
                    <Input
                      id="city"
                      v-model="form.city"
                      placeholder="Sua cidade"
                    />
                  </div>

                  <div>
                    <Label for="state">Estado *</Label>
                    <Select v-model="form.state">
                      <SelectTrigger id="state">
                        <SelectValue placeholder="UF" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem v-for="state in states" :key="state" :value="state">
                          {{ state }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>

                <div>
                  <Label for="whatsapp">WhatsApp *</Label>
                  <Input
                    id="whatsapp"
                    v-model="form.whatsapp"
                    placeholder="(00) 00000-0000"
                  />
                </div>

                <div>
                  <Label for="email">Email *</Label>
                  <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    placeholder="contato@loja.com.br"
                  />
                </div>
              </div>
            </div>

            <!-- Navigation -->
            <div class="flex justify-between items-center mt-8 pt-6 border-t">
              <Button v-if="currentStep > 1" type="button" variant="outline" @click="handleBack">
                Voltar
              </Button>
              <div v-else></div>

              <div class="flex gap-3">
                <Button
                  type="button"
                  variant="ghost"
                  @click="skipOnboarding"
                  class="text-gray-500 hover:text-gray-700"
                >
                  Pular por enquanto
                </Button>
                <Button
                  type="submit"
                  :disabled="!isStepValid"
                  class="bg-teal-600 hover:bg-teal-700 text-white"
                >
                  {{ currentStep === totalSteps ? 'Finalizar' : 'Avançar' }}
                </Button>
              </div>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
