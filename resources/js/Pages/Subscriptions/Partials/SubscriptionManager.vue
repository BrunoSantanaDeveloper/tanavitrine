<script setup>
import { Alert, AlertDescription, AlertTitle } from '@/Components/shadcn/ui/alert'
import { Button } from '@/Components/shadcn/ui/button'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import { Separator } from '@/Components/shadcn/ui/separator'
import { Tabs, TabsList, TabsTrigger } from '@/Components/shadcn/ui/tabs'
import { __ } from '@/Composables/useTranslations.js'
import { usePage } from '@inertiajs/vue3'
import { Check, Crown, Lock, TriangleAlert, Zap, Sparkles, ArrowRight } from 'lucide-vue-next'
import { computed, ref } from 'vue'

const page = usePage()

const props = defineProps({
  activeSubscriptions: {
    type: Array,
    default: () => [],
  },
  availableSubscriptions: {
    type: Array,
    default: () => [],
  },
  plans: {
    type: Array,
    required: true,
  },
  currentPlan: {
    type: Object,
    default: null,
  },
  limits: {
    type: Object,
    required: true,
  },
  usage: {
    type: Object,
    required: true,
  },
})

const selectedInterval = ref('monthly')

// Check if user has an active paid plan (not the default free plan)
const hasActivePlan = computed(() => {
  return props.currentPlan && !props.currentPlan.is_default
})

// Get only string features (filter out analytics object)
const currentPlanStringFeatures = computed(() => {
  if (!props.currentPlan?.features) return []

  return Object.values(props.currentPlan.features).filter(feature => typeof feature === 'string')
})

// Check if there are multiple intervals available
const hasMultipleIntervals = computed(() => {
  const allIntervals = new Set()
  props.plans.forEach(plan => {
    if (plan.intervals && Array.isArray(plan.intervals)) {
      plan.intervals.forEach(interval => {
        allIntervals.add(interval.code)
      })
    }
  })
  return allIntervals.size > 1
})

function formatPrice(price, interval = 'monthly') {
  if (price === 0 || !price) {
    return __('subscriptions.free')
  }
  const formatted = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  }).format(price)

  const intervalText = interval === 'yearly' ? '/ano' : '/mês'
  return `${formatted}${intervalText}`
}

function getPlanLimits(planLimits = null) {
  const limitsToUse = planLimits || props.limits
  if (!limitsToUse) return []

  return Object.entries(limitsToUse).map(([key, value]) => ({
    name: key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
    limit: value,
    current: props.usage?.[key] || 0,
  }))
}

// Filter only string features (remove analytics object)
function getStringFeatures(features) {
  if (!features) {
    return []
  }
  if (Array.isArray(features)) {
    return features
  }

  return Object.values(features).filter(feature => typeof feature === 'string')
}

const availablePlans = computed(() => {
  if (!props.plans || props.plans.length === 0) return []

  // Group plans by name and create intervals structure
  const planGroups = {}

  props.plans.forEach(plan => {
    // Skip default/free plans
    if (plan.is_default || plan.name.toLowerCase().includes('gratuito') || plan.name.toLowerCase().includes('free')) {
      return
    }

    // Skip current plan if user already has an active plan
    if (hasActivePlan.value && props.currentPlan && plan.id === props.currentPlan.id) {
      return
    }

    if (!planGroups[plan.name]) {
      planGroups[plan.name] = {
        id: plan.id,
        name: plan.name,
        description: plan.description,
        features: getStringFeatures(plan.features),
        limits: plan.limits || [],
        intervals: {}
      }
    }

    // Add intervals with their prices
    if (plan.intervals && plan.intervals.length > 0) {
      plan.intervals.forEach(interval => {
        // Map interval names to keys (Mensal -> monthly, Anual -> yearly)
        const intervalKey = interval.code === 'month' ? 'monthly' : interval.code === 'year' ? 'yearly' : interval.code

        // Handle both pivot object and flat structure
        const pivot = interval.pivot || interval

        const intervalData = {
          id: pivot.id,
          price: parseFloat(pivot.price || 0),
          stripe_price_id: pivot.stripe_price_id,
          name: interval.name,
          code: interval.code
        }

        planGroups[plan.name].intervals[intervalKey] = intervalData
      })
    }
  })

  return Object.values(planGroups)
})

