<script setup>
import StatCard from '@/Components/dashboard/StatCard.vue'
import { Button } from '@/Components/shadcn/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import { Badge } from '@/Components/shadcn/ui/badge'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, inject, ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/shadcn/ui/dialog'

const route = inject('route')

const props = defineProps({
  store: {
    type: Object,
    default: null
  },
  stats: {
    type: Object,
    default: () => ({
      total_views: 0,
      whatsapp_clicks: 0,
      website_clicks: 0,
      favorites_received: 0,
      views_trend: 0,
      clicks_trend: 0,
      favorites_trend: 0,
    })
  },
  plan: {
    type: Object,
    default: () => ({
      name: 'Gratuito',
      max_stores: 1,
      max_photos: 3,
      features: []
    })
  },
  welcomeDiscount: {
    type: Object,
    default: null
  },
})

const showWelcomeModal = ref(false)

onMounted(() => {
  if (props.welcomeDiscount) {
    setTimeout(() => {
      showWelcomeModal.value = true
    }, 800)
  }
})

const statCards = computed(() => [
  {
    title: 'Visualizações',
    value: props.stats.total_views,
    icon: 'lucide:eye',
    description: 'Total de visitas',
    trend: {
      isPositive: props.stats.views_trend > 0,
      value: Math.abs(props.stats.views_trend),
    },
  },
  {
    title: 'Cliques WhatsApp',
    value: props.stats.whatsapp_clicks,
    icon: 'lucide:message-circle',
    description: 'Total de cliques',
    trend: {
      isPositive: props.stats.clicks_trend > 0,
      value: Math.abs(props.stats.clicks_trend),
    },
  },
  {
    title: 'Cliques Site',
    value: props.stats.website_clicks,
    icon: 'lucide:globe',
    description: 'Total de cliques',
    trend: {
      isPositive: props.stats.clicks_trend > 0,
      value: Math.abs(props.stats.clicks_trend),
    },
  },
  {
    title: 'Favoritos',
    value: props.stats.favorites_received,
    icon: 'lucide:heart',
    description: 'Total recebidos',
    trend: {
      isPositive: props.stats.favorites_trend > 0,
      value: Math.abs(props.stats.favorites_trend),
    },
  },
])

function getStatusColor(status) {
  return {
    'ativo': 'default',
    'pendente': 'secondary',
    'inativo': 'destructive'
  }[status] || 'secondary'
}

const isTrialPlanAccess = computed(() => Boolean(props.plan?.subscription?.is_trial))
const hasCouponPlanAccess = computed(() => Boolean(props.plan?.subscription?.has_active_discount))
const hasSubscriptionData = computed(() => Boolean(props.plan?.subscription))
const isFormalizedPlanSubscription = computed(() => Boolean(props.plan?.subscription?.is_formalized))
const hasPaidContext = computed(() => {
  return String(props.plan?.name || '').toLowerCase() !== 'gratuito'
})
const isPendingSubscription = computed(() => {
  return props.store?.status === 'pendente'
    && hasPaidContext.value
    && (!hasSubscriptionData.value || Boolean(props.plan?.requires_payment))
})

const hasActivePlanAccess = computed(() => {
  const explicit = props.plan?.subscription?.has_active_access
  if (typeof explicit === 'boolean') {
    return explicit
  }

  return Boolean(props.plan?.subscription?.is_active)
})

const isPlanExpired = computed(() => Boolean(props.plan?.subscription) && !hasActivePlanAccess.value)

const isTopPaidPlan = computed(() => {
  return String(props.plan?.name || '').toLowerCase().includes('destaque')
})

const needsSubscriptionAction = computed(() => {
  if (!hasPaidContext.value) {
    return false
  }

  if (isPlanExpired.value) {
    return true
  }

  if (isPendingSubscription.value) {
    return true
  }

  if (!hasSubscriptionData.value) {
    return false
  }

  return !isFormalizedPlanSubscription.value
})

const dashboardPlanCtaLabel = computed(() => {
  if (isPendingSubscription.value) {
    return 'Concluir Assinatura'
  }

  if (needsSubscriptionAction.value) {
    return 'Assinar Agora'
  }

  if (isTopPaidPlan.value) {
    return 'Gerenciar Assinatura'
  }

  return 'Fazer Upgrade'
})

