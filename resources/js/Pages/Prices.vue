<script setup>
import FeaturesCard from '@/Components/FeaturesCard.vue'
import Accordion from '@/Components/shadcn/ui/accordion/Accordion.vue'
import AccordionContent from '@/Components/shadcn/ui/accordion/AccordionContent.vue'
import AccordionItem from '@/Components/shadcn/ui/accordion/AccordionItem.vue'
import AccordionTrigger from '@/Components/shadcn/ui/accordion/AccordionTrigger.vue'
import Badge from '@/Components/shadcn/ui/badge/Badge.vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import Card from '@/Components/shadcn/ui/card/Card.vue'
import Switch from '@/Components/shadcn/ui/switch/Switch.vue'
import { useSeoMetaTags } from '@/Composables/useSeoMetaTags.js'
import WebLayout from '@/Layouts/WebLayout.vue'
import { Icon } from '@iconify/vue'
import { computed, ref } from 'vue'

const props = defineProps({
  canLogin: {
    type: Boolean,
  },
  canRegister: {
    type: Boolean,
  },
  seo: {
    type: Object,
    default: () => null,
  },
  plans: {
    type: Array,
    default: () => [],
  },
})

useSeoMetaTags(props.seo)

// Toggle state for pricing interval (false = monthly, true = yearly)
const isYearly = ref(false)

// Computed property to get the selected interval based on toggle
const selectedInterval = computed(() => isYearly.value ? 'year' : 'month')

const publicPlans = computed(() => {
  return props.plans.filter(plan => !plan.metadata?.is_default)
})

// Check if there are multiple intervals available
const hasMultipleIntervals = computed(() => {
  const allIntervals = new Set()
  publicPlans.value.forEach((plan) => {
    plan.intervals?.forEach((interval) => {
      allIntervals.add(interval.code)
    })
  })
  return allIntervals.size > 1
})

function getStringFeatures(features) {
  if (!features)
    return []

  if (Array.isArray(features)) {
    return features.filter(feature => typeof feature === 'string')
  }

  if (typeof features === 'object') {
    return Object.values(features).filter(feature => typeof feature === 'string')
  }

  return []
}

function formatCurrency(value) {
  const numericValue = Number(value) || 0
  return numericValue.toFixed(2).replace('.', ',')
}

function normalizeText(value) {
  return String(value || '')
    .trim()
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036F]/g, '')
}

const planRules = [
  {
    key: 'vitrine',
    name: 'Vitrine',
    sections: [
      {
        title: 'Exposição',
        items: [
          { text: 'Exibição padrão nas buscas e listagens', status: 'included' },
          { text: 'Não aparece no mapa', status: 'excluded' },
          { text: 'Sem selo de verificação', status: 'excluded' },
        ],
      },
      {
        title: 'Conteúdo e mídia',
        items: [
          { text: '10 fotos de destaque', status: 'included' },
          { text: 'Até 2 coleções', status: 'included' },
          { text: '1 link de vídeo da loja', status: 'included' },
        ],
      },
      {
        title: 'Analytics',
        items: [
          { text: 'Analytics básico', status: 'included' },
          { text: 'Visualizações da vitrine', status: 'included' },
          { text: 'Cliques no WhatsApp', status: 'included' },
        ],
      },
      {
        title: 'Canais de contato',
        items: [
          { text: 'WhatsApp', status: 'included' },
          { text: 'Instagram', status: 'included' },
        ],
      },
      {
        title: 'Identidade da loja',
        items: [
          { text: 'Descrição completa da loja', status: 'included' },
          { text: 'Categoria e subcategorias', status: 'included' },
          { text: 'Logo da marca', status: 'included' },
          { text: 'Informações principais da loja', status: 'included' },
        ],
      },
    ],
  },
  {
    key: 'destaque',
    name: 'Destaque',
    sections: [
      {
        title: 'Exposição',
        items: [
          { text: 'Prioridade nas listagens', status: 'included' },
          { text: 'Presença em seções de destaque da home', status: 'included' },
          { text: 'Badge visual de destaque', status: 'included' },
          { text: 'Presença na área/mapa de destaque', status: 'included' },
          { text: 'Elegível ao selo de loja verificada após análise', status: 'included' },
        ],
      },
      {
        title: 'Conteúdo e mídia',
        items: [
          { text: '20 fotos de destaque', status: 'included' },
          { text: 'Até 5 coleções', status: 'included' },
          { text: '1 vídeo em destaque na vitrine', status: 'included' },
          { text: 'Até 2 vídeos nas coleções', status: 'included' },
        ],
      },
      {
        title: 'Analytics',
        items: [
          { text: 'Analytics completo', status: 'included' },
          { text: 'Visualizações da vitrine', status: 'included' },
          { text: 'Cliques no WhatsApp', status: 'included' },
          { text: 'Cliques no site', status: 'included' },
          { text: 'Cliques no mapa/localização', status: 'included' },
          { text: 'Compartilhamentos', status: 'included' },
          { text: 'Cliques no Instagram', status: 'included' },
          { text: 'Cliques no Facebook', status: 'included' },
          { text: 'Cliques no TikTok', status: 'included' },
        ],
      },
      {
        title: 'Canais de contato',
        items: [
          { text: 'WhatsApp', status: 'included' },
          { text: 'Instagram', status: 'included' },
          { text: 'Site', status: 'included' },
          { text: 'Facebook', status: 'included' },
          { text: 'TikTok', status: 'included' },
        ],
      },
      {
        title: 'Identidade da loja',
        items: [
          { text: 'Descrição completa da loja', status: 'included' },
          { text: 'Categoria e subcategorias', status: 'included' },
          { text: 'Logo da marca', status: 'included' },
          { text: 'Informações principais da loja', status: 'included' },
        ],
      },
    ],
  },
]

