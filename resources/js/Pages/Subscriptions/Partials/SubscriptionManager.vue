<script setup>
import { Alert, AlertDescription, AlertTitle } from '@/Components/shadcn/ui/alert'
import { Button } from '@/Components/shadcn/ui/button'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/shadcn/ui/dialog'
import { Input } from '@/Components/shadcn/ui/input'
import { Label } from '@/Components/shadcn/ui/label'
import { Progress } from '@/Components/shadcn/ui/progress'
import { Separator } from '@/Components/shadcn/ui/separator'
import { Tabs, TabsList, TabsTrigger } from '@/Components/shadcn/ui/tabs'
import { __ } from '@/Composables/useTranslations.js'
import { Check, Crown, Lock, TriangleAlert, Zap, Sparkles, ArrowRight } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import axios from 'axios'
import { toast } from 'vue-sonner'

const processingCheckoutId = ref(null)
const couponApplying = ref(false)
const appliedCoupon = ref(null)
const couponError = ref('')
const couponNeedsRefresh = ref(false)
const cancelProcessing = ref(false)
const actionFeedback = ref(null)
const planChangeDialogOpen = ref(false)
const planChangeSubmitting = ref(false)
const pendingPlanChange = ref(null)
const cancelDialogOpen = ref(false)

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
const couponCode = ref('')
const selectedPlanId = ref(null)

// Check if user has an active paid plan (not the default free plan)
const hasActivePlan = computed(() => {
  return props.currentPlan && !props.currentPlan.is_default
})

const isTrialAccess = computed(() => {
  return Boolean(props.currentPlan?.subscription?.is_trial)
})

const hasCouponAccess = computed(() => {
  return Boolean(props.currentPlan?.subscription?.has_active_discount)
})

const hasSubscriptionMeta = computed(() => {
  return Boolean(props.currentPlan?.subscription)
})

const isFormalizedSubscription = computed(() => {
  return Boolean(props.currentPlan?.subscription?.is_formalized)
})

const isPendingCheckout = computed(() => {
  return Boolean(props.currentPlan?.requires_payment)
    || (props.currentPlan?.store_status === 'pendente' && !hasSubscriptionMeta.value)
})

const isCancellationScheduled = computed(() => {
  return Boolean(props.currentPlan?.subscription?.on_grace_period)
})

const hasActiveAccess = computed(() => {
  if (!hasActivePlan.value) {
    return false
  }

  if (isPendingCheckout.value) {
    return false
  }

  const value = props.currentPlan?.subscription?.has_active_access
  if (typeof value === 'boolean') {
    return value
  }

  return true
})

const isExpiredAccess = computed(() => {
  return hasActivePlan.value && props.currentPlan?.subscription && !hasActiveAccess.value
})

const needsSubscriptionAction = computed(() => {
  if (!hasActivePlan.value) {
    return false
  }

  if (isPendingCheckout.value) {
    return true
  }

  if (!hasActiveAccess.value) {
    return true
  }

  if (!hasSubscriptionMeta.value) {
    return false
  }

  return !isFormalizedSubscription.value
})

const planStatusText = computed(() => {
  if (isExpiredAccess.value) {
    return 'Expirado'
  }

  if (isCancellationScheduled.value) {
    return 'Cancelamento Agendado'
  }

  if (isPendingCheckout.value) {
    return 'Pagamento Pendente'
  }

  if (isFormalizedSubscription.value) {
    return 'Ativo'
  }

  if (isTrialAccess.value) {
    return 'Trial Grátis'
  }

  if (hasCouponAccess.value) {
    return 'Cupom Ativo'
  }

  return 'Ativo'
})

function getSortOrder(plan) {
  const value = Number(plan?.sort_order)
  return Number.isFinite(value) ? value : Number.MAX_SAFE_INTEGER
}

function getMinPlanPrice(plan) {
  const intervals = Array.isArray(plan?.intervals)
    ? plan.intervals
    : Object.values(plan?.intervals || {})

  if (intervals.length === 0) {
    return 0
  }

  return intervals.reduce((min, interval) => {
    const price = Number(interval?.price ?? interval?.pivot?.price ?? 0)
    return Math.min(min, Number.isFinite(price) ? price : 0)
  }, Number.POSITIVE_INFINITY)
}

function comparePlanOrder(a, b) {
  const sortDiff = getSortOrder(a) - getSortOrder(b)
  if (sortDiff !== 0) {
    return sortDiff
  }

  return getMinPlanPrice(a) - getMinPlanPrice(b)
}

function prioritizeCurrentPlan(plans) {
  if (!props.currentPlan?.id) {
    return plans
  }

  const current = plans.find((plan) => plan.id === props.currentPlan.id)
  if (!current) {
    return plans
  }

  const others = plans.filter((plan) => plan.id !== props.currentPlan.id)
  return [current, ...others]
}

// Get only string features (filter out analytics object)
const currentPlanStringFeatures = computed(() => {
  if (!props.currentPlan?.features) return []

  return Object.values(props.currentPlan.features).filter(feature => typeof feature === 'string')
})

function normalizePlanName(value) {
  return String(value || '')
    .trim()
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036F]/g, '')
}

const summarizedPlanFeatures = {
  vitrine: [
    'Exibição padrão nas buscas e listagens, sem presença no mapa e sem selo de verificação.',
    'Conteúdo e mídia: 10 fotos de destaque, até 2 coleções e 1 link de vídeo da loja.',
    'Analytics básico: visualizações da vitrine e cliques no WhatsApp.',
    'Canais de contato: WhatsApp e Instagram.',
    'Identidade da loja: descrição completa, categoria/subcategorias, logo e informações principais.',
  ],
  destaque: [
    'Exposição premium: prioridade nas listagens, home e mapa de destaque.',
    'Credibilidade: badge visual de destaque e elegibilidade ao selo de loja verificada.',
    'Conteúdo e mídia: 20 fotos destaque, até 5 coleções, 1 vídeo na vitrine e até 2 vídeos nas coleções.',
    'Analytics completo: vitrine, WhatsApp, site, Ver Localização, compartilhamentos e redes sociais.',
    'Canais de contato completos: WhatsApp, Instagram, Site, Facebook e TikTok.',
    'Identidade completa da loja: descrição, categoria/subcategorias, logo e informações principais.',
  ],
}

const currentPlanDisplayFeatures = computed(() => {
  const normalizedPlanName = normalizePlanName(props.currentPlan?.name)

  if (normalizedPlanName.includes('vitrine')) {
    return summarizedPlanFeatures.vitrine
  }

  if (normalizedPlanName.includes('destaque')) {
    return summarizedPlanFeatures.destaque
  }

  return currentPlanStringFeatures.value
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

function formatCurrencyValue(value) {
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  }).format(Number(value || 0))
}

function formatDurationText(value, unit) {
  const normalizedValue = Number(value || 0)

  if (normalizedValue <= 0 || !unit) {
    return null
  }

  if (unit === 'days') {
    return `${normalizedValue} dia${normalizedValue > 1 ? 's' : ''}`
  }

  if (unit === 'months') {
    return `${normalizedValue} mes${normalizedValue > 1 ? 'es' : ''}`
  }

  if (unit === 'years') {
    return `${normalizedValue} ano${normalizedValue > 1 ? 's' : ''}`
  }

  return null
}

