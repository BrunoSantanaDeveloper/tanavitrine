<script setup>
import { Button } from '@/Components/shadcn/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import Input from '@/Components/shadcn/ui/input/Input.vue'
import Label from '@/Components/shadcn/ui/label/Label.vue'
import Textarea from '@/Components/shadcn/ui/textarea/Textarea.vue'
import Select from '@/Components/shadcn/ui/select/Select.vue'
import SelectContent from '@/Components/shadcn/ui/select/SelectContent.vue'
import SelectItem from '@/Components/shadcn/ui/select/SelectItem.vue'
import SelectTrigger from '@/Components/shadcn/ui/select/SelectTrigger.vue'
import SelectValue from '@/Components/shadcn/ui/select/SelectValue.vue'
import { Progress } from '@/Components/shadcn/ui/progress'
import AppLayout from '@/Layouts/AppLayout.vue'
import { router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { Icon } from '@iconify/vue'
import { formatPhone, formatCEP } from '@/utils/formatters'

const props = defineProps({
  categories: {
    type: Array,
    required: true
  }
})

const currentStep = ref(1)
const totalSteps = 5

const form = useForm({
  // Step 1: Tipo de negócio
  sale_type: '',

  // Step 2: Categoria
  category_id: '',
  subcategory: '',
  gender: '',

  // Step 3: Informações da loja
  name: '',
  description: '',
  store_type: '',
  min_order: '',

  // Step 4: Localização
  address: '',
  city: '',
  state: '',
  zip_code: '',

  // Step 5: Contato
  whatsapp: '',
  phone: '',
  email: '',
  website: '',
  instagram: '',
  facebook: '',
  tiktok: '',
})

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
  return category?.subcategories || []
})

function handleNext() {
  if (currentStep.value < totalSteps) {
    currentStep.value++
    window.scrollTo(0, 0)
  } else {
    // Submit form
    form.post('/dashboard/stores', {
      onSuccess: () => {
        router.visit('/dashboard/stores')
      }
    })
  }
}