function getPlanRule(planName) {
  const normalizedName = normalizeText(planName)
  return planRules.find(rule => normalizedName.includes(rule.key))
}

function getItemIcon(status) {
  if (status === 'excluded')
    return 'lucide:x-circle'
  if (status === 'pending')
    return 'lucide:alert-triangle'
  return 'lucide:check-circle'
}

function getItemClass(status) {
  if (status === 'excluded')
    return 'text-slate-400'
  if (status === 'pending')
    return 'text-amber-700'
  return 'text-emerald-600'
}

// Filter plans to show only the selected interval pricing
const plansWithSelectedInterval = computed(() => {
  return publicPlans.value
    .map(plan => ({
      ...plan,
      rule: getPlanRule(plan.name),
      features: getStringFeatures(plan.features),
      intervals: plan.intervals?.filter(interval => interval.code === selectedInterval.value) || [],
    }))
    .filter(plan => plan.intervals.length > 0)
})

const mobilePriorityPlanId = computed(() => {
  const plans = plansWithSelectedInterval.value

  if (plans.length === 0)
    return null

  const featuredPlan = plans.find(plan => plan.is_featured)
  if (featuredPlan)
    return featuredPlan.id

  const mostExpensivePlan = [...plans].sort((a, b) => {
    const priceA = Number(a.intervals?.[0]?.price || 0)
    const priceB = Number(b.intervals?.[0]?.price || 0)
    return priceB - priceA
  })[0]

  return mostExpensivePlan?.id ?? null
})

// Calculate average discount percentage for yearly plans
const averageYearlyDiscount = computed(() => {
  const plansWithBothIntervals = publicPlans.value.filter((plan) => {
    const hasMonth = plan.intervals?.some(i => i.code === 'month')
    const hasYear = plan.intervals?.some(i => i.code === 'year')
    return hasMonth && hasYear
  })

  if (plansWithBothIntervals.length === 0)
    return 0

  const discounts = plansWithBothIntervals.map((plan) => {
    const monthlyPrice = plan.intervals.find(i => i.code === 'month')?.price || 0
    const yearlyPrice = plan.intervals.find(i => i.code === 'year')?.price || 0
    if (!monthlyPrice)
      return 0
    const yearlyMonthlyEquivalent = yearlyPrice / 12
    const discount = ((monthlyPrice - yearlyMonthlyEquivalent) / monthlyPrice) * 100
    return discount
  })

  const avgDiscount = discounts.reduce((sum, d) => sum + d, 0) / discounts.length
  return Math.round(avgDiscount)
})

