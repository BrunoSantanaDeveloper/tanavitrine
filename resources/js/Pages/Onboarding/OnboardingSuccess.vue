<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import Card from '@/Components/shadcn/ui/card/Card.vue'
import CardContent from '@/Components/shadcn/ui/card/CardContent.vue'
import Checkbox from '@/Components/shadcn/ui/checkbox/Checkbox.vue'
import Label from '@/Components/shadcn/ui/label/Label.vue'
import Input from '@/Components/shadcn/ui/input/Input.vue'
import AddressForm from '@/Components/AddressForm.vue'
import { Separator } from '@/Components/shadcn/ui/separator'
import axios from 'axios'
import { inject } from 'vue'
import { useColorMode } from '@vueuse/core'
import { formatPhone } from '@/utils/formatters'

const route = inject('route')

// Forçar tema light para success page
useColorMode({
  attribute: 'class',
  modes: {
    light: '',
    dark: 'dark',
  },
  initialValue: 'light',
})

const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
  subscription: {
    type: Object,
    required: true,
  },
  clinic_address: {
    type: Object,
    default: null,
  },
})

const useClinicAddress = ref(true)
const deliveryAddress = ref({
  cep: '',
  street: '',
  number: '',
  complement: '',
  neighborhood: '',
  city: '',
  state: '',
})
const recipientName = ref('')
const recipientPhone = ref('')
const isSaving = ref(false)

async function saveAndContinue() {
  isSaving.value = true

  try {
    await axios.post(route('onboarding.delivery.save'), {
      recipient_name: useClinicAddress.value ? '' : recipientName.value,
      recipient_phone: useClinicAddress.value ? '' : recipientPhone.value,
    })

    // Redirect to dashboard
    router.visit(route('dashboard'))
  } catch (error) {
    console.error('Erro ao salvar endereço:', error)
    alert('Erro ao salvar endereço. Por favor, tente novamente.')
  } finally {
    isSaving.value = false
  }
}