function formatDateBr(date) {
  return new Intl.DateTimeFormat('pt-BR').format(date)
}

function addDurationToDate(baseDate, value, unit) {
  const date = new Date(baseDate)
  const normalizedValue = Number(value || 0)

  if (normalizedValue <= 0) {
    return date
  }

  if (unit === 'days') {
    date.setDate(date.getDate() + normalizedValue)
    return date
  }

  if (unit === 'months') {
    date.setMonth(date.getMonth() + normalizedValue)
    return date
  }

  if (unit === 'years') {
    date.setFullYear(date.getFullYear() + normalizedValue)
    return date
  }

  return date
}

const LIMIT_LABELS = {
  collections_per_vitrine: 'Coleções por vitrine',
  photos_per_collection: 'Fotos por coleção',
  photos_per_vitrine: 'Fotos destaque por vitrine',
  videos_per_collection: 'Vídeos por coleção',
  videos_per_vitrine: 'Vídeos por vitrine',
  products_per_store: 'Produtos na vitrine',
  featured_days: 'Dias em destaque',
}

function formatLimitLabel(key) {
  if (LIMIT_LABELS[key]) {
    return LIMIT_LABELS[key]
  }

  return key
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (letter) => letter.toUpperCase())
}

function formatLimitValue(value) {
  const numericValue = Number(value)

  if (!Number.isFinite(numericValue)) {
    return '0'
  }

  if (numericValue < 0) {
    return 'Sem limite'
  }

  return String(numericValue)
}

function getPlanLimits(planLimits = null) {
  const limitsToUse = planLimits || props.limits
  if (!limitsToUse) return []

  return Object.entries(limitsToUse).map(([key, value]) => {
    const current = Number(props.usage?.[key] ?? 0)

    return {
      key,
      name: formatLimitLabel(key),
      limit: value,
      current,
      limitLabel: formatLimitValue(value),
      currentLabel: Number.isFinite(current) ? String(current) : '0',
    }
  })
}

function getLimitProgress(limitItem) {
  const current = Number(limitItem?.current ?? 0)
  const limit = Number(limitItem?.limit ?? 0)

  if (!Number.isFinite(current) || !Number.isFinite(limit) || limit <= 0) {
    return 0
  }

  return Math.max(0, Math.min(100, Math.round((current / limit) * 100)))
}

function getLimitStatusText(limitItem) {
  const limit = Number(limitItem?.limit ?? 0)

  if (!Number.isFinite(limit) || limit < 0) {
    return 'Uso livre'
  }

  if (limit === 0) {
    return 'Indisponivel neste plano'
  }

  const progress = getLimitProgress(limitItem)

  if (progress >= 90) {
    return 'Proximo do limite'
  }

  if (progress >= 70) {
    return 'Uso moderado'
  }

  return 'Dentro do limite'
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

const groupedPaidPlans = computed(() => {
  if (!props.plans || props.plans.length === 0) return []

  // Group plans by name and create intervals structure
  const planGroups = {}

  props.plans.forEach(plan => {
    // Skip inactive/default/free plans
    if (
      !plan.is_active ||
      plan.is_default ||
      plan.name.toLowerCase().includes('gratuito') ||
      plan.name.toLowerCase().includes('free')
    ) {
      return
    }

    if (!planGroups[plan.name]) {
      planGroups[plan.name] = {
        id: plan.id,
        name: plan.name,
        description: plan.description,
        sort_order: plan.sort_order,
        features: getStringFeatures(plan.features),
        limits: plan.limits || [],
        intervals: {},
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
          code: interval.code,
        }

        planGroups[plan.name].intervals[intervalKey] = intervalData
      })
    }
  })

  return Object.values(planGroups).sort(comparePlanOrder)
})

const recommendedPlan = computed(() => {
  if (!hasActivePlan.value || needsSubscriptionAction.value || !props.currentPlan) {
    return null
  }

  const currentIndex = groupedPaidPlans.value.findIndex(plan => plan.id === props.currentPlan.id)
  if (currentIndex === -1) {
    return null
  }

  return groupedPaidPlans.value[currentIndex + 1] || null
})

const isOnBestPlan = computed(() => {
  return hasActivePlan.value && !needsSubscriptionAction.value && !recommendedPlan.value
})

const availablePlans = computed(() => {
  if (!hasActivePlan.value) {
    return groupedPaidPlans.value
  }

  // Trial/coupon: permitir assinatura de qualquer plano (inclusive o atual)
  if (needsSubscriptionAction.value) {
    return prioritizeCurrentPlan(groupedPaidPlans.value)
  }

  if (!props.currentPlan) {
    return groupedPaidPlans.value
  }

  // Assinatura ativa formalizada: mostrar migração (upgrade/downgrade), excluindo o plano atual.
  return groupedPaidPlans.value.filter(plan => plan.id !== props.currentPlan.id)
})

const hasPlanChangeOptions = computed(() => {
  return hasActivePlan.value && !needsSubscriptionAction.value && availablePlans.value.length > 0
})

const selectedPlan = computed(() => {
  if (!availablePlans.value || availablePlans.value.length === 0) {
    return null
  }

  if (selectedPlanId.value) {
    const found = availablePlans.value.find(plan => plan.id === selectedPlanId.value)
    if (found) {
      return found
    }
  }

  return availablePlans.value[0] || null
})

const couponPreviewPlan = computed(() => selectedPlan.value)

const selectedPlanIntervalData = computed(() => {
  const plan = selectedPlan.value
  if (!plan?.intervals) {
    return null
  }

  if (!plan.intervals[selectedInterval.value]) {
    return null
  }

  return {
    ...plan.intervals[selectedInterval.value],
    key: selectedInterval.value,
  }
})

const couponPreviewIntervalData = computed(() => selectedPlanIntervalData.value)

const selectedPlanIntervalLabel = computed(() => {
  if (!selectedPlanIntervalData.value) {
    return '-'
  }

  return selectedPlanIntervalData.value.key === 'yearly' ? 'anual' : 'mensal'
})

const selectedPlanPriceLabel = computed(() => {
  if (!selectedPlanIntervalData.value) {
    return '-'
  }

  return formatCurrencyValue(selectedPlanIntervalData.value.price)
})

const selectedPlanHasChosenInterval = computed(() => {
  return Boolean(selectedPlan.value?.intervals?.[selectedInterval.value])
})

const selectedPlanCheckoutBusy = computed(() => {
  return processingCheckoutId.value !== null && processingCheckoutId.value === selectedPlanIntervalData.value?.id
})

const selectedPlanChangeType = computed(() => {
  if (!hasActivePlan.value || needsSubscriptionAction.value || !props.currentPlan || !selectedPlan.value) {
    return 'subscribe'
  }

  const diff = comparePlanOrder(selectedPlan.value, props.currentPlan)
  if (diff > 0) {
    return 'upgrade'
  }

  if (diff < 0) {
    return 'downgrade'
  }

  return 'current'
})