// Function to open WhatsApp chat
function openWhatsAppChat() {
  const phone = '556231900204'
  const message = 'Olá! Gostaria de falar com um especialista sobre a Tá na Vitrine.'
  const url = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`
  window.open(url, '_blank')
}

const features = [
  {
    icon: '/images/saving-money.png',
    title: 'Mais visibilidade para sua marca',
    description: 'Sua loja ganha destaque no maior catálogo atacadista do Brasil, alcançando compradores de todas as regiões sem depender de anúncios pagos.',
  },
  {
    icon: '/images/close-business.png',
    title: 'Receba contatos diretos',
    description: 'Os interessados entram em contato pelos seus próprios canais — WhatsApp, Instagram ou site — sem intermediários e sem taxas.',
  },
  {
    icon: '/images/notification.png',
    title: 'Fácil de cadastrar e gerenciar',
    description: 'Crie sua vitrine em minutos: adicione fotos, informações da loja e formas de contato em um painel simples e intuitivo.',
  },
  {
    icon: '/images/brand-image.png',
    title: 'Compradores qualificados',
    description: 'Atraia atacadistas, revendedores e lojistas que realmente buscam novos fornecedores no segmento de moda.',
  },
]

const faqItems = [
  {
    value: 'item-1',
    title: 'O que é a Tá na Vitrine?',
    content: 'A Tá na Vitrine é uma plataforma de anúncios para lojas de moda no atacado e varejo. Cada loja cria uma vitrine com suas fotos, informações e contatos, conectando-se diretamente com compradores interessados.',
  },
  {
    value: 'item-2',
    title: 'A Tá na Vitrine vende ou processa pagamentos?',
    content: 'Não. A plataforma não realiza vendas nem intermedia pagamentos. Nosso foco é gerar visibilidade e leads qualificados para as lojas.',
  },
  {
    value: 'item-3',
    title: 'Quem pode anunciar na Tá na Vitrine?',
    content: 'Qualquer loja, marca, fabricante ou fornecedor do segmento de moda que queira divulgar seus produtos e atrair compradores de todo o Brasil.',
  },
  {
    value: 'item-4',
    title: 'Como funciona o anúncio (vitrine) da minha loja?',
    content: 'Você cadastra sua loja uma única vez, adiciona fotos, informações e canais de contato. A partir daí, sua vitrine fica disponível para compradores que buscam fornecedores no catálogo.',
  },
  {
    value: 'item-5',
    title: 'Posso colocar links para meu WhatsApp ou Instagram?',
    content: 'Sim! Você escolhe como quer ser contatado — WhatsApp, Instagram, site, e-mail ou telefone. O comprador fala diretamente com você.',
  },
  {
    value: 'item-6',
    title: 'Quanto custa anunciar na Tá na Vitrine?',
    content: 'A Tá na Vitrine oferece planos acessíveis e sem comissão sobre vendas. Você paga apenas pelo espaço da sua vitrine — o retorno vem em visibilidade e novos contatos.',
  },
  {
    value: 'item-7',
    title: 'Preciso ter CNPJ para anunciar?',
    content: 'O ideal é ter, mas não é obrigatório. Aceitamos tanto lojistas formalizados quanto empreendedores individuais que já atuam no setor de moda.',
  },
  {
    value: 'item-8',
    title: 'Posso editar minha vitrine depois de criada?',
    content: 'Sim! Você pode atualizar fotos, informações e contatos a qualquer momento pelo seu painel de controle.',
  },
  {
    value: 'item-9',
    title: 'Como os compradores encontram minha loja?',
    content: 'As vitrines aparecem no catálogo por categoria, localização e tipo de produto. Isso facilita para que compradores encontrem exatamente o que procuram.',
  },
  {
    value: 'item-10',
    title: 'A Tá na Vitrine é segura?',
    content: 'Sim. Todos os dados são protegidos, e as comunicações ocorrem diretamente entre comprador e vendedor, sem intermediações nem acesso a informações sensíveis.',
  },
]
</script>

<template>
  <WebLayout :can-login="canLogin" :can-register="canRegister">
    <!-- Hero Section -->
    <section class="relative overflow-hidden border-b border-orange-200 py-10 sm:py-20">
      <div class="absolute inset-0 z-0 overflow-hidden">
        <img
          src="/images/bg-hero.png?v=2"
          alt=""
          class="h-full w-full object-cover object-center opacity-80"
          loading="eager"
          fetchpriority="high"
          aria-hidden="true"
        >
      </div>
      <div class="absolute inset-0 z-0 bg-linear-to-r from-teal-950/70 via-teal-900/62 to-teal-800/68" />

      <div class="relative z-10 container mx-auto px-4 text-center">
        <!-- Main Heading -->
        <div class="mx-auto max-w-4xl">
          <h1
            class="text-4xl font-extrabold tracking-tight sm:text-5xl md:text-6xl lg:text-7xl"
            :style="{ contain: 'layout paint' }"
          >
            <span class="block text-white">Divulgue sua loja</span>
            <span
              class="mt-2 block bg-linear-to-r from-yellow-500 via-rose-400 to-amber-500 bg-clip-text text-transparent"
            >
              na maior vitrine de moda do Brasil
            </span>
          </h1>
        </div>

        <!-- Subtitle - Add priority hint -->
        <p
          class="mx-auto mt-6 max-w-2xl text-center text-base text-white sm:text-lg md:text-xl"
          :style="{ contain: 'layout paint' }"
          fetchpriority="high"
        >
          Sem comissão, sem complicação, mais visibilidade, mais contatos e mais oportunidades para o seu negócio.
        </p>
      </div>

      <!-- Background Effects -->
      <div
        class="absolute inset-0 z-0 h-full w-full bg-[linear-gradient(to_right,#4f4f4f22_1px,transparent_1px),linear-gradient(to_bottom,#4f4f4f22_1px,transparent_1px)] bg-[size:14px_24px]"
      />
      <div
        class="absolute left-0 right-0 top-0 z-0 m-auto h-[310px] w-[310px] rounded-full bg-primary/20 opacity-25 blur-[100px]"
      />
    </section>

    <section id="pricing" class="border-t border-orange-200 bg-linear-to-b from-orange-50/70 via-amber-50/30 to-white">
      <div class="container mx-auto px-4 py-16 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mx-auto mb-12 max-w-4xl text-center">
          <h2 class="text-center text-3xl font-bold tracking-tight sm:text-4xl">
            Escolha o Plano Ideal Para Você
          </h2>
          <p class="mx-auto mt-4 max-w-2xl text-center text-sm text-muted-foreground sm:text-base">
            Escolha o plano que melhor atende às necessidades da sua loja.
          </p>

          <!-- Billing Toggle - Only show if multiple intervals exist -->
          <div v-if="hasMultipleIntervals" class="mt-8 flex justify-center">
            <div class="inline-flex items-center gap-3 rounded-full border border-slate-200 bg-white px-4 py-2 shadow-sm">
              <span :class="!isYearly ? 'font-semibold text-foreground' : 'text-muted-foreground'" class="text-sm">
                Mensal
              </span>
              <Switch v-model:checked="isYearly" class="data-[state=checked]:bg-primary" />
              <span :class="isYearly ? 'font-semibold text-foreground' : 'text-muted-foreground'" class="text-sm">
                Anual
              </span>
              <Badge v-if="averageYearlyDiscount > 0" variant="secondary" class="border border-emerald-200 bg-emerald-50 text-emerald-700">
                <Icon icon="lucide:trending-down" class="size-3 mr-1" aria-hidden="true" />
                {{ averageYearlyDiscount }}% OFF
              </Badge>
            </div>
          </div>
        </div>

        <!-- Trial Notice -->
        <div class="relative mx-auto mb-8 max-w-5xl">
          <div class="pointer-events-none absolute -inset-0.5 rounded-2xl bg-linear-to-r from-emerald-300 via-teal-300 to-cyan-300 opacity-70 blur-md" />
          <div class="relative overflow-hidden rounded-2xl border border-emerald-200 bg-white/95 px-5 py-4 shadow-lg sm:px-6 sm:py-5">
            <div class="absolute -right-10 -top-10 h-28 w-28 rounded-full bg-emerald-100/80 blur-2xl" />
            <div class="relative flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <div class="space-y-1">
                <p class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-emerald-700">
                  <Icon icon="lucide:sparkles" class="h-3.5 w-3.5" />
                  Oferta de Boas-vindas
                </p>
                <h3 class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl">
                  Comece com 14 dias grátis para testar
                </h3>
                <p class="text-sm leading-snug text-slate-600">
                  Cadastre sua loja e teste todos os recursos com acesso completo.
                </p>
              </div>
              <div class="flex shrink-0 items-center gap-2 self-start sm:self-auto">
                <Icon icon="lucide:shield-check" class="h-4.5 w-4.5 text-emerald-600" />
                <span class="text-xs font-medium text-slate-600">Ativação automática no cadastro</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Pricing Cards Grid -->
        <div v-if="plansWithSelectedInterval.length > 0" class="mx-auto mt-10 grid max-w-5xl gap-6 md:grid-cols-2 lg:grid-cols-2">
          <Card
            v-for="plan in plansWithSelectedInterval"
            :key="`${plan.id}-${selectedInterval}`"
            class="relative flex h-full flex-col overflow-hidden border border-slate-200/90 bg-white shadow-sm transition-all duration-300" :class="[
              plan.is_featured ? 'ring-1 ring-primary shadow-xl md:-translate-y-1' : 'hover:shadow-md',
              plan.id === mobilePriorityPlanId ? 'order-first md:order-none' : '',
            ]"
          >
            <div class="flex h-full flex-col p-5 sm:p-6">
              <!-- Header -->
              <div class="mb-5 border-b border-slate-200 pb-5 text-center">
                <div v-if="plan.is_featured" class="mb-3 flex justify-center md:justify-end">
                  <Badge class="border border-amber-200 bg-amber-50 text-amber-700">
                    <Icon icon="lucide:star" class="mr-1 size-3" aria-hidden="true" />
                    Plano Recomendado
                  </Badge>
                </div>
                <h3 class="mb-1 text-2xl font-bold text-slate-900">
                  {{ plan.name }}
                </h3>
                <p class="mx-auto max-w-sm text-sm text-slate-600">
                  {{ plan.description }}
                </p>
              </div>

              <!-- Price -->
              <div v-if="plan.intervals && plan.intervals.length > 0" class="mb-5 rounded-lg border border-slate-200 bg-slate-50 px-4 py-4 text-center">
                <div v-for="interval in plan.intervals" :key="interval.id">
                  <div class="mb-1 text-4xl font-bold text-slate-900 sm:text-[2.75rem]">
                    R$ {{ formatCurrency(interval.price) }}
                  </div>
                  <p class="text-sm font-medium text-slate-600">
                    {{ interval.name }}
                  </p>
                  <p v-if="isYearly" class="mt-1 text-xs text-slate-500">
                    R$ {{ formatCurrency(interval.price / 12) }}/mês
                  </p>
                </div>
              </div>

              <!-- CTA Button -->
              <div class="mb-5">
                <Button
                  v-for="interval in plan.intervals"
                  :key="`btn-${interval.id}`"
                  as="a"
                  :href="`/onboarding/start?plan=${interval.id}`"
                  class="w-full"
                  size="lg"
                  :variant="plan.is_featured ? 'default' : 'secondary'"
                >
                  <Icon icon="lucide:zap" class="size-4 mr-2" aria-hidden="true" />
                  Escolher Este Plano
                </Button>
              </div>

              <!-- Features List -->
              <div class="flex-1">
                <div v-if="plan.rule" class="divide-y divide-slate-200">
                  <div
                    v-for="section in plan.rule.sections"
                    :key="`${plan.id}-${section.title}`"
                    class="py-3 first:pt-0 last:pb-0"
                  >
                    <h4 class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                      {{ section.title }}
                    </h4>
                    <ul class="space-y-1.5">
                      <li
                        v-for="item in section.items"
                        :key="item.text"
                        class="flex items-start gap-2 text-sm"
                        :class="getItemClass(item.status)"
                      >
                        <Icon
                          :icon="getItemIcon(item.status)"
                          class="mt-0.5 h-3.5 w-3.5 shrink-0"
                        />
                        <span class="leading-snug text-slate-700">{{ item.text }}</span>
                      </li>
                    </ul>
                  </div>
                </div>

                <ul v-else-if="plan.features.length > 0" class="space-y-2">
                  <li v-for="(feature, index) in plan.features" :key="index" class="flex items-start text-sm">
                    <Icon icon="lucide:check" class="mr-2 h-5 w-5 text-primary flex-shrink-0 mt-0.5" />
                    <span class="text-slate-700">{{ feature }}</span>
                  </li>
                </ul>
                <p v-else class="text-sm text-slate-600">
                  Recursos sob consulta.
                </p>
              </div>
            </div>
          </Card>
        </div>

        <!-- Fallback if no plans -->
        <div v-else class="text-center py-12">
          <p class="text-muted-foreground">
            Nenhum plano disponível no momento. Entre em contato conosco para mais informações.
          </p>
        </div>
      </div>
    </section>

    <!-- Features Grid -->
    <section id="features" class="container mx-auto px-4 py-25 sm:px-6 lg:px-8">
      <h2 class="text-center text-2xl font-bold tracking-tight sm:text-4xl">
        Foco em visibilidade e conexão
      </h2>
      <p class="mx-auto mt-4 max-w-2xl text-center text-muted-foreground">
        Conecte sua loja a milhares de compradores <br>prontos para negociar.
      </p>

      <div class="mt-16 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
        <FeaturesCard
          v-for="feature in features" :key="feature.title" :icon="feature.icon"
          :title="feature.title" :description="feature.description"
        />
      </div>
      <div class="mt-15 flex justify-center gap-2">
        <Button
          variant="secondary" as="a" href="#pricing"
          rel="noopener noreferrer" size="lg"
        >
          <Icon icon="lucide:handshake" class="size-4" aria-hidden="true" />
          Contratar agora
        </Button>
      </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="border-t border-orange-200">
      <div class="container mx-auto px-4 pt-16 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl">
          <div class="text-center mb-12">
            <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">
              Perguntas Frequentes
            </h2>
            <p class="mt-4 text-muted-foreground">
              Tire suas dúvidas sobre a Tá na Vitrine
            </p>
          </div>

          <Accordion type="single" class="w-full" collapsible default-value="item-1">
            <AccordionItem v-for="item in faqItems" :key="item.value" :value="item.value">
              <AccordionTrigger class="text-left text-base sm:text-lg font-semibold hover:text-primary">
                {{ item.title }}
              </AccordionTrigger>
              <AccordionContent class="text-muted-foreground">
                {{ item.content }}
              </AccordionContent>
            </AccordionItem>
          </Accordion>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section id="cta" class="relative overflow-hidden">
      <div class="container relative z-10 mx-auto px-4 py-16 sm:py-24 sm:px-6 lg:px-8">
        <div class="relative overflow-hidden rounded-3xl bg-white/10 backdrop-blur-sm px-6 py-16 shadow-2xl sm:px-16 border border-white/20">
          <!-- Background Image -->
          <div class="absolute inset-0 -z-10 overflow-hidden">
            <img
              src="/images/bg-cta.png"
              alt="Tá na Vitrine"
              class="w-full h-full object-cover"
            >
            <!-- Overlay with gradient -->
            <div class="absolute inset-0 bg-gradient-to-br from-primary/40 via-amber-600/85 to-primary/30" />
          </div>
          <div class="mx-auto max-w-3xl text-center">
            <h2 class="text-3xl font-bold tracking-tight  text-white sm:text-5xl">
              Pronto para Fortalecer sua marca e ampliar seu alcance no principal hub de moda atacadista do Brasil?
            </h2>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-white font-semibold">
              Junte-se a centenas de atacadistas, cadastre sua loja uma vez e seja encontrado por quem realmente compra.
            </p>
            <div class="mt-4 pt-6 border-t border-white/20">
              <div class=" flex flex-col sm:flex-row items-center justify-center gap-4">
                <Button as="a" href="#pricing" size="lg" variant="secondary" class="w-full sm:w-auto">
                  <Icon icon="lucide:rocket" class="size-5 mr-2" aria-hidden="true" />
                  Ver Planos e Preços
                </Button>
                <Button size="lg" variant="outline" class="w-full sm:w-auto bg-white/10 hover:bg-white/20 text-white border-white/30" @click="openWhatsAppChat">
                  <Icon icon="lucide:message-circle" class="size-5 mr-2" aria-hidden="true" />
                  Falar com Especialista
                </Button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </WebLayout>
</template>
