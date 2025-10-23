<script setup>
import { ref, computed } from 'vue'
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
  availablePlans: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['next', 'prev', 'change-plan'])

const showPlanSelector = ref(false)

const couponCode = ref('')
const couponApplied = ref(false)
const discount = ref(0)

function changePlan(newPlan) {
  // Emitir evento para o componente pai atualizar o plano
  emit('change-plan', newPlan)
  showPlanSelector.value = false
}

// Converter price para número
const planPrice = computed(() => {
  return parseFloat(props.plan.price) || 0
})

const totalFirstPayment = computed(() => {
  return planPrice.value - discount.value
})

function applyCoupon() {
  // Aqui você pode validar o cupom com o backend
  // Por enquanto, apenas um exemplo
  if (couponCode.value.toLowerCase() === 'desconto10') {
    discount.value = planPrice.value * 0.1
    couponApplied.value = true
  } else {
    couponApplied.value = false
    discount.value = 0
  }
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

    <!-- Campo de cupom -->
    <div>
      <Label>Cupom de Desconto (Opcional)</Label>
      <div class="flex gap-2 mt-2">
        <Input
          v-model="couponCode"
          placeholder="Digite o código do cupom"
          :disabled="couponApplied"
        />
        <Button
          variant="outline"
          @click="applyCoupon"
          :disabled="!couponCode || couponApplied"
        >
          {{ couponApplied ? 'Aplicado' : 'Aplicar' }}
        </Button>
      </div>
      <p v-if="couponApplied" class="text-xs text-green-600 mt-1">
        <Icon icon="lucide:check-circle" class="inline h-4 w-4 mr-1" />
        Cupom aplicado com sucesso!
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
          <Icon icon="lucide:check" class="mr-2 h-5 w-5 text-primary flex-shrink-0 mt-0.5" />
          <span>{{ feature }}</span>
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