const selectedPlanCheckoutDisabled = computed(() => {
  return !selectedPlanHasChosenInterval.value || selectedPlanCheckoutBusy.value || selectedPlanChangeType.value === 'current'
})

const subscribeButtonLabel = computed(() => {
  if (!selectedPlanHasChosenInterval.value) {
    return 'Intervalo indisponível'
  }

  if (isPendingCheckout.value) {
    return 'Concluir Assinatura'
  }

  if (needsSubscriptionAction.value) {
    return 'Assinar Agora'
  }

  if (selectedPlanChangeType.value === 'downgrade') {
    return 'Fazer Downgrade'
  }

  if (selectedPlanChangeType.value === 'upgrade') {
    return 'Fazer Upgrade'
  }

  if (selectedPlanChangeType.value === 'current') {
    return 'Plano atual'
  }

  return 'Assinar Agora'
})

const planSelectionHelperText = computed(() => {
  if (!selectedPlan.value) {
    return 'Escolha um plano para continuar.'
  }

  if (!selectedPlanHasChosenInterval.value) {
    return `Plano selecionado: ${selectedPlan.value.name} (sem opção ${selectedInterval.value === 'yearly' ? 'anual' : 'mensal'}).`
  }

  return `Plano selecionado: ${selectedPlan.value.name} (${selectedPlanIntervalLabel.value})`
})

const couponApplyDisabled = computed(() => {
  return couponApplying.value || couponCode.value.trim().length === 0 || !couponPreviewIntervalData.value
})

const couponApplyButtonLabel = computed(() => {
  return couponApplying.value ? 'Aplicando...' : 'Aplicar cupom'
})

const appliedCouponOfferText = computed(() => {
  if (!appliedCoupon.value) {
    return ''
  }

  const coupon = appliedCoupon.value

  if (coupon.is_special) {
    const durationText = formatDurationText(coupon.duration_value, coupon.duration_unit)
    return durationText ? `100% de desconto por ${durationText}` : '100% de desconto'
  }

  if (coupon.type === 'percentage') {
    return `${Number(coupon.discount_percentage || 0)}% de desconto`
  }

  return `${formatCurrencyValue(coupon.discount_amount)} de desconto`
})

const couponPreviewContextText = computed(() => {
  if (!appliedCoupon.value || !couponPreviewPlan.value || !couponPreviewIntervalData.value) {
    return ''
  }

  const intervalLabel = couponPreviewIntervalData.value.key === 'yearly' ? 'anual' : 'mensal'
  return `Plano ${couponPreviewPlan.value.name} (${intervalLabel})`
})

const couponBillingForecast = computed(() => {
  if (!appliedCoupon.value || !couponPreviewIntervalData.value) {
    return null
  }

  const coupon = appliedCoupon.value
  const durationValue = Number(coupon.duration_value || 0)
  const durationUnit = String(coupon.duration_unit || '')

  if (coupon.is_special && durationValue > 0 && durationUnit) {
    const nextChargeDate = addDurationToDate(new Date(), durationValue, durationUnit)

    return {
      nextChargeDateText: formatDateBr(nextChargeDate),
      nextChargeAmountText: formatCurrencyValue(couponPreviewIntervalData.value.price),
      durationText: formatDurationText(durationValue, durationUnit),
    }
  }

  return null
})

const plansSectionTitle = computed(() => {
  if (!hasActivePlan.value) {
    return 'Planos Disponíveis'
  }

  if (isExpiredAccess.value) {
    return 'Assinar Agora'
  }

  if (needsSubscriptionAction.value) {
    return 'Ativar Assinatura'
  }

  if (isOnBestPlan.value) {
    return 'Gerenciar Assinatura'
  }

  return 'Migrar Plano'
})

const plansSectionDescription = computed(() => {
  if (!hasActivePlan.value) {
    return 'Todos os planos incluem acesso completo à plataforma'
  }

  if (isExpiredAccess.value) {
    return 'Seu período de acesso terminou e sua vitrine está fora do ar. Assine agora para reativar.'
  }

  if (isPendingCheckout.value) {
    return 'Sua vitrine está pendente e ainda não aparece no catálogo. Conclua a assinatura para publicá-la.'
  }

  if (needsSubscriptionAction.value) {
    return 'Seu acesso atual é promocional. Escolha um plano para formalizar sua assinatura.'
  }

  if (isOnBestPlan.value) {
    return 'Sua assinatura já está ativa no melhor plano. Se quiser, você pode migrar para um plano inferior.'
  }

  return 'Escolha entre upgrade ou downgrade conforme o momento da sua loja.'
})

const ctaLabel = computed(() => {
  if (needsSubscriptionAction.value) {
    return isPendingCheckout.value ? 'Concluir Assinatura' : 'Assinar Agora'
  }

  if (hasPlanChangeOptions.value) {
    return 'Gerenciar Plano'
  }

  return 'Escolher Plano'
})

const currentStatusText = computed(() => {
  if (isExpiredAccess.value) {
    return 'Expirado'
  }

  if (isCancellationScheduled.value) {
    return 'Cancelamento agendado'
  }

  if (isPendingCheckout.value) {
    return 'Pagamento pendente'
  }

  if (isFormalizedSubscription.value) {
    return 'Ativo'
  }

  if (isTrialAccess.value) {
    return 'Trial em andamento'
  }

  if (hasCouponAccess.value) {
    return 'Promocional'
  }

  return 'Ativo'
})

const accessUntilText = computed(() => {
  if (isCancellationScheduled.value && props.currentPlan?.subscription?.ends_at) {
    return props.currentPlan.subscription.ends_at
  }

  if (isTrialAccess.value && props.currentPlan?.subscription?.trial_ends_at) {
    return props.currentPlan.subscription.trial_ends_at
  }

  if (hasCouponAccess.value && props.currentPlan?.subscription?.discount_ends_at) {
    return props.currentPlan.subscription.discount_ends_at
  }

  if (isExpiredAccess.value) {
    return 'Acesso encerrado'
  }

  if (isPendingCheckout.value) {
    return 'Aguardando assinatura'
  }

  return 'Sem data definida'
})

const nextActionText = computed(() => {
  if (isCancellationScheduled.value) {
    return 'Reativar assinatura'
  }

  if (needsSubscriptionAction.value) {
    return isPendingCheckout.value ? 'Finalizar pagamento' : 'Concluir assinatura'
  }

  if (hasPlanChangeOptions.value) {
    return 'Gerenciar plano'
  }

  return 'Manter plano atual'
})

const nextStepTitle = computed(() => {
  if (!hasActivePlan.value) {
    return 'Escolha o plano ideal para começar'
  }

  if (isExpiredAccess.value) {
    return 'Reative sua vitrine'
  }

  if (isPendingCheckout.value) {
    return 'Conclua sua assinatura'
  }

  if (isCancellationScheduled.value) {
    return 'Cancelamento agendado'
  }

  if (needsSubscriptionAction.value) {
    return 'Formalize sua assinatura'
  }

  if (hasPlanChangeOptions.value) {
    return 'Gerencie sua assinatura'
  }

  return 'Plano ativo'
})

