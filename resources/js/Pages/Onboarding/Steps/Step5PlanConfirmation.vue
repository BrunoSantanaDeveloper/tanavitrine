<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'
import { Icon } from '@iconify/vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import Card from '@/Components/shadcn/ui/card/Card.vue'
import CardContent from '@/Components/shadcn/ui/card/CardContent.vue'
import Badge from '@/Components/shadcn/ui/badge/Badge.vue'
import Input from '@/Components/shadcn/ui/input/Input.vue'
import Label from '@/Components/shadcn/ui/label/Label.vue'
import { Separator } from '@/Components/shadcn/ui/separator'

const form = defineModel()

const props = defineProps({
  plan: {
    type: Object,
    required: true,
  },
  journey: {
    type: String,
    default: 'subscription',
  },
  trialDays: {
    type: Number,
    default: 14,
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

const trialDays = computed(() => props.trialDays)
const isTrialJourney = computed(() => props.journey === 'trial')
const trialCouponDays = computed(() => {
  if (!couponData.value?.duration_value || !couponData.value?.duration_unit) {
    return 0
  }

  const value = Number(couponData.value.duration_value) || 0
  if (value <= 0) {
    return 0
  }

  switch (couponData.value.duration_unit) {
    case 'days':
      return value
    case 'months':
      return value * 30
    case 'years':
      return value * 365
    default:
      return 0
  }
})

const trialDaysWithCoupon = computed(() => {
  if (!isTrialJourney.value || !couponApplied.value) {
    return trialDays.value
  }

  if (trialCouponDays.value <= 0) {
    return trialDays.value
  }

  return Math.max(trialDays.value, trialCouponDays.value)
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
      const trialCouponValue = Number(response.data.coupon?.duration_value) || 0
      const trialCouponUnit = response.data.coupon?.duration_unit
      const hasTrialDuration = trialCouponValue > 0 && ['days', 'months', 'years'].includes(trialCouponUnit)

      if (isTrialJourney.value && !hasTrialDuration) {
        couponError.value = 'Este cupom não oferece período adicional para jornada de teste.'
        couponApplied.value = false
        couponData.value = null
        discount.value = 0
        form.value.coupon_code = null
        return
      }

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
</script>

<template>
  <div class="space-y-6">
    <div class="text-center mb-6">
      <div class="w-16 h-16 bg-teal-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <Icon icon="lucide:credit-card" class="h-8 w-8 text-teal-600" />
      </div>
      <h2 class="text-2xl font-bold mb-2">Confirme seu Plano</h2>
      <p class="text-muted-foreground">
        {{ isTrialJourney ? `Ative seu teste grátis de ${trialDays} dias e, se tiver cupom, amplie o período promocional` : 'Revise os detalhes antes de prosseguir para o pagamento' }}
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
              v-if="!isTrialJourney"
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
            <span>{{ isTrialJourney ? 'Período grátis:' : 'Valor do plano:' }}</span>
            <span class="font-semibold">
              <template v-if="isTrialJourney">{{ trialDaysWithCoupon }} {{ trialDaysWithCoupon === 1 ? 'dia' : 'dias' }}</template>
              <template v-else>R$ {{ planPrice.toFixed(2).replace('.', ',') }}</template>
            </span>
          </div>
          <div v-if="isTrialJourney && couponApplied && trialCouponDays > 0" class="flex justify-between text-green-600">
            <span>Cupom aplicado no período:</span>
            <span class="font-semibold">{{ trialCouponDays }} {{ trialCouponDays === 1 ? 'dia' : 'dias' }}</span>
          </div>
          <div v-if="discount > 0 && !isTrialJourney" class="flex justify-between text-green-600">
            <span>Desconto aplicado:</span>
            <span class="font-semibold">- R$ {{ discount.toFixed(2).replace('.', ',') }}</span>
          </div>
          <Separator v-if="discount > 0 && !isTrialJourney" class="my-2" />
          <div v-if="!isTrialJourney" class="flex justify-between text-lg font-bold">
            <span>Total a pagar:</span>
            <span>R$ {{ totalFirstPayment.toFixed(2).replace('.', ',') }}</span>
          </div>
          <div v-else class="flex justify-between text-lg font-bold text-green-700">
            <span>Total agora:</span>
            <span>Grátis</span>
          </div>
        </div>

        <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
          <p class="text-xs text-blue-800">
            <Icon icon="lucide:info" class="inline h-4 w-4 mr-1" />
            <template v-if="isTrialJourney">
              Após o teste, sua vitrine sai do ar e você pode seguir para assinatura pelo painel.
            </template>
            <template v-else>
              Cobrança recorrente de R$ {{ planPrice.toFixed(2).replace('.', ',') }}/{{ plan.interval_name?.toLowerCase() }}
            </template>
          </p>
        </div>
      </CardContent>
    </Card>

    <!-- Outros planos disponíveis -->
    <div v-if="showPlanSelector && availablePlans.length > 0 && !isTrialJourney" class="space-y-3">
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

    <!-- Campo de cupom -->
    <div>
      <Label>{{ isTrialJourney ? 'Cupom (Opcional)' : 'Cupom de Desconto (Opcional)' }}</Label>
      <div class="flex gap-2 mt-2">
        <Input
          v-model="couponCode"
          :placeholder="isTrialJourney ? 'Digite o cupom para ampliar o período grátis' : 'Digite o código do cupom'"
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
      <p v-if="couponApplied" class="text-xs text-green-600 mt-1">
        <Icon icon="lucide:check-circle" class="inline h-4 w-4 mr-1" />
        {{ isTrialJourney ? 'Cupom aplicado no período de teste.' : 'Cupom aplicado com sucesso!' }}
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

  </div>
</template>
