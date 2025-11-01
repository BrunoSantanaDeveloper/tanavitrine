<script setup>
import { computed, inject, onMounted, ref, watch } from 'vue'
import { Icon } from '@iconify/vue'
import { useForm } from '@inertiajs/vue3'
import { useColorMode } from '@vueuse/core'
import Card from '@/Components/shadcn/ui/card/Card.vue'
import CardContent from '@/Components/shadcn/ui/card/CardContent.vue'
import { Progress } from '@/Components/shadcn/ui/progress'
import Step1StoreName from './Steps/Step1StoreName.vue'
import Step2Category from './Steps/Step2Category.vue'
import Step3Media from './Steps/Step3Media.vue'
import Step4Contact from './Steps/Step4Contact.vue'
import Step5PlanConfirmation from './Steps/Step5PlanConfirmation.vue'
import Step6UserData from './Steps/Step6UserData.vue'

const route = inject('route')

// Forçar tema light para onboarding
useColorMode({
  attribute: 'class',
  modes: {
    light: '',
    dark: 'dark',
  },
  initialValue: 'light',
})

const props = defineProps({
  plan: {
    type: Object,
    required: true,
  },
  availablePlans: {
    type: Array,
    default: () => [],
  },
  categories: {
    type: Array,
    default: () => [],
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
})
const currentStep = ref(1)
const totalSteps = 6 // Step 1: Nome | Step 2: Categoria | Step 3: Mídia | Step 4: Contato | Step 5: Plano | Step 6: Dados do Usuário
const currentPlan = ref(props.plan)

const form = useForm({
  // Step 1 - Nome da Loja
  store_name: '',
  sale_type: '', // atacado, varejo, ambos

  // Step 2 - Categoria e Produtos
  category_id: '',
  subcategory: [], // Array para seleção múltipla
  gender: '',
  description: '',
  min_order: '',

  // Step 3 - Logo, Fotos e Vídeo
  logo: null,
  photos: [],
  video_url: null,
  video_file: null,

  // Step 4 - Contato e Localização
  address: {
    cep: '',
    street: '',
    number: '',
    complement: '',
    neighborhood: '',
    city: '',
    state: '',
  },
  whatsapp: '',
  phone: '',
  social_media: {
    instagram: '',
    facebook: '',
    tiktok: '',
    website: '',
  },
  // Coordenadas geográficas (preenchidas automaticamente via geocoding)
  latitude: null,
  longitude: null,

  // Step 5 - Dados do Usuário
  name: '',
  email: '',
  user_phone: '',
  password: '',
  password_confirmation: '',
  terms: false,

  // Metadata
  plan_interval_id: props.plan?.id,
  coupon_code: null, // Coupon code if applied
})

// Converter arquivos para base64 para salvar no localStorage
async function fileToBase64(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.readAsDataURL(file)
    reader.onload = () => resolve({
      data: reader.result,
      name: file.name,
      type: file.type,
      size: file.size,
    })
    reader.onerror = error => reject(error)
  })
}

// Auto-save em localStorage a cada mudança
watch(form, async (newForm) => {
  const dataToSave = {
    store_name: newForm.store_name,
    sale_type: newForm.sale_type,
    category_id: newForm.category_id,
    subcategory: newForm.subcategory,
    gender: newForm.gender,
    description: newForm.description,
    min_order: newForm.min_order,
    video_url: newForm.video_url,
    address: newForm.address,
    whatsapp: newForm.whatsapp,
    phone: newForm.phone,
    social_media: newForm.social_media,
    latitude: newForm.latitude,
    longitude: newForm.longitude,
    name: newForm.name,
    email: newForm.email,
    user_phone: newForm.user_phone,
    currentStep: currentStep.value,
  }

  // Salvar logo em base64
  if (newForm.logo && newForm.logo instanceof File) {
    try {
      dataToSave.logo = await fileToBase64(newForm.logo)
    } catch (e) {
      console.error('Erro ao converter logo:', e)
    }
  }

  // Salvar fotos em base64
  if (newForm.photos && newForm.photos.length > 0) {
    try {
      dataToSave.photos = await Promise.all(
        newForm.photos.map(photo => photo instanceof File ? fileToBase64(photo) : photo)
      )
    } catch (e) {
      console.error('Erro ao converter fotos:', e)
    }
  }

  localStorage.setItem('onboarding_progress_tanavitrine', JSON.stringify(dataToSave))
}, { deep: true })

// Converter base64 de volta para File
function base64ToFile(base64Data) {
  if (!base64Data || !base64Data.data) return null

  try {
    const arr = base64Data.data.split(',')
    const mime = arr[0].match(/:(.*?);/)[1]
    const bstr = atob(arr[1])
    let n = bstr.length
    const u8arr = new Uint8Array(n)
    while (n--) {
      u8arr[n] = bstr.charCodeAt(n)
    }
    return new File([u8arr], base64Data.name, { type: mime })
  } catch (e) {
    console.error('Erro ao converter base64 para arquivo:', e)
    return null
  }
}