const nextStepDescription = computed(() => {
  if (!hasActivePlan.value) {
    return 'Selecione um plano e siga para o checkout para ativar sua vitrine.'
  }

  if (isExpiredAccess.value) {
    return 'Seu acesso venceu e sua vitrine está fora do ar. Assine agora para voltar a aparecer no catálogo.'
  }

  if (isPendingCheckout.value) {
    return 'Sua vitrine está pendente e só ficará pública após a confirmação da assinatura na Stripe.'
  }

  if (isCancellationScheduled.value) {
    return `Sua assinatura segue ativa até ${props.currentPlan?.subscription?.ends_at || 'o fim do ciclo atual'}.`
  }

  if (needsSubscriptionAction.value) {
    return 'Seu acesso atual é promocional. Conclua a assinatura para garantir continuidade sem interrupções.'
  }

  if (hasPlanChangeOptions.value) {
    return 'Sua assinatura está ativa. Você pode fazer upgrade ou downgrade de acordo com sua estratégia.'
  }

  return 'Tudo certo com sua assinatura atual.'
})

const showNextStepButton = computed(() => needsSubscriptionAction.value || hasPlanChangeOptions.value || !hasActivePlan.value)
const showCouponSection = computed(() => !hasActivePlan.value || needsSubscriptionAction.value)
const cancellationButtonLabel = computed(() => isCancellationScheduled.value ? 'Reativar assinatura' : 'Cancelar assinatura')
const cancellationButtonVariant = computed(() => isCancellationScheduled.value ? 'outline' : 'destructive')
const cancellationButtonBusyLabel = computed(() => isCancellationScheduled.value ? 'Reativando...' : 'Cancelando...')
const reactivationDeadlineText = computed(() => {
  return props.currentPlan?.subscription?.ends_at || null
})
const cancelDialogTitle = computed(() => isCancellationScheduled.value ? 'Reativar assinatura' : 'Confirmar cancelamento')
const cancelDialogDescription = computed(() => {
  if (isCancellationScheduled.value) {
    return 'Deseja remover o cancelamento agendado? Sua assinatura continuará ativa normalmente.'
  }

  return 'Deseja agendar o cancelamento da assinatura no fim do ciclo atual? Sua vitrine continuará ativa até a data final.'
})
const cancelDialogConfirmLabel = computed(() => {
  if (cancelProcessing.value) {
    return cancellationButtonBusyLabel.value
  }

  return isCancellationScheduled.value ? 'Reativar assinatura' : 'Agendar cancelamento'
})

const confirmationStepTitle = computed(() => {
  if (showCouponSection.value) {
    return '3. Confirmar assinatura'
  }

  return '2. Confirmar alteração de plano'
})

const confirmationSummaryText = computed(() => {
  if (!selectedPlan.value) {
    return ''
  }

  if (!selectedPlanHasChosenInterval.value) {
    return `Plano: ${selectedPlan.value.name} (intervalo indisponível).`
  }

  const base = `Plano: ${selectedPlan.value.name} (${selectedPlanIntervalLabel.value}) - ${selectedPlanPriceLabel.value}.`

  if (selectedPlanChangeType.value === 'upgrade') {
    return `${base} Você fará um upgrade mantendo sua assinatura ativa.`
  }

  if (selectedPlanChangeType.value === 'downgrade') {
    return `${base} Você fará um downgrade mantendo sua assinatura ativa.`
  }

  return base
})

const planChangeDialogTitle = computed(() => {
  if (!pendingPlanChange.value) {
    return 'Confirmar alteração de plano'
  }

  if (pendingPlanChange.value.changeType === 'upgrade') {
    return 'Confirmar upgrade de plano'
  }

  if (pendingPlanChange.value.changeType === 'downgrade') {
    return 'Confirmar downgrade de plano'
  }

  return 'Confirmar alteração de plano'
})

const planChangeDialogConfirmLabel = computed(() => {
  if (!pendingPlanChange.value) {
    return 'Confirmar alteração'
  }

  if (planChangeSubmitting.value) {
    if (pendingPlanChange.value.changeType === 'upgrade') {
      return 'Aplicando upgrade...'
    }

    if (pendingPlanChange.value.changeType === 'downgrade') {
      return 'Aplicando downgrade...'
    }

    return 'Aplicando alteração...'
  }

  if (pendingPlanChange.value.changeType === 'upgrade') {
    return 'Confirmar upgrade'
  }

  if (pendingPlanChange.value.changeType === 'downgrade') {
    return 'Confirmar downgrade'
  }

  return 'Confirmar alteração'
})

const planChangeDialogDescription = computed(() => {
  if (!pendingPlanChange.value) {
    return ''
  }

  const fromPlan = props.currentPlan?.name || 'Plano atual'
  const toPlan = pendingPlanChange.value.plan?.name || 'Novo plano'
  const intervalLabel = pendingPlanChange.value.intervalLabel || 'mensal'
  const priceLabel = pendingPlanChange.value.priceLabel || '-'

  if (pendingPlanChange.value.changeType === 'upgrade') {
    return `Você vai migrar de ${fromPlan} para ${toPlan} (${intervalLabel}, ${priceLabel}). A cobrança será ajustada no próximo ciclo.`
  }

  if (pendingPlanChange.value.changeType === 'downgrade') {
    return `Você vai migrar de ${fromPlan} para ${toPlan} (${intervalLabel}, ${priceLabel}). A cobrança será ajustada no próximo ciclo.`
  }

  return `Você vai alterar seu plano para ${toPlan} (${intervalLabel}, ${priceLabel}).`
})

function syncSelectedPlan() {
  const plans = availablePlans.value

  if (!plans || plans.length === 0) {
    selectedPlanId.value = null
    return
  }

  if (!selectedPlanId.value || !plans.some(plan => plan.id === selectedPlanId.value)) {
    selectedPlanId.value = plans[0].id
  }
}

function selectPlan(planId) {
  if (selectedPlanId.value === planId) {
    return
  }

  selectedPlanId.value = planId
}

function isPlanSelected(plan) {
  return selectedPlanId.value === plan.id
}

function normalizeCouponCode(value) {
  return value.trim().toUpperCase()
}

function setActionFeedback(type, title, description) {
  actionFeedback.value = { type, title, description }
}

function openPlanChangeDialog(plan, intervalName) {
  const intervalData = plan?.intervals?.[intervalName]
  if (!plan || !intervalData) {
    return
  }

  pendingPlanChange.value = {
    plan,
    intervalName,
    intervalLabel: intervalName === 'yearly' ? 'anual' : 'mensal',
    priceLabel: formatCurrencyValue(intervalData.price),
    changeType: selectedPlanChangeType.value,
  }

  planChangeDialogOpen.value = true
}

function closePlanChangeDialog() {
  if (planChangeSubmitting.value) {
    return
  }

  planChangeDialogOpen.value = false
  pendingPlanChange.value = null
}

function openCancellationDialog() {
  if (!isFormalizedSubscription.value || needsSubscriptionAction.value || cancelProcessing.value) {
    return
  }

  cancelDialogOpen.value = true
}