function handlePhoneInput(e) {
  recipientPhone.value = formatPhone(e.target.value)
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-green-50 to-emerald-50 py-12">
    <div class="container mx-auto px-4 max-w-3xl">
      <Card>
        <CardContent class="p-8">
          <!-- Confirmação de sucesso -->
          <div class="text-center mb-8">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <Icon icon="lucide:check" class="h-10 w-10 text-green-600" />
            </div>
            <h1 class="text-3xl font-bold mb-2">Pagamento Confirmado! 🎉</h1>
            <p class="text-muted-foreground">
              Bem-vindo ao Tanavitrine, <span class="font-semibold">{{ user.name }}</span>!
            </p>
          </div>

          <!-- Resumo do plano -->
          <Card class="mb-6 border-2 border-green-200 bg-green-50/50">
            <CardContent class="p-4">
              <div class="flex items-center gap-2 mb-2">
                <Icon icon="lucide:crown" class="h-5 w-5 text-primary" />
                <h3 class="font-semibold">Plano Contratado</h3>
              </div>
              <p class="text-lg font-bold">{{ subscription.plan_name }}</p>
              <p v-if="subscription.price" class="text-sm text-muted-foreground">
                R$ {{ subscription.price.toFixed(2).replace('.', ',') }}/{{ subscription.interval }}
              </p>
              <p v-else class="text-sm text-muted-foreground">
                {{ subscription.interval }}
              </p>
            </CardContent>
          </Card>

          <!-- Pré-requisitos técnicos -->
          <div class="mb-6">
            <h3 class="font-semibold mb-4 flex items-center gap-2">
              <Icon icon="lucide:clipboard-check" class="h-5 w-5 text-primary" />
              Pré-requisitos Técnicos
            </h3>
            <div class="space-y-2 bg-blue-50 border border-blue-200 rounded-lg p-4">
              <div class="flex items-start gap-2">
                <Icon icon="lucide:check-circle" class="h-5 w-5 text-green-600 mt-0.5 flex-shrink-0" />
                <span class="text-sm">TV com entrada HDMI</span>
              </div>
              <div class="flex items-start gap-2">
                <Icon icon="lucide:check-circle" class="h-5 w-5 text-green-600 mt-0.5 flex-shrink-0" />
                <span class="text-sm">Conexão Wi-Fi estável (mínimo 5 Mbps)</span>
              </div>
              <div class="flex items-start gap-2">
                <Icon icon="lucide:check-circle" class="h-5 w-5 text-green-600 mt-0.5 flex-shrink-0" />
                <span class="text-sm">Tomada próxima à TV</span>
              </div>
            </div>
          </div>

          <Separator class="my-6" />

          <!-- Endereço de entrega -->
          <div class="mb-8">
            <h3 class="font-semibold mb-4 flex items-center gap-2">
              <Icon icon="lucide:map-pin" class="h-5 w-5 text-primary" />
              Endereço de Entrega do Player
            </h3>

            <div class="flex items-center gap-2 mb-4 p-3 border rounded-lg">
              <Checkbox
                id="use-clinic-address"
                v-model:checked="useClinicAddress"
              />
              <Label for="use-clinic-address" class="cursor-pointer">
                Usar endereço da clínica
              </Label>
            </div>

            <div v-if="!useClinicAddress" class="space-y-4 bg-gray-50 border rounded-lg p-4">
              <AddressForm v-model="deliveryAddress" />

              <Separator class="my-4" />

              <div>
                <Label for="recipient-name">Nome para Recebimento *</Label>
                <Input
                  id="recipient-name"
                  v-model="recipientName"
                  placeholder="Nome completo"
                  class="mt-1"
                />
              </div>

              <div>
                <Label for="recipient-phone">Telefone para Contato da Entrega *</Label>
                <Input
                  id="recipient-phone"
                  v-model="recipientPhone"
                  type="tel"
                  placeholder="(62) 99999-9999"
                  maxlength="15"
                  class="mt-1"
                  @input="handlePhoneInput"
                />
              </div>
            </div>

            <div v-else class="bg-gray-50 border rounded-lg p-4">
              <p class="text-sm text-muted-foreground mb-2">
                <Icon icon="lucide:info" class="inline h-4 w-4 mr-1" />
                O Player será enviado para o endereço da clínica cadastrado:
              </p>
              <div v-if="clinic_address" class="text-sm">
                <p>{{ clinic_address.street }}, {{ clinic_address.number }}</p>
                <p v-if="clinic_address.complement">{{ clinic_address.complement }}</p>
                <p>{{ clinic_address.neighborhood }} - {{ clinic_address.city }}/{{ clinic_address.state }}</p>
                <p>CEP: {{ clinic_address.cep }}</p>
              </div>
            </div>

            <p class="text-sm text-muted-foreground mt-4 flex items-center gap-2">
              <Icon icon="lucide:truck" class="h-4 w-4" />
              Prazo de entrega: <span class="font-semibold">5 a 7 dias úteis</span>
            </p>
          </div>

          <Separator class="my-6" />

          <!-- Próximos passos -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <h3 class="font-semibold mb-3 flex items-center gap-2">
              <Icon icon="lucide:list-checks" class="h-5 w-5 text-primary" />
              Próximos Passos
            </h3>
            <ol class="space-y-2 text-sm">
              <li class="flex items-start gap-2">
                <span class="font-bold text-primary">1.</span>
                <span>Aguarde o recebimento do Player VetFun configurado</span>
              </li>
              <li class="flex items-start gap-2">
                <span class="font-bold text-primary">2.</span>
                <span>Conecte o Player à sua TV via HDMI</span>
              </li>
              <li class="flex items-start gap-2">
                <span class="font-bold text-primary">3.</span>
                <span>Configure a conexão Wi-Fi seguindo as instruções na tela</span>
              </li>
              <li class="flex items-start gap-2">
                <span class="font-bold text-primary">4.</span>
                <span>Acesse o painel Tanavitrine para gerenciar seus conteúdos</span>
              </li>
            </ol>
          </div>

          <!-- Botão para dashboard -->
          <div class="text-center">
            <Button
              size="lg"
              @click="saveAndContinue"
              :disabled="isSaving || (!useClinicAddress && (!deliveryAddress.cep || !recipientName || !recipientPhone))"
            >
              <Icon v-if="isSaving" icon="lucide:loader-2" class="mr-2 h-5 w-5 animate-spin" />
              <Icon v-else icon="lucide:arrow-right" class="mr-2 h-5 w-5" />
              {{ isSaving ? 'Salvando...' : 'Acessar Meu Painel' }}
            </Button>
          </div>
        </CardContent>
      </Card>

      <!-- Footer info -->
      <div class="text-center mt-8">
        <p class="text-sm text-muted-foreground">
          <Icon icon="lucide:headphones" class="inline h-4 w-4 mr-1" />
          Precisa de ajuda? Entre em contato pelo WhatsApp: +55 62 9 9172-9522
        </p>
      </div>
    </div>
  </div>
</template>