function handleSubscribe(plan, intervalName) {
  const intervalData = plan.intervals[intervalName]
  if (!intervalData || !intervalData.id) {
    console.error('Invalid interval data', { plan, intervalName, intervalData })
    return
  }

  // Get user and store info
  const userName = page.props.auth?.user?.name || 'Usuário'
  const storeName = page.props.auth?.user?.current_team?.name || 'Minha Loja'
  const currentPlanName = props.currentPlan?.name || 'Gratuito'

  // Format price
  const price = formatPrice(intervalData.price, intervalName)
  const intervalText = intervalName === 'yearly' ? 'Anual' : 'Mensal'

  // Create WhatsApp message
  const message = `Olá! Gostaria de fazer upgrade do meu plano.\n\n` +
    `👤 Usuário: ${userName}\n` +
    `🏪 Loja: ${storeName}\n` +
    `📦 Plano Atual: ${currentPlanName}\n\n` +
    `🎯 Novo Plano Desejado:\n` +
    `   • ${plan.name}\n` +
    `   • ${intervalText}\n` +
    `   • ${price}\n\n` +
    `Por favor, me ajude com o processo de upgrade.`

  // Encode message and open WhatsApp
  const encodedMessage = encodeURIComponent(message)
  window.open(`https://wa.me/556231900204?text=${encodedMessage}`, '_blank')
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <main class="flex-1 p-6">
      <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
          <h1 class="text-2xl font-bold">
            {{ hasActivePlan ? 'Minha Assinatura' : 'Assine um Plano' }}
          </h1>
          <p class="text-gray-500">
            {{ hasActivePlan ? 'Gerencie sua assinatura e faça upgrade' : 'Escolha o plano ideal para suas necessidades' }}
          </p>
        </div>

        <!-- No Subscription Alert -->
        <Alert v-if="!hasActivePlan" variant="destructive" class="mb-8">
          <TriangleAlert class="h-4 w-4" />
          <AlertTitle>
            Você não possui um plano ativo
          </AlertTitle>
          <AlertDescription>
            Para utilizar o Tanavitrine, você precisa assinar um dos nossos planos. Escolha abaixo o que melhor se adequa às suas necessidades.
          </AlertDescription>
        </Alert>

        <!-- Current Plan Info -->
        <Card v-if="hasActivePlan" class="mb-8">
          <CardHeader>
            <div class="flex items-start justify-between">
              <div>
                <CardTitle class="flex items-center gap-2 text-xl">
                  <Crown class="h-5 w-5 text-yellow-500" />
                  {{ currentPlan.name }}
                </CardTitle>
                <CardDescription class="mt-1">
                  {{ currentPlan.description }}
                </CardDescription>
              </div>
              <div class="flex items-center gap-2 px-3 py-1 rounded-full" :class="currentPlan.subscription?.is_trial ? 'bg-yellow-50 text-yellow-700' : 'bg-green-50 text-green-700'">
                <Sparkles v-if="currentPlan.subscription?.is_trial" class="h-4 w-4" />
                <Check v-else class="h-4 w-4" />
                <span class="text-sm font-medium">{{ currentPlan.subscription?.is_trial ? 'Trial Grátis' : 'Ativo' }}</span>
              </div>
            </div>
          </CardHeader>

          <!-- Trial Alert -->
          <Alert v-if="currentPlan.subscription?.is_trial && currentPlan.subscription?.trial_days_remaining > 0" class="mx-6 mb-4 border-yellow-200 bg-yellow-50">
            <Sparkles class="h-4 w-4 text-yellow-600" />
            <AlertTitle class="text-yellow-900">
              Você está com acesso especial grátis! 🎉
            </AlertTitle>
            <AlertDescription class="text-yellow-800">
              Seu período gratuito termina em <strong>{{ currentPlan.subscription.trial_ends_at }}</strong>
              ({{ Math.ceil(currentPlan.subscription.trial_days_remaining) }} dias restantes).
              Aproveite todos os recursos do plano {{ currentPlan.name }}!
            </AlertDescription>
          </Alert>

          <CardContent>
            <div class="grid gap-6 md:grid-cols-3">
              <div class="space-y-2">
                <h4 class="text-sm font-medium text-muted-foreground">
                  Valor
                </h4>
                <p class="text-2xl font-bold">
                  <span v-if="currentPlan.subscription?.is_trial" class="text-green-600">Grátis</span>
                  <span v-else>{{ currentPlan.current_price ? formatPrice(currentPlan.current_price, currentPlan.current_interval?.toLowerCase().includes('anual') ? 'yearly' : 'monthly') : 'N/A' }}</span>
                </p>
                <p v-if="currentPlan.subscription?.is_trial" class="text-xs text-muted-foreground">
                  Depois: {{ currentPlan.current_price ? formatPrice(currentPlan.current_price, currentPlan.current_interval?.toLowerCase().includes('anual') ? 'yearly' : 'monthly') : 'N/A' }}
                </p>
              </div>

              <div class="space-y-2">
                <h4 class="text-sm font-medium text-muted-foreground">
                  Uso Atual
                </h4>
                <ul class="space-y-1">
                  <li v-for="limit in getPlanLimits()" :key="limit.name" class="text-sm">
                    {{ limit.name }}: <span class="font-medium">{{ limit.current }}/{{ limit.limit }}</span>
                  </li>
                </ul>
              </div>

              <div class="space-y-2">
                <h4 class="text-sm font-medium text-muted-foreground">
                  Recursos
                </h4>
                <ul class="space-y-1">
                  <li v-for="(feature, idx) in currentPlanStringFeatures.slice(0, 3)" :key="idx" class="flex items-center gap-2 text-sm">
                    <Check class="h-3 w-3 text-green-500 flex-shrink-0" />
                    <span>{{ feature }}</span>
                  </li>
                </ul>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Plans Section Header -->
        <div class="mb-6">
          <h2 class="text-xl font-bold mb-2">
            {{ hasActivePlan ? 'Fazer Upgrade do Plano' : 'Planos Disponíveis' }}
          </h2>
          <p class="text-gray-500">
            {{ hasActivePlan ? 'Aumente os limites e desbloqueie novos recursos' : 'Todos os planos incluem acesso completo à plataforma' }}
          </p>
        </div>

        <!-- Interval Selector - Only show if multiple intervals exist -->
        <Card v-if="hasMultipleIntervals" class="p-4 mb-6">
          <div class="flex justify-center">
            <Tabs v-model="selectedInterval" default-value="monthly" class="w-full max-w-md">
              <TabsList class="grid w-full grid-cols-2">
                <TabsTrigger value="monthly">
                  Mensal
                </TabsTrigger>
                <TabsTrigger value="yearly" class="flex items-center gap-2">
                  Anual
                  <span class="text-xs bg-green-500 text-white px-2 py-0.5 rounded">-20%</span>
                </TabsTrigger>
              </TabsList>
            </Tabs>
          </div>
        </Card>

        <!-- Plans Grid -->
        <div v-if="availablePlans.length > 0" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3 mb-8">
          <Card
            v-for="plan in availablePlans"
            :key="plan.id"
            class="flex flex-col relative overflow-hidden hover:shadow-lg transition-shadow"
            :class="{ 'border-primary border-2': plan.name.includes('Pro') }"
          >
            <!-- Popular Badge -->
            <div v-if="plan.name.includes('Pro')" class="absolute top-4 right-4 z-10">
              <div class="bg-primary text-primary-foreground px-3 py-1 rounded-full text-xs font-semibold flex items-center gap-1">
                <Sparkles class="h-3 w-3" />
                Popular
              </div>
            </div>

            <CardHeader>
              <CardTitle class="flex items-center gap-2 text-xl">
                <Zap class="h-5 w-5 text-primary" />
                {{ plan.name }}
              </CardTitle>
              <CardDescription class="mt-2 min-h-[3rem]">
                {{ plan.description }}
              </CardDescription>

              <!-- Price Display -->
              <div class="mt-4">

                <div v-if="plan.intervals[selectedInterval]" class="flex items-baseline gap-1">
                  <span class="text-4xl font-bold">
                    {{ formatPrice(plan.intervals[selectedInterval].price, selectedInterval).split('/')[0] }}
                  </span>
                  <span class="text-muted-foreground">
                    /{{ selectedInterval === 'yearly' ? 'ano' : 'mês' }}
                  </span>
                </div>
                <div v-else class="text-muted-foreground text-sm">
                  Intervalo não disponível ({{ selectedInterval }})
                </div>
              </div>
            </CardHeader>

            <CardContent class="flex-1">
              <Separator class="mb-4" />

              <!-- Features -->
              <div v-if="plan.features && plan.features.length > 0" class="space-y-3">
                <h4 class="text-sm font-semibold">
                  Recursos Incluídos
                </h4>
                <ul class="space-y-2">
                  <li
                    v-for="(feature, index) in plan.features"
                    :key="index"
                    class="flex items-start gap-2"
                  >
                    <Check class="h-4 w-4 text-green-500 mt-0.5 flex-shrink-0" />
                    <span class="text-sm">{{ feature }}</span>
                  </li>
                </ul>
              </div>
            </CardContent>

            <CardFooter>
              <Button
                class="w-full group"
                :disabled="!plan.intervals[selectedInterval]"
                @click="handleSubscribe(plan, selectedInterval)"
              >
                {{ hasActivePlan ? 'Fazer Upgrade' : 'Assinar Agora' }}
                <ArrowRight class="h-4 w-4 ml-2 group-hover:translate-x-1 transition-transform" />
              </Button>
            </CardFooter>
          </Card>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-12 bg-white rounded-lg border">
          <div class="mx-auto w-16 h-16 mb-4 bg-gray-100 rounded-full flex items-center justify-center">
            <TriangleAlert class="h-8 w-8 text-gray-400" />
          </div>
          <h3 class="text-lg font-medium text-gray-900">
            Nenhum plano disponível
          </h3>
          <p class="mt-2 text-gray-500">
            Entre em contato com o suporte para mais informações.
          </p>
        </div>

        <!-- Security Notice -->
        <Alert class="mt-8">
          <Lock class="h-4 w-4" />
          <AlertDescription>
            Pagamentos processados de forma segura. Seus dados estão protegidos.
          </AlertDescription>
        </Alert>
      </div>
    </main>
  </div>
</template>
