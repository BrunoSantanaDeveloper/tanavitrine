<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import { Icon } from '@iconify/vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import Card from '@/Components/shadcn/ui/card/Card.vue'
import CardContent from '@/Components/shadcn/ui/card/CardContent.vue'
import Badge from '@/Components/shadcn/ui/badge/Badge.vue'
import Input from '@/Components/shadcn/ui/input/Input.vue'
import Label from '@/Components/shadcn/ui/label/Label.vue'
import { Separator } from '@/Components/shadcn/ui/separator'
import { Alert, AlertDescription, AlertTitle } from '@/Components/shadcn/ui/alert'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/shadcn/ui/dialog'

const form = defineModel()

const props = defineProps({
  plan: {
    type: Object,
    required: true,
  },
  availablePlans: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['next', 'prev', 'change-plan'])

const showPlanSelector = ref(false)

const couponCode = ref('')
const couponApplied = ref(false)
const couponData = ref(null)
const discount = ref(0)
const couponError = ref('')
const couponLoading = ref(false)

// Exit Intent Modal
const showExitModal = ref(false)
const exitCoupon = ref(null)
const exitModalShown = ref(false)

function changePlan(newPlan) {
  // Emitir evento para o componente pai atualizar o plano
  emit('change-plan', newPlan)
  showPlanSelector.value = false

  // Resetar cupom ao mudar de plano
  resetCoupon()
}

// Converter price para número
const planPrice = computed(() => {
  return parseFloat(props.plan.price) || 0
})

const totalFirstPayment = computed(() => {
  return Math.max(0, planPrice.value - discount.value)
})

const isSpecialCoupon = computed(() => {
  return couponData.value?.is_special || false
})

const couponDurationText = computed(() => {
  if (!couponData.value?.duration_months) return ''
  const months = couponData.value.duration_months
  return months === 1 ? '1 mês' : `${months} meses`
})

function resetCoupon() {
  couponApplied.value = false
  couponData.value = null
  discount.value = 0
  couponError.value = ''
}

async function applyCoupon() {
  if (!couponCode.value.trim()) {
    couponError.value = 'Digite um código de cupom'
    return
  }

  couponLoading.value = true
  couponError.value = ''

  try {
    const response = await axios.post('/api/coupons/validate', {
      code: couponCode.value.trim(),
      plan_price: planPrice.value,
    })

    if (response.data.valid) {
      couponApplied.value = true
      couponData.value = response.data.coupon
      discount.value = response.data.coupon.discount_amount

      // Save coupon code to form data for later use
      form.value.coupon_code = couponCode.value.trim().toUpperCase()
    }
  } catch (error) {
    console.error('Coupon validation error:', error)
    couponError.value = error.response?.data?.message || 'Erro ao validar cupom. Tente novamente.'
    resetCoupon()
  } finally {
    couponLoading.value = false
  }
}

function removeCoupon() {
  couponCode.value = ''
  form.value.coupon_code = null
  resetCoupon()
}

// Exit Intent Functions
async function fetchExitIntentCoupon() {
  try {
    const response = await axios.post('/api/coupons/exit-intent', {
      plan_price: planPrice.value,
    })

    if (response.data.found) {
      exitCoupon.value = response.data.coupon
    }
  } catch (error) {
    console.error('Error fetching exit intent coupon:', error)
  }
}

function handleMouseLeave(e) {
  // Só mostrar se:
  // 1. Mouse está saindo pela parte superior da página (indo para barra de endereços/tabs)
  // 2. Não aplicou cupom de 100%
  // 3. Modal ainda não foi mostrado
  // 4. Existe cupom de exit intent disponível
  const shouldShow = e.clientY <= 0 &&
                     !isSpecialCoupon.value &&
                     !exitModalShown.value &&
                     exitCoupon.value

  if (shouldShow) {
    showExitModal.value = true
    exitModalShown.value = true
  }
}

function applyExitCoupon() {
  if (exitCoupon.value) {
    couponCode.value = exitCoupon.value.code
    couponApplied.value = true
    couponData.value = exitCoupon.value
    discount.value = exitCoupon.value.discount_amount
    form.value.coupon_code = exitCoupon.value.code
    showExitModal.value = false
  }
}

// Lifecycle
onMounted(() => {
  // Buscar cupom de exit intent ao montar componente
  fetchExitIntentCoupon()

  // Adicionar listener de mouseleave para detectar quando mouse sai da página
  document.addEventListener('mouseleave', handleMouseLeave)
})

onUnmounted(() => {
  // Remover listener ao desmontar
  document.removeEventListener('mouseleave', handleMouseLeave)
})
</script>

<template>
  <div class="space-y-6">
    <div class="text-center mb-6">
      <div class="w-16 h-16 bg-teal-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <Icon icon="lucide:credit-card" class="h-8 w-8 text-teal-600" />
      </div>
      <h2 class="text-2xl font-bold mb-2">Confirme seu Plano</h2>
      <p class="text-muted-foreground">
        Revise os detalhes antes de prosseguir para o pagamento
      </p>
    </div>

    <!-- Plano selecionado -->
    <Card class="border-2 border-primary">
      <CardContent class="p-6">
        <div class="flex justify-between items-start mb-4">
          <div>
            <h3 class="text-xl font-bold">{{ plan.name }}</h3>
            <p class="text-muted-foreground">{{ plan.description }}</p>
            <Badge class="mt-2">{{ plan.interval_name }}</Badge>
          </div>
          <div class="flex flex-col items-end gap-2">
            <Badge variant="secondary">Selecionado</Badge>
            <Button
              variant="ghost"
              size="sm"
              @click="showPlanSelector = !showPlanSelector"
            >
              <Icon icon="lucide:refresh-cw" class="mr-2 h-4 w-4" />
              Trocar plano
            </Button>
          </div>
        </div>

        <Separator class="my-4" />

        <div class="space-y-2 text-sm">
          <div class="flex justify-between">
            <span>Valor do plano:</span>
            <span class="font-semibold">R$ {{ planPrice.toFixed(2).replace('.', ',') }}</span>
          </div>
          <div v-if="discount > 0" class="flex justify-between text-green-600">
            <span>Desconto aplicado:</span>
            <span class="font-semibold">- R$ {{ discount.toFixed(2).replace('.', ',') }}</span>
          </div>
          <Separator v-if="discount > 0" class="my-2" />
          <div class="flex justify-between text-lg font-bold">
            <span>Total a pagar:</span>
            <span>R$ {{ totalFirstPayment.toFixed(2).replace('.', ',') }}</span>
          </div>
        </div>

        <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
          <p class="text-xs text-blue-800">
            <Icon icon="lucide:info" class="inline h-4 w-4 mr-1" />
            Cobrança recorrente de R$ {{ planPrice.toFixed(2).replace('.', ',') }}/{{ plan.interval_name?.toLowerCase() }}
          </p>
        </div>
      </CardContent>
    </Card>

    <!-- Outros planos disponíveis -->
    <div v-if="showPlanSelector && availablePlans.length > 0" class="space-y-3">
      <h4 class="font-semibold text-sm">Escolha outro plano:</h4>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <Card
          v-for="availPlan in availablePlans.filter(p => p.id !== plan.id)"
          :key="availPlan.id"
          class="cursor-pointer hover:border-primary transition-all"
          @click="changePlan(availPlan)"
        >
          <CardContent class="p-4">
            <div class="flex justify-between items-start mb-2">
              <div>
                <h4 class="font-bold">{{ availPlan.name }}</h4>
                <Badge variant="outline" class="mt-1 text-xs">{{ availPlan.interval_name }}</Badge>
              </div>
              <span class="text-lg font-bold text-primary">
                R$ {{ parseFloat(availPlan.price).toFixed(2).replace('.', ',') }}
              </span>
            </div>
            <p class="text-xs text-muted-foreground mb-3">{{ availPlan.description }}</p>
            <ul class="space-y-1">
              <li
                v-for="(feature, idx) in availPlan.features?.slice(0, 3)"
                :key="idx"
                class="flex items-start text-xs"
              >
                <Icon icon="lucide:check" class="mr-1 h-3 w-3 text-primary flex-shrink-0 mt-0.5" />
                <span>{{ feature }}</span>
              </li>
              <li v-if="availPlan.features?.length > 3" class="text-xs text-muted-foreground">
                +{{ availPlan.features.length - 3 }} recursos
              </li>
            </ul>
            <Button variant="outline" size="sm" class="w-full mt-3">
              <Icon icon="lucide:arrow-right" class="mr-2 h-4 w-4" />
              Selecionar este plano
            </Button>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- Mensagem Especial para Cupom 100% -->
    <Alert v-if="isSpecialCoupon" class="border-2 border-yellow-500 bg-yellow-50">
      <Icon icon="lucide:circle-star" class="h-5 w-5 text-yellow-600" />
      <AlertTitle class="text-yellow-900 font-bold">Parabéns! Você é muito especial! 🎉</AlertTitle>
      <AlertDescription class="text-yellow-800">
        Você ganhou <strong>100% de desconto</strong> por <strong>{{ couponDurationText }}</strong>!
        Aproveite todos os recursos do plano <strong>{{ plan.name }}</strong> completamente grátis durante este período.
        Este é um benefício exclusivo para você!
      </AlertDescription>
    </Alert>

    <!-- Campo de cupom -->
    <div>
      <Label>Cupom de Desconto (Opcional)</Label>
      <div class="flex gap-2 mt-2">
        <Input
          v-model="couponCode"
          placeholder="Digite o código do cupom"
          :disabled="couponApplied"
          class="uppercase"
        />
        <Button
          variant="outline"
          @click="applyCoupon"
          :disabled="!couponCode || couponApplied || couponLoading"
        >
          <Icon v-if="couponLoading" icon="lucide:loader-2" class="mr-2 h-4 w-4 animate-spin" />
          {{ couponApplied ? 'Aplicado' : 'Aplicar' }}
        </Button>
        <Button
          v-if="couponApplied"
          variant="ghost"
          size="icon"
          @click="removeCoupon"
          title="Remover cupom"
        >
          <Icon icon="lucide:x" class="h-4 w-4" />
        </Button>
      </div>

      <!-- Success Message -->
      <p v-if="couponApplied && !isSpecialCoupon" class="text-xs text-green-600 mt-1">
        <Icon icon="lucide:check-circle" class="inline h-4 w-4 mr-1" />
        Cupom aplicado com sucesso!
      </p>

      <!-- Special Coupon Success -->
      <p v-if="couponApplied && isSpecialCoupon" class="text-xs text-yellow-600 mt-1 font-semibold">
        <Icon icon="lucide:sparkles" class="inline h-4 w-4 mr-1" />
        Cupom VIP aplicado! Você terá acesso grátis por {{ couponDurationText }}!
      </p>

      <!-- Error Message -->
      <p v-if="couponError" class="text-xs text-red-600 mt-1">
        <Icon icon="lucide:alert-circle" class="inline h-4 w-4 mr-1" />
        {{ couponError }}
      </p>
    </div>

    <!-- Recursos incluídos -->
    <div>
      <h4 class="font-semibold mb-3">O que está incluído:</h4>
      <ul class="space-y-2">
        <li
          v-for="(feature, index) in plan.features"
          :key="index"
          class="flex items-start text-sm"
        >
          <template v-if="typeof feature === 'string'">
            <Icon icon="lucide:check" class="mr-2 h-5 w-5 text-primary flex-shrink-0 mt-0.5" />
            <span>{{ feature }}</span>
          </template>
        </li>
      </ul>
    </div>

    <!-- Botões -->
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
        @click="emit('next')"
      >
        Continuar
        <Icon icon="lucide:arrow-right" class="ml-2 h-5 w-5" />
      </Button>
    </div>

    <!-- Exit Intent Modal -->
    <Dialog v-model:open="showExitModal">
      <DialogContent class="max-w-lg">
        <DialogHeader class="space-y-1">
          <div class="flex items-center justify-center mb-1">
            <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center animate-pulse">
              <Icon icon="lucide:gift" class="h-6 w-6 text-white" />
            </div>
          </div>
          <DialogTitle class="text-center text-lg font-bold bg-gradient-to-r from-yellow-600 to-orange-600 bg-clip-text text-transparent">
            🎉 ESPERE! Você Ganhou um Presente Especial! 🎉
          </DialogTitle>
          <DialogDescription class="text-center text-xs sm:text-sm">
            Antes de sair, saiba que você acaba de ganhar <span class="font-bold text-base text-yellow-600">100% DE DESCONTO</span> por <span class="font-bold">{{ exitCoupon?.duration_months }} meses</span>!
          </DialogDescription>
        </DialogHeader>

        <div class="py-2 space-y-2.5">
          <!-- Benefícios -->
          <div class="bg-gradient-to-br from-yellow-50 to-orange-50 p-2.5 rounded-lg border-2 border-yellow-300">
            <h3 class="font-bold text-sm mb-1.5 text-center text-gray-800">
              Com este cupom VIP você terá:
            </h3>
            <ul class="space-y-1">
              <li class="flex items-start gap-1.5">
                <Icon icon="lucide:check-circle" class="h-4 w-4 text-green-600 flex-shrink-0 mt-0.5" />
                <span class="text-xs text-gray-700"><strong>{{ exitCoupon?.duration_months }} meses GRÁTIS</strong> do plano {{ plan?.name || 'escolhido' }}</span>
              </li>
              <li class="flex items-start gap-1.5">
                <Icon icon="lucide:check-circle" class="h-4 w-4 text-green-600 flex-shrink-0 mt-0.5" />
                <span class="text-xs text-gray-700">Acesso completo a <strong>todos os recursos</strong></span>
              </li>
              <li class="flex items-start gap-1.5">
                <Icon icon="lucide:check-circle" class="h-4 w-4 text-green-600 flex-shrink-0 mt-0.5" />
                <span class="text-xs text-gray-700">Economia de <strong class="text-sm text-green-600">R$ {{ (planPrice * (exitCoupon?.duration_months || 1)).toFixed(2) }}</strong></span>
              </li>
              <li class="flex items-start gap-1.5">
                <Icon icon="lucide:check-circle" class="h-4 w-4 text-green-600 flex-shrink-0 mt-0.5" />
                <span class="text-xs text-gray-700">Sem compromisso - cancele quando quiser</span>
              </li>
            </ul>
          </div>

          <!-- Código do Cupom -->
          <div class="bg-white border-3 border-dashed border-yellow-400 p-2.5 rounded-lg text-center">
            <p class="text-xs text-gray-600 mb-1">Seu código VIP exclusivo:</p>
            <div class="bg-gradient-to-r from-yellow-100 to-orange-100 py-1.5 px-3 rounded-lg mb-1">
              <p class="text-xl sm:text-2xl font-bold text-yellow-700 tracking-wider">
                {{ exitCoupon?.code }}
              </p>
            </div>
            <p class="text-xs text-gray-500">
              Este cupom é válido apenas para você e expira em breve!
            </p>
          </div>

          <!-- Urgência -->
          <div class="bg-red-50 border-2 border-red-300 rounded-lg p-2 text-center">
            <p class="text-xs font-bold text-red-800">
              ⚠️ Clique abaixo para aplicar automaticamente!
            </p>
            <p class="text-xs text-red-600 mt-0.5">
              Oferta válida apenas para novos cadastros
            </p>
          </div>
        </div>

        <DialogFooter class="flex-col sm:flex-row gap-2">
          <Button
            variant="outline"
            size="sm"
            class="w-full sm:w-auto"
            @click="showExitModal = false"
          >
            Não, obrigado
          </Button>
          <Button
            size="sm"
            class="w-full sm:w-auto bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-600 hover:to-orange-600 text-white font-bold shadow-lg"
            @click="applyExitCoupon"
          >
            <Icon icon="lucide:sparkles" class="mr-2 h-4 w-4" />
            SIM! Aplicar Cupom
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