const showDashboardPlanCta = computed(() => {
  return needsSubscriptionAction.value || isFormalizedPlanSubscription.value || !isTopPaidPlan.value
})
</script>

<template>
  <Head title="Dashboard" />

  <AppLayout title="Dashboard">
    <div class="min-h-screen bg-gray-50">
      <div class="flex flex-1 flex-col gap-6 p-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between mt-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">
              Dashboard
            </h1>
            <p class="text-muted-foreground">
              Gerencie sua vitrine e acompanhe suas métricas
            </p>
          </div>
          <Button
            v-if="store && store.status === 'ativo'"
            :as="Link"
            :href="route('store.show', store.slug)"
            class="bg-teal-600 hover:bg-teal-700 mt-4 md:mt-0"
          >
            <Icon icon="lucide:store" class="mr-2 h-4 w-4" />
            Ver vitrine pública →
          </Button>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <StatCard
            v-for="card in statCards"
            :key="card.title"
            v-bind="card"
          />
        </div>

        <!-- Minha Vitrine -->
        <div v-if="store">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold">Minha Vitrine</h2>
          </div>

          <Card class="hover:shadow-lg transition-shadow">
            <CardHeader class="pb-3">
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <CardTitle class="text-xl">{{ store.name }}</CardTitle>
                  <div class="flex flex-wrap items-center gap-2 mt-2">
                    <Badge variant="outline" class="text-xs">
                      {{ store.category }}
                    </Badge>
                    <Badge
                      v-for="(subcategory, index) in (Array.isArray(store.subcategory) ? store.subcategory : [])"
                      :key="index"
                      variant="secondary"
                      class="text-xs"
                    >
                      {{ subcategory }}
                    </Badge>
                    <Badge variant="outline" class="text-xs">
                      {{ store.sale_type }}
                    </Badge>
                  </div>
                </div>
                <Badge :variant="getStatusColor(store.status)">
                  {{ store.status }}
                </Badge>
              </div>
              <p class="text-sm text-gray-600 mt-3 line-clamp-2">
                {{ store.description }}
              </p>
            </CardHeader>
            <CardContent>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                <Card class="cursor-pointer hover:shadow-lg transition-shadow" @click="router.visit(`/dashboard/stores/${store.slug}/edit`)">
                    <CardContent class="p-6 text-center">
                    <Icon icon="lucide:pencil-line" class="h-12 w-12 mx-auto mb-3 text-teal-600" />
                    <h3 class="font-semibold mb-1">Editar Vitrine</h3>
                    <p class="text-sm text-muted-foreground">Altere informações da sua loja</p>
                    </CardContent>
                </Card>

                <Card class="cursor-pointer hover:shadow-lg transition-shadow" @click="router.visit(`/dashboard/stores/${store.slug}/photos`)">
                    <CardContent class="p-6 text-center">
                    <Icon icon="lucide:image" class="h-12 w-12 mx-auto mb-3 text-teal-600" />
                    <h3 class="font-semibold mb-1">Fotos ({{ store.photos_count || 0 }})</h3>
                    <p class="text-sm text-muted-foreground">Gerencie as fotos da vitrine</p>
                    </CardContent>
                </Card>

                <Card class="cursor-pointer hover:shadow-lg transition-shadow" @click="router.visit(`/dashboard/stores/${store.slug}/analytics`)">
                    <CardContent class="p-6 text-center">
                    <Icon icon="lucide:bar-chart" class="h-12 w-12 mx-auto mb-3 text-teal-600" />
                    <h3 class="font-semibold mb-1">Analytics</h3>
                    <p class="text-sm text-muted-foreground">Veja métricas detalhadas</p>
                    </CardContent>
                </Card>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Plan Info -->
        <Card class="border-2 border-teal-200 bg-gradient-to-br from-white via-teal-50/30 to-orange-50/30 shadow-md hover:shadow-lg transition-all">
          <CardContent class="p-4">
            <div class="space-y-3">
              <!-- Header com título e botão -->
              <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                  <div class="w-10 h-10 bg-gradient-to-br from-teal-500 to-teal-600 rounded-lg flex items-center justify-center shadow-md">
                    <Icon icon="lucide:crown" class="h-5 w-5 text-white" />
                  </div>
                  <div class="flex items-center gap-2">
                    <h2 class="text-lg font-bold text-gray-900">
                      Plano {{ plan.name }}
                    </h2>
                    <Badge
                      v-if="plan.subscription || isPendingSubscription"
                      :variant="isPlanExpired || isPendingSubscription ? 'destructive' : (isFormalizedPlanSubscription ? 'secondary' : (isTrialPlanAccess ? 'default' : 'secondary'))"
                      class="text-xs"
                    >
                      {{ isPendingSubscription ? 'Pagamento pendente' : (isPlanExpired ? 'Expirado' : (isFormalizedPlanSubscription ? 'Ativo' : (isTrialPlanAccess ? 'Trial Grátis' : (hasCouponPlanAccess ? 'Cupom Ativo' : 'Ativo')))) }}
                    </Badge>
                  </div>
                </div>

                <Link v-if="showDashboardPlanCta" :href="route('subscriptions.index')">
                  <Button class="bg-gradient-to-r from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700 shadow-md hover:shadow-lg transition-all">
                    <Icon icon="lucide:sparkles" class="mr-2 h-4 w-4" />
                    {{ dashboardPlanCtaLabel }}
                  </Button>
                </Link>
                <p v-else class="text-sm font-medium text-teal-700">
                  Você já está no melhor plano
                </p>
              </div>

              <div
                v-if="isPendingSubscription"
                class="p-3 bg-amber-50 border-l-4 border-amber-400 rounded-r-lg"
              >
                <div class="flex items-start gap-2">
                  <Icon icon="lucide:alert-triangle" class="h-4 w-4 text-amber-600 flex-shrink-0 mt-0.5" />
                  <div>
                    <p class="text-sm font-semibold text-amber-900">
                      Sua assinatura ainda não foi concluída
                    </p>
                    <p class="text-xs text-amber-800 mt-1">
                      Sua vitrine está pendente e ainda não aparece no catálogo público. Clique em <strong>Concluir Assinatura</strong> para finalizar na Stripe.
                    </p>
                  </div>
                </div>
              </div>

              <div
                v-if="isPlanExpired"
                class="p-3 bg-red-50 border-l-4 border-red-400 rounded-r-lg"
              >
                <div class="flex items-start gap-2">
                  <Icon icon="lucide:alert-triangle" class="h-4 w-4 text-red-600 flex-shrink-0 mt-0.5" />
                  <div>
                    <p class="text-sm font-semibold text-red-900">
                      Sua vitrine está fora do ar
                    </p>
                    <p class="text-xs text-red-800 mt-1">
                      Você ainda tem acesso ao painel. Clique em <strong>Assinar Agora</strong> para reativar.
                    </p>
                  </div>
                </div>
              </div>

              <!-- Special Trial Message (100% Coupon) -->
              <div
                v-if="!isPlanExpired && !isFormalizedPlanSubscription && plan.subscription?.is_trial && plan.subscription?.trial_days_remaining > 0"
                class="p-3 bg-yellow-50 border-l-4 border-yellow-400 rounded-r-lg"
              >
                <div class="flex items-start gap-2">
                  <Icon icon="lucide:sparkles" class="h-4 w-4 text-yellow-600 flex-shrink-0 mt-0.5" />
                  <div>
                    <p class="text-sm font-semibold text-yellow-900">
                      Você está com acesso especial grátis! 🎉
                    </p>
                    <p class="text-xs text-yellow-800 mt-1">
                      Seu período gratuito termina em
                      <strong>{{ plan.subscription.trial_ends_at }}</strong>
                      ({{ Math.ceil(plan.subscription.trial_days_remaining) }} dias restantes)
                    </p>
                  </div>
                </div>
              </div>

              <div
                v-if="!isPlanExpired && isFormalizedPlanSubscription && (plan.subscription?.trial_days_remaining > 0 || plan.subscription?.discount_days_remaining > 0)"
                class="p-3 bg-emerald-50 border-l-4 border-emerald-400 rounded-r-lg"
              >
                <div class="flex items-start gap-2">
                  <Icon icon="lucide:check-circle" class="h-4 w-4 text-emerald-600 flex-shrink-0 mt-0.5" />
                  <div>
                    <p class="text-sm font-semibold text-emerald-900">
                      Assinatura ativa com benefício promocional
                    </p>
                    <p class="text-xs text-emerald-800 mt-1">
                      Sua assinatura já está formalizada. Benefício vigente até
                      <strong>{{ plan.subscription?.discount_ends_at || plan.subscription?.trial_ends_at }}</strong>.
                    </p>
                  </div>
                </div>
              </div>

              <!-- Usage Info -->
              <div class="bg-white/80 rounded-lg p-3 border border-gray-200">
                <div class="flex items-center justify-between mb-1.5">
                  <p class="text-sm font-medium text-gray-700">
                    Fotos por vitrine
                  </p>
                  <Badge variant="outline" class="text-xs">
                    {{ store?.photos_count || 0 }} / {{ plan.max_photos }}
                  </Badge>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                  <div
                    class="h-full rounded-full transition-all duration-300"
                    :class="{
                      'bg-teal-500': (store?.photos_count || 0) / plan.max_photos < 0.8,
                      'bg-orange-500': (store?.photos_count || 0) / plan.max_photos >= 0.8 && (store?.photos_count || 0) / plan.max_photos < 1,
                      'bg-red-500': (store?.photos_count || 0) / plan.max_photos >= 1
                    }"
                    :style="{ width: `${Math.min(100, ((store?.photos_count || 0) / plan.max_photos) * 100)}%` }"
                  />
                </div>
                <p class="text-xs text-gray-500 mt-1">
                  Você está usando {{ store?.photos_count || 0 }} de {{ plan.max_photos }} fotos disponíveis
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- Welcome Discount Modal -->
    <Dialog v-model:open="showWelcomeModal">
      <DialogContent class="max-w-md max-h-[90vh]">
        <DialogHeader class="space-y-1 pb-2">
          <div class="flex items-center justify-center mb-2">
            <div class="w-12 h-12 bg-gradient-to-br from-teal-400 to-orange-500 rounded-full flex items-center justify-center">
              <Icon icon="lucide:sparkles" class="h-6 w-6 text-white" />
            </div>
          </div>
          <DialogTitle class="text-center text-lg font-bold bg-gradient-to-r from-teal-600 to-orange-600 bg-clip-text text-transparent">
            Parabéns! Desconto Especial Ativo
          </DialogTitle>
          <DialogDescription class="text-center text-sm mt-1">
            Como novo usuário, você ganhou <span class="font-bold text-teal-600">{{ welcomeDiscount?.text }}</span> no plano <span class="font-bold">{{ welcomeDiscount?.plan_name }}</span>!
          </DialogDescription>
        </DialogHeader>

        <div class="py-3 space-y-3">
          <!-- Discount Info -->
          <div class="bg-gradient-to-br from-teal-50 to-orange-50 p-3 rounded-lg border border-teal-200">
            <div class="text-center mb-2">
              <p class="text-xl font-bold text-teal-700">
                {{ welcomeDiscount?.text }}
              </p>
            </div>
            <ul class="space-y-1.5 text-xs">
              <li class="flex items-start gap-2">
                <Icon icon="lucide:check-circle" class="h-4 w-4 text-green-600 flex-shrink-0 mt-0.5" />
                <span class="text-gray-700">Acesso completo a todos os recursos</span>
              </li>
              <li class="flex items-start gap-2">
                <Icon icon="lucide:check-circle" class="h-4 w-4 text-green-600 flex-shrink-0 mt-0.5" />
                <span class="text-gray-700">Sem compromisso - cancele quando quiser</span>
              </li>
              <li class="flex items-start gap-2">
                <Icon icon="lucide:check-circle" class="h-4 w-4 text-green-600 flex-shrink-0 mt-0.5" />
                <span class="text-gray-700">Configure sua vitrine e comece a vender!</span>
              </li>
            </ul>
          </div>

          <!-- Call to Action -->
          <div class="bg-white border border-teal-200 rounded-lg p-2.5 text-center">
            <p class="text-xs text-gray-600">
              Seu desconto já está ativo e aplicado à sua assinatura! Aproveite este período especial para explorar todos os recursos.
            </p>
          </div>
        </div>

        <DialogFooter class="flex justify-center pt-2">
          <Button
            size="sm"
            class="bg-gradient-to-r from-teal-500 to-orange-500 hover:from-teal-600 hover:to-orange-600 text-white font-semibold"
            @click="showWelcomeModal = false"
          >
            <Icon icon="lucide:rocket" class="mr-1.5 h-4 w-4" />
            Começar Agora!
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
