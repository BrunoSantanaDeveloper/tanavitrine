<script setup>
import { useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { Button } from '@/Components/shadcn/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/shadcn/ui/card'
import { Input } from '@/Components/shadcn/ui/input'
import { Label } from '@/Components/shadcn/ui/label'
import { Textarea } from '@/Components/shadcn/ui/textarea'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/shadcn/ui/select'
import { toast } from 'vue-sonner'
import { Icon } from '@iconify/vue'
import { formatPhone, formatCEP } from '@/utils/formatters'

const props = defineProps({
  store: {
    type: Object,
    required: true
  },
  categories: {
    type: Array,
    default: () => []
  }
})

const logoPreview = ref(props.store.logo_url || null)
const logoInput = ref(null)

const form = useForm({
  name: props.store.name,
  description: props.store.description,
  sale_type: props.store.sale_type,
  store_type: props.store.store_type,
  category_id: props.store.category_id ? String(props.store.category_id) : null,
  subcategory: props.store.subcategory,
  gender: props.store.gender,
  min_order: props.store.min_order,
  whatsapp: props.store.whatsapp,
  phone: props.store.phone,
  email: props.store.email,
  website: props.store.website,
  instagram: props.store.instagram,
  facebook: props.store.facebook,
  tiktok: props.store.tiktok,
  address: props.store.address,
  city: props.store.city,
  state: props.store.state,
  zip_code: props.store.zip_code,
  logo: null,
})

const subcategories = computed(() => {
  const category = props.categories.find(c => c.id === parseInt(form.category_id))
  return category?.children || []
})

function handleLogoUpload(e) {
  const file = e.target.files[0]
  if (!file) return

  if (!file.type.startsWith('image/')) {
    alert('Apenas imagens são permitidas')
    return
  }
  if (file.size > 2 * 1024 * 1024) {
    alert('Logo muito grande. Máximo 2MB.')
    return
  }

  form.logo = file

  const reader = new FileReader()
  reader.onload = (e) => {
    logoPreview.value = e.target.result
  }
  reader.readAsDataURL(file)
}

function removeLogo() {
  form.logo = null
  logoPreview.value = null
  if (logoInput.value) {
    logoInput.value.value = ''
  }
}

function handleWhatsAppInput(e) {
  form.whatsapp = formatPhone(e.target.value)
}

function handlePhoneInput(e) {
  form.phone = formatPhone(e.target.value)
}

function handleCEPInput(e) {
  form.zip_code = formatCEP(e.target.value)
}

function submit() {
  // Inertia requires using POST with _method for file uploads
  form.transform((data) => ({
    ...data,
    _method: 'PUT'
  })).post(route('dashboard.stores.update', props.store.slug), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
      toast.success('Salvo com Sucesso!')
    },
    onError: () => {
        toast.success('Erro ao salvar, entre em contato com o suporte!')
    },
  })

}
</script>