function closeCancellationDialog() {
  if (cancelProcessing.value) {
    return
  }

  cancelDialogOpen.value = false
}

async function applyCoupon() {
  if (!showCouponSection.value) {
    return
  }

  const normalizedCode = normalizeCouponCode(couponCode.value)
  const intervalData = couponPreviewIntervalData.value

  if (!normalizedCode || !intervalData) {
    return
  }

  couponApplying.value = true
  couponError.value = ''

  try {
    const response = await axios.post('/api/coupons/validate', {
      code: normalizedCode,
      plan_price: Number(intervalData.price || 0),
    })

    const coupon = response?.data?.coupon

    if (!coupon) {
      throw new Error('Resposta inválida ao validar cupom')
    }

    couponCode.value = normalizedCode
    appliedCoupon.value = coupon
    couponNeedsRefresh.value = false
  } catch (error) {
    appliedCoupon.value = null
    couponNeedsRefresh.value = false
    couponError.value = error?.response?.data?.message || 'Não foi possível validar este cupom agora.'
  } finally {
    couponApplying.value = false
  }
}

watch(availablePlans, () => {
  syncSelectedPlan()
}, { immediate: true })

watch([selectedInterval, selectedPlanId], () => {
  if (appliedCoupon.value) {
    couponNeedsRefresh.value = true
  }
})

watch(couponCode, (newValue) => {
  const normalizedCode = normalizeCouponCode(newValue)
  couponError.value = ''

  if (!normalizedCode) {
    appliedCoupon.value = null
    couponNeedsRefresh.value = false
    return
  }

  if (appliedCoupon.value && normalizedCode !== String(appliedCoupon.value.code || '').toUpperCase()) {
    appliedCoupon.value = null
    couponNeedsRefresh.value = false
  }
})

watch(selectedPlanIntervalData, (newValue) => {
  if (!newValue) {
    appliedCoupon.value = null
    couponNeedsRefresh.value = false
  }
})

watch(planChangeDialogOpen, (isOpen) => {
  if (!isOpen && !planChangeSubmitting.value) {
    pendingPlanChange.value = null
  }
})