function handleBack() {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

const isStepValid = computed(() => {
  if (currentStep.value === 1) {
    return !!form.sale_type
  }
  if (currentStep.value === 2) {
    return !!form.category_id && !!form.subcategory
  }
  if (currentStep.value === 3) {
    return form.name.trim() !== '' && form.description.trim() !== '' && !!form.store_type
  }
  if (currentStep.value === 4) {
    return form.city.trim() !== '' && form.state.trim() !== ''
  }
  if (currentStep.value === 5) {
    return form.whatsapp.trim() !== '' || form.email.trim() !== ''
  }
  return false
})

const progress = computed(() => (currentStep.value / totalSteps) * 100)

function handlePhoneInput(e) {
  form.whatsapp = formatPhone(e.target.value)
}

function handlePhoneFixoInput(e) {
  form.phone = formatPhone(e.target.value)
}

function handleCEPInput(e) {
  form.zip_code = formatCEP(e.target.value)
}
</script>

<template>
  <AppLayout title="Criar Vitrine">
    <div class="py-12">
      <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <!-- Progress -->
        <div class="mb-8">
          <Progress :model-value="progress" class="h-2" />
          <p class="text-sm text-muted-foreground mt-2 text-center">
            Passo {{ currentStep }} de {{ totalSteps }}
          </p>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>
              <span v-if="currentStep === 1">Tipo de Negócio</span>
              <span v-if="currentStep === 2">Categoria e Segmento</span>
              <span v-if="currentStep === 3">Informações da Loja</span>
              <span v-if="currentStep === 4">Localização</span>
              <span v-if="currentStep === 5">Informações de Contato</span>
            </CardTitle>
          </CardHeader>

          <CardContent class="space-y-6">
            <!-- Step 1: Sale Type -->
            <div v-if="currentStep === 1" class="space-y-4">
              <Label>Qual o tipo de venda da sua loja?</Label>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <button
                  v-for="type in saleTypes"
                  :key="type.value"
                  @click="form.sale_type = type.value"
                  :class="[
                    'p-6 border-2 rounded-lg transition-all cursor-pointer',
                    form.sale_type === type.value
                      ? 'border-primary bg-primary/10'
                      : 'border-border hover:border-primary/50'
                  ]"
                >
                  <Icon :icon="type.icon" class="size-12 mx-auto mb-3" />
                  <p class="font-semibold">{{ type.label }}</p>
                </button>
              </div>
            </div>

            <!-- Step 2: Category -->
            <div v-if="currentStep === 2" class="space-y-4">
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

            <!-- Step 3: Store Info -->
            <div v-if="currentStep === 3" class="space-y-4">
              <div>
                <Label for="name">Nome da Loja *</Label>
                <Input
                  id="name"
                  v-model="form.name"
                  placeholder="Ex: Moda Bella Atacado"
                  :class="{ 'border-destructive': form.errors.name }"
                />
                <p v-if="form.errors.name" class="text-sm text-destructive mt-1">
                  {{ form.errors.name }}
                </p>
              </div>

              <div>
                <Label for="description">Descrição *</Label>
                <Textarea
                  id="description"
                  v-model="form.description"
                  placeholder="Descreva sua loja, produtos e diferenciais..."
                  rows="5"
                  :class="{ 'border-destructive': form.errors.description }"
                />
                <p v-if="form.errors.description" class="text-sm text-destructive mt-1">
                  {{ form.errors.description }}
                </p>
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

            <!-- Step 4: Location -->
            <div v-if="currentStep === 4" class="space-y-4">
              <div>
                <Label for="address">Endereço</Label>
                <Input
                  id="address"
                  v-model="form.address"
                  placeholder="Rua, número, bairro"
                />
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <Label for="city">Cidade *</Label>
                  <Input
                    id="city"
                    v-model="form.city"
                    placeholder="Cidade"
                    :class="{ 'border-destructive': form.errors.city }"
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
                <Label for="zip_code">CEP</Label>
                <Input
                  id="zip_code"
                  v-model="form.zip_code"
                  placeholder="00000-000"
                  maxlength="9"
                  @input="handleCEPInput"
                />
              </div>
            </div>

            <!-- Step 5: Contact -->
            <div v-if="currentStep === 5" class="space-y-4">
              <div>
                <Label for="whatsapp">WhatsApp * (principal contato)</Label>
                <Input
                  id="whatsapp"
                  v-model="form.whatsapp"
                  placeholder="(00) 00000-0000"
                  maxlength="15"
                  :class="{ 'border-destructive': form.errors.whatsapp }"
                  @input="handlePhoneInput"
                />
              </div>

              <div>
                <Label for="phone">Telefone</Label>
                <Input
                  id="phone"
                  v-model="form.phone"
                  placeholder="(00) 0000-0000"
                  maxlength="15"
                  @input="handlePhoneFixoInput"
                />
              </div>

              <div>
                <Label for="email">Email *</Label>
                <Input
                  id="email"
                  v-model="form.email"
                  type="email"
                  placeholder="contato@loja.com.br"
                  :class="{ 'border-destructive': form.errors.email }"
                />
              </div>

              <div>
                <Label for="website">Website</Label>
                <Input
                  id="website"
                  v-model="form.website"
                  type="url"
                  placeholder="https://www.loja.com.br"
                />
              </div>

              <div>
                <Label>Redes Sociais (opcional)</Label>
                <div class="space-y-2">
                  <Input
                    v-model="form.instagram"
                    placeholder="Instagram: @usuario"
                  />
                  <Input
                    v-model="form.facebook"
                    placeholder="Facebook: facebook.com/usuario"
                  />
                  <Input
                    v-model="form.tiktok"
                    placeholder="TikTok: @usuario"
                  />
                </div>
              </div>
            </div>

            <!-- Navigation -->
            <div class="flex justify-between pt-6 border-t">
              <Button
                v-if="currentStep > 1"
                variant="outline"
                @click="handleBack"
                :disabled="form.processing"
              >
                Voltar
              </Button>
              <div v-else></div>

              <Button
                @click="handleNext"
                :disabled="!isStepValid || form.processing"
              >
                {{ currentStep === totalSteps ? 'Criar Vitrine' : 'Próximo' }}
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