// Restaurar progresso salvo
onMounted(() => {
  const saved = localStorage.getItem('onboarding_progress_tanavitrine')
  if (saved) {
    try {
      const savedData = JSON.parse(saved)

      Object.keys(savedData).forEach(key => {
        if (key === 'currentStep') {
          currentStep.value = savedData[key]
        }
        else if (key === 'logo' && savedData[key]) {
          // Restaurar logo de base64
          form.logo = base64ToFile(savedData[key])
        }
        else if (key === 'photos' && savedData[key]) {
          // Restaurar fotos de base64
          form.photos = savedData[key].map(photo => base64ToFile(photo)).filter(Boolean)
        }
        else if (key === 'category_id' && savedData[key]) {
          // Garantir que category_id seja String para compatibilidade com Select
          form.category_id = String(savedData[key])
        }
        else if (key === 'subcategory') {
          // Garantir que subcategory seja Array
          form.subcategory = Array.isArray(savedData[key])
            ? savedData[key]
            : (savedData[key] ? [savedData[key]] : [])
        }
        else if (form[key] !== undefined) {
          form[key] = savedData[key]
        }
      })
    }
    catch (e) {
      console.error('Erro ao restaurar dados salvos:', e)
      localStorage.removeItem('onboarding_progress_tanavitrine')
    }
  }
})

const progressPercentage = computed(() => {
  return (currentStep.value / totalSteps) * 100
})

const currentStepComponent = computed(() => {
  const components = {
    1: Step1StoreName,
    2: Step2Category,
    3: Step3Media,
    4: Step4Contact,
    5: Step5PlanConfirmation,
    6: Step6UserData,
  }
  return components[currentStep.value]
})

function nextStep() {
  if (currentStep.value < totalSteps) {
    currentStep.value++
    window.scrollTo(0, 0)
  } else {
    submitForm()
  }
}

function prevStep() {
  if (currentStep.value > 1) {
    currentStep.value--
    window.scrollTo(0, 0)
  }
}

function handleChangePlan(newPlan) {
  currentPlan.value = newPlan
  form.plan_interval_id = newPlan.id
  window.scrollTo(0, 0)
}

function submitForm() {
  // Preparar dados para envio
  const formData = new FormData()

  // Dados do usuário
  formData.append('name', form.name)
  formData.append('email', form.email)
  formData.append('user_phone', form.user_phone)
  formData.append('password', form.password)
  formData.append('password_confirmation', form.password_confirmation)
  formData.append('terms', form.terms ? '1' : '0')
  formData.append('plan', form.plan_interval_id)

  // Coupon code if applied
  if (form.coupon_code) {
    formData.append('coupon_code', form.coupon_code)
  }

  // Dados da loja
  formData.append('store_name', form.store_name)
  formData.append('sale_type', form.sale_type)
  formData.append('category_id', form.category_id)

  // Subcategory as array
  if (form.subcategory && form.subcategory.length > 0) {
    form.subcategory.forEach((sub, index) => {
      formData.append(`subcategory[${index}]`, sub)
    })
  }

  if (form.gender) formData.append('gender', form.gender)
  formData.append('description', form.description)
  if (form.min_order) formData.append('min_order', form.min_order)

  // Localização e contato
  formData.append('address', JSON.stringify(form.address))
  formData.append('whatsapp', form.whatsapp)
  if (form.phone) formData.append('phone', form.phone)
  formData.append('social_media', JSON.stringify(form.social_media))

  // Coordenadas geográficas
  if (form.latitude)
    formData.append('latitude', form.latitude)
  if (form.longitude)
    formData.append('longitude', form.longitude)

  // Logo
  if (form.logo)
    formData.append('logo', form.logo)

  // Fotos
  form.photos.forEach((photo, index) => {
    formData.append(`photos[${index}]`, photo)
  })

  // Vídeo
  if (form.video_url)
    formData.append('video_url', form.video_url)
  if (form.video_file)
    formData.append('video', form.video_file)

  form
    .transform(() => formData)
    .post(route('register'), {
      onSuccess: () => {
        // Redireciona para Stripe Checkout via RegisterResponse
        localStorage.removeItem('onboarding_progress_tanavitrine')
      },
      onError: (errors) => {
        console.error('Erros no registro:', errors)
        // Se houver erros, voltar para a etapa correspondente
        if (errors.email || errors.password || errors.name || errors.user_phone) {
          currentStep.value = 6 // Step 6: User Data
        } else if (errors.store_name || errors.sale_type) {
          currentStep.value = 1 // Step 1: Store Name
        } else if (errors.category_id || errors.description || errors.subcategory) {
          currentStep.value = 2 // Step 2: Category
        } else if (errors.logo || errors.photos || errors.video) {
          currentStep.value = 3 // Step 3: Media
        } else if (errors.whatsapp || errors.address || errors.phone || errors.social_media) {
          currentStep.value = 4 // Step 4: Contact
        }
      },
    })
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-teal-50 to-orange-50 py-8">
    <div class="container mx-auto px-4 max-w-4xl">
      <!-- Header com logo e progresso -->
      <div class="text-center mb-8">
        <a href="/" class="flex justify-center items-center mb-6">
          <img src="/tanavitrine_light_icon.png" alt="TanaVitrine" class="h-16">
        </a>
        <h1 class="text-3xl font-bold mb-2 text-teal-900">Crie sua Vitrine no TanaVitrine</h1>
        <p class="text-muted-foreground">Etapa {{ currentStep }} de {{ totalSteps }}</p>
        <Progress :model-value="progressPercentage" class="mt-4 h-2" />
      </div>

      <!-- Steps -->
      <Card>
        <CardContent class="p-8">
          <component
            :is="currentStepComponent"
            v-model="form"
            :plan="currentPlan"
            :available-plans="availablePlans"
            :categories="categories"
            :errors="errors"
            @next="nextStep"
            @prev="prevStep"
            @change-plan="handleChangePlan"
          />
        </CardContent>
      </Card>

      <!-- Footer -->
      <div class="text-center mt-8">
        <p class="text-sm text-muted-foreground">
          <Icon icon="lucide:shield-check" class="inline h-4 w-4 mr-1" />
          Seus dados estão seguros e protegidos
        </p>
      </div>
    </div>
  </div>
</template>