function scrollToPlans() {
  const section = document.getElementById('plans-list')
  if (!section) {
    return
  }

  section.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

async function handleSubscribe(plan, intervalName) {
  const intervalData = plan.intervals[intervalName]
  if (!intervalData || !intervalData.id) {
    console.error('Invalid interval data', { plan, intervalName, intervalData })
    return
  }

  processingCheckoutId.value = intervalData.id

  try {
    const formattedCoupon = normalizeCouponCode(couponCode.value)
    const payload = formattedCoupon ? { coupon_code: formattedCoupon } : {}

    const response = await axios.post(`/subscriptions/checkout/${intervalData.id}`, payload)
    const checkoutUrl = response?.data?.checkout_url

    if (!checkoutUrl) {
      throw new Error('Checkout URL not found')
    }

    window.location.href = checkoutUrl
  } catch (error) {
    console.error('Error starting checkout:', error)

    const message = error?.response?.data?.message || 'Não foi possível iniciar o checkout agora. Tente novamente.'
    window.alert(message)
    processingCheckoutId.value = null
  }
}

async function handlePlanChange(plan, intervalName) {
  const intervalData = plan.intervals[intervalName]
  if (!intervalData || !intervalData.id) {
    console.error('Invalid interval data', { plan, intervalName, intervalData })
    return
  }

  processingCheckoutId.value = intervalData.id

  try {
    const response = await axios.post(`/subscriptions/change-plan/${intervalData.id}`)
    const changeType = response?.data?.change_type || selectedPlanChangeType.value
    const fromPlan = response?.data?.from_plan || props.currentPlan?.name || 'plano atual'
    const toPlan = response?.data?.to_plan || plan.name
    const nextBillingDate = response?.data?.next_billing_date

    let title = 'Plano alterado com sucesso'
    if (changeType === 'upgrade') {
      title = 'Upgrade realizado com sucesso'
    } else if (changeType === 'downgrade') {
      title = 'Downgrade realizado com sucesso'
    }

    let description = `Migração de ${fromPlan} para ${toPlan} concluída.`
    if (nextBillingDate) {
      description += ` A cobrança do novo plano será no próximo ciclo (${nextBillingDate}).`
    } else {
      description += ' A cobrança do novo plano será aplicada no próximo ciclo.'
    }

    setActionFeedback('success', title, description)
    toast.success(title, { description })
    planChangeDialogOpen.value = false
    pendingPlanChange.value = null

    setTimeout(() => {
      window.location.reload()
    }, 2000)
  } catch (error) {
    console.error('Error changing plan:', error)

    const message = error?.response?.data?.message || 'Não foi possível alterar o plano agora. Tente novamente.'
    setActionFeedback('error', 'Não foi possível alterar o plano', message)
    toast.error('Não foi possível alterar o plano', { description: message })
    processingCheckoutId.value = null
    planChangeDialogOpen.value = false
    pendingPlanChange.value = null
  }
}

async function confirmPlanChange() {
  if (!pendingPlanChange.value) {
    return
  }

  planChangeSubmitting.value = true

  try {
    await handlePlanChange(pendingPlanChange.value.plan, pendingPlanChange.value.intervalName)
  } finally {
    planChangeSubmitting.value = false
  }
}

async function toggleSubscriptionCancellation() {
  if (!isFormalizedSubscription.value || needsSubscriptionAction.value) {
    return
  }

  const isResume = isCancellationScheduled.value

  cancelProcessing.value = true

  try {
    const endpoint = isResume ? '/subscriptions/resume-plan' : '/subscriptions/cancel-plan'
    const response = await axios.post(endpoint)

    if (isResume) {
      setActionFeedback(
        'success',
        'Assinatura reativada',
        response?.data?.message || 'Cancelamento removido. Sua assinatura segue ativa.'
      )
      toast.success('Assinatura reativada', {
        description: response?.data?.message || 'Cancelamento removido. Sua assinatura segue ativa.',
      })
    } else {
      const endsAt = response?.data?.ends_at
      const detail = endsAt
        ? `Sua vitrine permanece ativa até ${endsAt}.`
        : 'Sua assinatura continuará ativa até o fim do ciclo atual.'

      setActionFeedback(
        'info',
        'Cancelamento agendado',
        `${response?.data?.message || 'Cancelamento agendado com sucesso.'} ${detail}`
      )
      toast.success('Cancelamento agendado', {
        description: response?.data?.message || detail,
      })
    }

    cancelDialogOpen.value = false
    setTimeout(() => {
      window.location.reload()
    }, 1400)
  } catch (error) {
    const message = error?.response?.data?.message || 'Não foi possível concluir essa ação agora.'
    setActionFeedback('error', 'Falha ao atualizar assinatura', message)
    toast.error('Falha ao atualizar assinatura', { description: message })
    cancelProcessing.value = false
    cancelDialogOpen.value = false
  }
}

async function subscribeSelectedPlan() {
  if (!selectedPlan.value || !selectedPlanIntervalData.value) {
    return
  }

  if (isFormalizedSubscription.value && !needsSubscriptionAction.value) {
    openPlanChangeDialog(selectedPlan.value, selectedPlanIntervalData.value.key)
    return
  }

  await handleSubscribe(selectedPlan.value, selectedPlanIntervalData.value.key)
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
            {{ hasActivePlan ? 'Gerencie sua assinatura e próximos passos do seu plano' : 'Escolha o plano ideal para suas necessidades' }}
          </p>
        </div>

        <Card class="mb-8 border-teal-200 bg-gradient-to-r from-teal-50 to-emerald-50">
          <CardContent class="p-5">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
              <div class="space-y-1">
                <p class="text-sm font-semibold text-teal-700">Próximo passo</p>
                <h2 class="text-xl font-bold text-slate-900">{{ nextStepTitle }}</h2>
                <p class="text-sm text-slate-600">{{ nextStepDescription }}</p>
              </div>
              <Button v-if="showNextStepButton" class="w-full md:w-auto" @click="scrollToPlans">
                {{ ctaLabel }}
                <ArrowRight class="h-4 w-4 ml-2" />
              </Button>
            </div>
          </CardContent>
        </Card>

        <Alert
          v-if="actionFeedback"
          class="mb-6"
          :variant="actionFeedback.type === 'error' ? 'destructive' : 'default'"
          :class="actionFeedback.type === 'success'
            ? 'border-emerald-200 bg-emerald-50'
            : (actionFeedback.type === 'info' ? 'border-blue-200 bg-blue-50' : '')"
        >
          <Check v-if="actionFeedback.type !== 'error'" class="h-4 w-4" />
          <TriangleAlert v-else class="h-4 w-4" />
          <AlertTitle>{{ actionFeedback.title }}</AlertTitle>
          <AlertDescription>{{ actionFeedback.description }}</AlertDescription>
        </Alert>

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
              <div
                class="flex items-center gap-2 px-3 py-1 rounded-full"
                :class="isExpiredAccess
                  ? 'bg-red-50 text-red-700'
                  : (isCancellationScheduled
                      ? 'bg-amber-50 text-amber-700'
                      : (isFormalizedSubscription
                          ? 'bg-green-50 text-green-700'
                          : (isPendingCheckout
                            ? 'bg-amber-50 text-amber-700'
                            : (isTrialAccess
                              ? 'bg-yellow-50 text-yellow-700'
                              : (hasCouponAccess ? 'bg-blue-50 text-blue-700' : 'bg-green-50 text-green-700')))))"
              >
                <TriangleAlert v-if="isExpiredAccess" class="h-4 w-4" />
                <TriangleAlert v-else-if="isCancellationScheduled" class="h-4 w-4" />
                <TriangleAlert v-else-if="isPendingCheckout" class="h-4 w-4" />
                <Sparkles v-else-if="!isFormalizedSubscription && (isTrialAccess || hasCouponAccess)" class="h-4 w-4" />
                <Check v-else class="h-4 w-4" />
                <span class="text-sm font-medium">{{ planStatusText }}</span>
              </div>
            </div>
          </CardHeader>

          <!-- Expired Access Alert -->
          <Alert v-if="isExpiredAccess" class="mx-6 mb-4 w-auto border-red-200 bg-red-50">
            <TriangleAlert class="h-4 w-4 text-red-600" />
            <AlertTitle class="text-red-900">
              Seu acesso expirou
            </AlertTitle>
            <AlertDescription class="text-red-800">
              Sua vitrine está fora do ar no site público. Você ainda tem acesso ao painel para gerenciar seus dados.
              Para reativar a vitrine, conclua sua assinatura.
            </AlertDescription>
          </Alert>

          <Alert v-if="isPendingCheckout" class="mx-6 mb-4 w-auto border-amber-200 bg-amber-50">
            <TriangleAlert class="h-4 w-4 text-amber-700" />
            <AlertTitle class="text-amber-900">
              Pagamento pendente
            </AlertTitle>
            <AlertDescription class="text-amber-800">
              Sua vitrine está criada, mas ainda não aparece no site público. Conclua a assinatura na Stripe para publicar sua vitrine.
            </AlertDescription>
          </Alert>

          <!-- Trial Alert -->
          <Alert
            v-if="!isExpiredAccess && !isFormalizedSubscription && currentPlan.subscription?.is_trial && currentPlan.subscription?.trial_days_remaining > 0"
            class="mx-6 mb-4 w-auto border-yellow-200 bg-yellow-50"
          >
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

          <!-- Coupon Access Alert -->
          <Alert
            v-if="!isExpiredAccess && !isFormalizedSubscription && !isTrialAccess && hasCouponAccess && currentPlan.subscription?.discount_days_remaining > 0"
            class="mx-6 mb-4 w-auto border-blue-200 bg-blue-50"
          >
            <Sparkles class="h-4 w-4 text-blue-600" />
            <AlertTitle class="text-blue-900">
              Seu plano está em período promocional
            </AlertTitle>
            <AlertDescription class="text-blue-800">
              O benefício do cupom vai até <strong>{{ currentPlan.subscription.discount_ends_at }}</strong>
              ({{ currentPlan.subscription.discount_days_remaining }} dias restantes).
              Para manter sua vitrine ativa sem interrupções, finalize sua assinatura.
            </AlertDescription>
          </Alert>

          <Alert
            v-if="!isExpiredAccess && isFormalizedSubscription && (currentPlan.subscription?.trial_days_remaining > 0 || currentPlan.subscription?.discount_days_remaining > 0)"
            class="mx-6 mb-4 w-auto border-emerald-200 bg-emerald-50"
          >
            <Check class="h-4 w-4 text-emerald-600" />
            <AlertTitle class="text-emerald-900">
              Assinatura ativa com benefício promocional
            </AlertTitle>
            <AlertDescription class="text-emerald-800">
              Sua assinatura já está formalizada. O benefício promocional segue ativo
              até <strong>{{ currentPlan.subscription?.discount_ends_at || currentPlan.subscription?.trial_ends_at }}</strong>.
            </AlertDescription>
          </Alert>

          <Alert
            v-if="!isExpiredAccess && isFormalizedSubscription && isCancellationScheduled"
            class="mx-6 mb-4 w-auto border-amber-200 bg-amber-50"
          >
            <TriangleAlert class="h-4 w-4 text-amber-700" />
            <AlertTitle class="text-amber-900">
              Cancelamento agendado
            </AlertTitle>
            <AlertDescription class="text-amber-800 space-y-2">
              <p>
                Sua assinatura segue ativa até <strong>{{ currentPlan.subscription?.ends_at || 'o fim do ciclo' }}</strong>.
              </p>
              <p v-if="reactivationDeadlineText">
                Você pode reativar até <strong>{{ reactivationDeadlineText }}</strong>.
              </p>
              <p v-else>
                Você pode reativar antes do fechamento deste ciclo.
              </p>
              <div>
                <Button
                  size="sm"
                  variant="outline"
                  :disabled="cancelProcessing"
                  @click="openCancellationDialog"
                >
                  {{ cancelProcessing ? cancellationButtonBusyLabel : 'Reativar assinatura' }}
                </Button>
              </div>
            </AlertDescription>
          </Alert>

          <CardContent class="space-y-6">
            <div
              v-if="isFormalizedSubscription && !isExpiredAccess"
              class="flex flex-col gap-2 sm:flex-row sm:justify-end"
            >
              <Button variant="outline" @click="scrollToPlans">
                Migrar plano
              </Button>
              <Button
                :variant="cancellationButtonVariant"
                :disabled="cancelProcessing"
                @click="openCancellationDialog"
              >
                {{ cancelProcessing ? cancellationButtonBusyLabel : cancellationButtonLabel }}
              </Button>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
              <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                  Plano atual
                </p>
                <p class="mt-1 text-sm font-semibold text-slate-900">
                  {{ currentPlan.name }}
                </p>
              </div>
              <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                  Status
                </p>
                <p class="mt-1 text-sm font-semibold text-slate-900">
                  {{ currentStatusText }}
                </p>
              </div>
              <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                  Vigência
                </p>
                <p class="mt-1 text-sm font-semibold text-slate-900">
                  {{ accessUntilText }}
                </p>
              </div>
              <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                  Próxima ação
                </p>
                <p class="mt-1 text-sm font-semibold text-slate-900">
                  {{ nextActionText }}
                </p>
              </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
              <div class="space-y-2">
                <h4 class="text-sm font-medium text-muted-foreground">
                  Valor
                </h4>
                <p class="text-2xl font-bold">
                  <span v-if="isTrialAccess && !isFormalizedSubscription" class="text-green-600">Grátis</span>
                  <span v-else>{{ currentPlan.current_price ? formatPrice(currentPlan.current_price, currentPlan.current_interval?.toLowerCase().includes('anual') ? 'yearly' : 'monthly') : 'N/A' }}</span>
                </p>
                <p v-if="isTrialAccess && !isFormalizedSubscription" class="text-xs text-muted-foreground">
                  Depois: {{ currentPlan.current_price ? formatPrice(currentPlan.current_price, currentPlan.current_interval?.toLowerCase().includes('anual') ? 'yearly' : 'monthly') : 'N/A' }}
                </p>
              </div>

              <div class="space-y-2">
                <h4 class="text-sm font-medium text-muted-foreground">
                  Uso Atual
                </h4>
                <ul class="space-y-3">
                  <li
                    v-for="limit in getPlanLimits()"
                    :key="limit.key"
                    class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2"
                  >
                    <div class="flex items-center justify-between gap-3 text-sm">
                      <span class="text-slate-700">{{ limit.name }}</span>
                      <span class="font-semibold text-slate-900">
                        {{ limit.currentLabel }}/{{ limit.limitLabel }}
                      </span>
                    </div>
                    <Progress v-if="Number(limit.limit) > 0" :model-value="getLimitProgress(limit)" class="mt-2 h-1.5" />
                    <p class="mt-1 text-xs text-muted-foreground">
                      {{ getLimitStatusText(limit) }}
                    </p>
                  </li>
                </ul>
                <p class="text-xs text-muted-foreground">
                  Limites do plano atual
                </p>
              </div>

              <div class="space-y-2">
                <h4 class="text-sm font-medium text-muted-foreground">
                  Recursos do plano
                </h4>
                <p class="text-xs text-muted-foreground">
                  {{ currentPlanDisplayFeatures.length }} recurso(s) ativo(s)
                </p>
                <ul v-if="currentPlanDisplayFeatures.length > 0" class="overflow-hidden rounded-md border border-emerald-100 bg-emerald-50/30">
                  <li
                    v-for="(feature, idx) in currentPlanDisplayFeatures"
                    :key="idx"
                    class="flex items-start gap-2 border-b border-emerald-100 px-3 py-2 text-sm text-slate-800 last:border-b-0"
                  >
                    <Check class="mt-0.5 h-4 w-4 flex-shrink-0 text-emerald-600" />
                    <span class="leading-5">{{ feature }}</span>
                  </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">
                  Este plano não possui recursos adicionais configurados.
                </p>
              </div>
            </div>

            <div v-if="showNextStepButton" class="flex justify-end">
              <Button @click="scrollToPlans">
                {{ ctaLabel }}
                <ArrowRight class="ml-2 h-4 w-4" />
              </Button>
            </div>
          </CardContent>
        </Card>

        <!-- Plans Section Header -->
        <div class="mb-6">
          <h2 class="text-xl font-bold mb-2">
            {{ plansSectionTitle }}
          </h2>
          <p class="text-gray-500">
            {{ plansSectionDescription }}
          </p>
        </div>

        <!-- Upgrade Recommendation -->
        <Alert v-if="recommendedPlan" class="mb-6 border-teal-200 bg-teal-50">
          <Crown class="h-4 w-4 text-teal-700" />
          <AlertTitle class="text-teal-900">
            Upgrade recomendado: {{ recommendedPlan.name }}
          </AlertTitle>
          <AlertDescription class="text-teal-800">
            Você está no {{ currentPlan?.name }}. O próximo passo recomendado para crescimento da sua vitrine é o plano {{ recommendedPlan.name }}.
          </AlertDescription>
        </Alert>

        <!-- Interval Selector - Only show if multiple intervals exist -->
        <Card v-if="hasMultipleIntervals && availablePlans.length > 0" class="p-4 mb-6">
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
        <div id="plans-list" v-if="availablePlans.length > 0" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3 mb-8">
          <Card
            v-for="plan in availablePlans"
            :key="plan.id"
            class="flex flex-col relative overflow-hidden hover:shadow-lg transition-shadow"
            :class="{
              'border-primary border-2': plan.name.includes('Pro'),
              'ring-2 ring-teal-500 border-teal-200': isPlanSelected(plan),
            }"
          >
            <!-- Popular Badge -->
            <div v-if="plan.name.includes('Pro')" class="absolute top-4 right-4 z-10">
              <div class="bg-primary text-primary-foreground px-3 py-1 rounded-full text-xs font-semibold flex items-center gap-1">
                <Sparkles class="h-3 w-3" />
                Popular
              </div>
            </div>

            <CardHeader>
              <div class="flex items-start justify-between gap-2">
                <CardTitle class="flex items-center gap-2 text-xl">
                  <Zap class="h-5 w-5 text-primary" />
                  {{ plan.name }}
                </CardTitle>
                <span
                  v-if="isPlanSelected(plan)"
                  class="rounded-full bg-teal-100 px-2 py-1 text-[11px] font-semibold text-teal-800"
                >
                  Selecionado
                </span>
              </div>
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
                v-if="!isPlanSelected(plan)"
                class="w-full"
                variant="outline"
                @click="selectPlan(plan.id)"
              >
                Selecionar plano
              </Button>
              <div
                v-else
                class="w-full rounded-md border border-teal-200 bg-teal-50 px-3 py-2 text-center text-sm font-semibold text-teal-800"
              >
                Plano selecionado
              </div>
            </CardFooter>
          </Card>
        </div>

        <Card v-if="availablePlans.length > 0 && selectedPlan" class="p-4 mb-6">
          <div class="space-y-4">
            <div v-if="showCouponSection" class="space-y-1">
              <p class="text-sm font-semibold text-slate-900">
                2. Cupom (opcional)
              </p>
              <p class="text-xs text-muted-foreground">
                Com o plano selecionado, aplique um cupom para visualizar o benefício antes de assinar.
              </p>
            </div>

            <div v-if="showCouponSection" class="grid gap-3 md:grid-cols-[minmax(0,320px)_1fr] md:items-end">
              <div class="space-y-2">
                <Label for="subscription-coupon">Código do cupom (opcional)</Label>
                <div class="flex gap-2">
                  <Input
                    id="subscription-coupon"
                    v-model="couponCode"
                    placeholder="Ex.: PROMO3MESES"
                    class="max-w-sm"
                  />
                  <Button type="button" variant="outline" :disabled="couponApplyDisabled" @click="applyCoupon">
                    {{ couponApplyButtonLabel }}
                  </Button>
                </div>
              </div>
              <div class="space-y-1 rounded-md border border-teal-100 bg-teal-50/60 px-3 py-2 text-xs text-teal-800">
                <p>
                  {{ planSelectionHelperText }}
                </p>
                <p>
                  Valor base para validação: <strong>{{ selectedPlanPriceLabel }}</strong>.
                </p>
              </div>
            </div>

            <p v-if="showCouponSection && couponCode.trim().length > 0" class="text-xs text-slate-500">
              Cupom informado: <span class="font-semibold text-slate-700">{{ couponCode.trim().toUpperCase() }}</span>
            </p>

            <Alert v-if="showCouponSection && couponError" variant="destructive" class="w-auto">
              <TriangleAlert class="h-4 w-4" />
              <AlertTitle>Não foi possível aplicar o cupom</AlertTitle>
              <AlertDescription>{{ couponError }}</AlertDescription>
            </Alert>

            <Alert v-if="showCouponSection && couponNeedsRefresh && appliedCoupon" class="w-auto border-amber-200 bg-amber-50">
              <TriangleAlert class="h-4 w-4 text-amber-600" />
              <AlertTitle class="text-amber-900">Reaplique o cupom</AlertTitle>
              <AlertDescription class="text-amber-800">
                Você alterou o plano ou o período. Clique em "Aplicar cupom" para atualizar os valores.
              </AlertDescription>
            </Alert>

            <Alert v-if="showCouponSection && appliedCoupon" class="w-auto border-emerald-200 bg-emerald-50">
              <Check class="h-4 w-4 text-emerald-700" />
              <AlertTitle class="text-emerald-900">
                Cupom {{ appliedCoupon.code }} aplicado
              </AlertTitle>
              <AlertDescription class="space-y-1 text-emerald-800">
                <p>
                  Benefício: <strong>{{ appliedCouponOfferText }}</strong>
                </p>
                <p v-if="!couponNeedsRefresh">
                  {{ couponPreviewContextText }}:
                  com cupom <strong>{{ formatCurrencyValue(appliedCoupon.final_price) }}</strong>.
                </p>
                <p v-if="!couponNeedsRefresh">
                  Valor normal sem cupom:
                  <strong>{{ formatCurrencyValue(couponPreviewIntervalData?.price) }}</strong>.
                </p>
                <p v-if="couponBillingForecast && !couponNeedsRefresh">
                  Primeira cobrança prevista em
                  <strong>{{ couponBillingForecast.nextChargeDateText }}</strong>:
                  <strong>{{ couponBillingForecast.nextChargeAmountText }}</strong>.
                </p>
                <p v-if="couponBillingForecast && !couponNeedsRefresh" class="text-[11px] text-emerald-700/90">
                  Estimativa com base em assinatura iniciada hoje (benefício por {{ couponBillingForecast.durationText }}).
                </p>
                <p v-if="couponNeedsRefresh">
                  O benefício foi validado, mas o plano/período mudou. Reaplique para atualizar os valores exibidos.
                </p>
              </AlertDescription>
            </Alert>

            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
              <p class="text-sm font-semibold text-slate-900">{{ confirmationStepTitle }}</p>
              <p class="mt-1 text-sm text-slate-600">
                {{ confirmationSummaryText }}
              </p>
              <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:justify-end">
                <Button
                  class="w-full sm:w-auto group"
                  :disabled="selectedPlanCheckoutDisabled"
                  @click="subscribeSelectedPlan"
                >
                  <template v-if="selectedPlanCheckoutBusy">
                    Processando...
                  </template>
                  <template v-else>
                    {{ subscribeButtonLabel }}
                  </template>
                  <ArrowRight class="h-4 w-4 ml-2 group-hover:translate-x-1 transition-transform" />
                </Button>
              </div>
            </div>
          </div>
        </Card>

        <!-- Empty State -->
        <div v-else class="text-center py-12 bg-white rounded-lg border">
          <div class="mx-auto w-16 h-16 mb-4 bg-gray-100 rounded-full flex items-center justify-center">
            <TriangleAlert class="h-8 w-8 text-gray-400" />
          </div>
          <h3 class="text-lg font-medium text-gray-900">
            {{ isOnBestPlan ? 'Você já está no melhor plano' : 'Nenhum plano disponível' }}
          </h3>
          <p class="mt-2 text-gray-500">
            {{ isOnBestPlan ? 'Quando novos planos forem lançados, você verá opções de upgrade aqui.' : 'Entre em contato com o suporte para mais informações.' }}
          </p>
        </div>

        <!-- Security Notice -->
        <Alert class="mt-8">
          <Lock class="h-4 w-4" />
          <AlertDescription>
            Pagamentos processados de forma segura. Seus dados estão protegidos.
          </AlertDescription>
        </Alert>

        <Dialog v-model:open="planChangeDialogOpen">
          <DialogContent class="sm:max-w-lg">
            <DialogHeader>
              <DialogTitle>{{ planChangeDialogTitle }}</DialogTitle>
              <DialogDescription>
                {{ planChangeDialogDescription }}
              </DialogDescription>
            </DialogHeader>

            <DialogFooter class="gap-2">
              <Button variant="outline" :disabled="planChangeSubmitting" @click="closePlanChangeDialog">
                Voltar
              </Button>
              <Button :disabled="planChangeSubmitting" @click="confirmPlanChange">
                {{ planChangeDialogConfirmLabel }}
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>

        <Dialog v-model:open="cancelDialogOpen">
          <DialogContent class="sm:max-w-md">
            <DialogHeader>
              <DialogTitle>{{ cancelDialogTitle }}</DialogTitle>
              <DialogDescription>
                {{ cancelDialogDescription }}
              </DialogDescription>
            </DialogHeader>

            <DialogFooter class="gap-2">
              <Button variant="outline" :disabled="cancelProcessing" @click="closeCancellationDialog">
                Voltar
              </Button>
              <Button
                :variant="isCancellationScheduled ? 'default' : 'destructive'"
                :disabled="cancelProcessing"
                @click="toggleSubscriptionCancellation"
              >
                {{ cancelDialogConfirmLabel }}
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>
    </main>
  </div>
</template>