<template>
  <Head :title="`Editar ${store.name}`" />

  <AppLayout :title="`Editar ${store.name}`">
    <div class="min-h-screen bg-gray-50 p-6">
      <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Editar Vitrine</h1>
            <p class="text-muted-foreground">{{ store.name }}</p>
          </div>
          <Button :as="Link" :href="route('dashboard')" variant="outline">
            <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
            Voltar
          </Button>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
          <!-- Informações Básicas -->
          <Card>
            <CardHeader>
              <CardTitle>Informações Básicas</CardTitle>
              <CardDescription>Informações principais da sua vitrine</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="grid gap-4">
                <!-- Logo Upload -->
                <div>
                  <Label class="text-base mb-2 block">Logo da Loja</Label>
                  <div class="flex gap-4 items-start">
                    <div class="flex-shrink-0">
                      <div v-if="logoPreview" class="relative">
                        <img :src="logoPreview" alt="Logo preview" class="w-32 h-32 object-cover rounded-lg border-2 border-gray-300" />
                        <button type="button" @click="removeLogo" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1.5 shadow-lg hover:bg-red-600 transition-colors">
                          <Icon icon="lucide:x" class="h-4 w-4" />
                        </button>
                      </div>
                      <div v-else class="w-32 h-32 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center cursor-pointer hover:border-teal-500 transition-colors">
                        <label for="logo-upload" class="cursor-pointer text-center">
                          <Icon icon="lucide:image-plus" class="h-8 w-8 text-gray-400 mx-auto mb-1" />
                          <span class="text-xs text-gray-500">Adicionar logo</span>
                        </label>
                      </div>
                      <input ref="logoInput" type="file" id="logo-upload" accept="image/*" class="hidden" @change="handleLogoUpload" />
                    </div>
                    <div class="flex-1">
                      <p class="text-sm text-muted-foreground">
                        Faça upload do logo da sua loja. Recomendamos uma imagem quadrada de pelo menos 200x200px.
                      </p>
                      <p class="text-xs text-muted-foreground mt-2">
                        Formatos aceitos: JPG, PNG, WEBP (máx. 2MB)
                      </p>
                      <div v-if="logoPreview" class="mt-3">
                        <Button type="button" variant="outline" size="sm" @click="logoInput?.click()">
                          <Icon icon="lucide:upload" class="mr-2 h-4 w-4" />
                          Trocar Logo
                        </Button>
                      </div>
                    </div>
                  </div>
                </div>

                <div>
                  <Label for="name">Nome da Loja *</Label>
                  <Input id="name" v-model="form.name" required />
                </div>

                <div>
                  <Label for="description">Descrição *</Label>
                  <Textarea id="description" v-model="form.description" rows="4" required />
                </div>

                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <Label for="sale_type">Tipo de Venda *</Label>
                    <Select v-model="form.sale_type">
                      <SelectTrigger>
                        <SelectValue placeholder="Selecione" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="atacado">Atacado</SelectItem>
                        <SelectItem value="varejo">Varejo</SelectItem>
                        <SelectItem value="ambos">Ambos</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <div>
                    <Label for="store_type">Tipo de Loja *</Label>
                    <Select v-model="form.store_type">
                      <SelectTrigger>
                        <SelectValue placeholder="Selecione" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="fisica">Física</SelectItem>
                        <SelectItem value="virtual">Virtual</SelectItem>
                        <SelectItem value="ambos">Ambos</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Categoria -->
          <Card>
            <CardHeader>
              <CardTitle>Categoria</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <Label for="category_id">Categoria *</Label>
                  <Select v-model="form.category_id">
                    <SelectTrigger>
                      <SelectValue placeholder="Selecione" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="cat in categories" :key="cat.id" :value="String(cat.id)">
                        {{ cat.name }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div>
                  <Label for="subcategory">Subcategoria</Label>
                  <Input id="subcategory" v-model="form.subcategory" />
                </div>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <Label for="gender">Gênero</Label>
                  <Select v-model="form.gender">
                    <SelectTrigger>
                      <SelectValue placeholder="Selecione" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="masculino">Masculino</SelectItem>
                      <SelectItem value="feminino">Feminino</SelectItem>
                      <SelectItem value="unissex">Unissex</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div>
                  <Label for="min_order">Pedido Mínimo</Label>
                  <Input id="min_order" v-model="form.min_order" placeholder="Ex: 6 peças" />
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Contato -->
          <Card>
            <CardHeader>
              <CardTitle>Contato</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <Label for="whatsapp">WhatsApp *</Label>
                  <Input id="whatsapp" v-model="form.whatsapp" placeholder="(62) 99999-9999" maxlength="15" required @input="handleWhatsAppInput" />
                </div>

                <div>
                  <Label for="phone">Telefone</Label>
                  <Input id="phone" v-model="form.phone" placeholder="(62) 3333-3333" maxlength="15" @input="handlePhoneInput" />
                </div>
              </div>

              <div>
                <Label for="email">E-mail *</Label>
                <Input id="email" v-model="form.email" type="email" required />
              </div>
            </CardContent>
          </Card>

          <!-- Redes Sociais -->
          <Card>
            <CardHeader>
              <CardTitle>Redes Sociais</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <Label for="website">Website</Label>
                  <Input id="website" v-model="form.website" placeholder="https://..." />
                </div>

                <div>
                  <Label for="instagram">Instagram</Label>
                  <Input id="instagram" v-model="form.instagram" placeholder="@usuario" />
                </div>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <Label for="facebook">Facebook</Label>
                  <Input id="facebook" v-model="form.facebook" placeholder="facebook.com/..." />
                </div>

                <div>
                  <Label for="tiktok">TikTok</Label>
                  <Input id="tiktok" v-model="form.tiktok" placeholder="@usuario" />
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Localização -->
          <Card>
            <CardHeader>
              <CardTitle>Localização</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div>
                <Label for="address">Endereço</Label>
                <Input id="address" v-model="form.address" />
              </div>

              <div class="grid grid-cols-3 gap-4">
                <div>
                  <Label for="city">Cidade *</Label>
                  <Input id="city" v-model="form.city" required />
                </div>

                <div>
                  <Label for="state">Estado *</Label>
                  <Input id="state" v-model="form.state" maxlength="2" required />
                </div>

                <div>
                  <Label for="zip_code">CEP</Label>
                  <Input id="zip_code" v-model="form.zip_code" placeholder="00000-000" maxlength="9" @input="handleCEPInput" />
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Actions -->
          <div class="flex justify-end gap-4">
            <Button type="button" variant="outline" :as="Link" :href="route('dashboard')">
              Cancelar
            </Button>
            <Button type="submit" :disabled="form.processing" class="bg-teal-600 hover:bg-teal-700">
              <Icon v-if="form.processing" icon="lucide:loader-2" class="mr-2 h-4 w-4 animate-spin" />
              Salvar Alterações
            </Button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
