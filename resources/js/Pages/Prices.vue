<script setup>
import FeaturesCard from '@/Components/FeaturesCard.vue'
import PricingCard from '@/Components/PricingCard.vue'
import Accordion from '@/Components/shadcn/ui/accordion/Accordion.vue'
import AccordionContent from '@/Components/shadcn/ui/accordion/AccordionContent.vue'
import AccordionItem from '@/Components/shadcn/ui/accordion/AccordionItem.vue'
import AccordionTrigger from '@/Components/shadcn/ui/accordion/AccordionTrigger.vue'
import Badge from '@/Components/shadcn/ui/badge/Badge.vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import Card from '@/Components/shadcn/ui/card/Card.vue'
import Switch from '@/Components/shadcn/ui/switch/Switch.vue'
import Terminal from '@/Components/Terminal.vue'
import Tabs from '@/Components/shadcn/ui/tabs/Tabs.vue'
import TabsContent from '@/Components/shadcn/ui/tabs/TabsContent.vue'
import TabsList from '@/Components/shadcn/ui/tabs/TabsList.vue'
import TabsTrigger from '@/Components/shadcn/ui/tabs/TabsTrigger.vue'
import Select from '@/Components/shadcn/ui/select/Select.vue'
import SelectContent from '@/Components/shadcn/ui/select/SelectContent.vue'
import SelectItem from '@/Components/shadcn/ui/select/SelectItem.vue'
import SelectTrigger from '@/Components/shadcn/ui/select/SelectTrigger.vue'
import SelectValue from '@/Components/shadcn/ui/select/SelectValue.vue'
import { useSeoMetaTags } from '@/Composables/useSeoMetaTags.js'
import WebLayout from '@/Layouts/WebLayout.vue'
import { Icon } from '@iconify/vue'
import { Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

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

// Check if there are multiple intervals available
const hasMultipleIntervals = computed(() => {
  const allIntervals = new Set()
  props.plans.forEach(plan => {
    plan.intervals?.forEach(interval => {
      allIntervals.add(interval.code)
    })
  })
  return allIntervals.size > 1
})

// Filter plans to show only the selected interval pricing
const plansWithSelectedInterval = computed(() => {
  return props.plans.map(plan => ({
    ...plan,
    intervals: plan.intervals?.filter(interval => interval.code === selectedInterval.value) || []
  }))
})

// Calculate average discount percentage for yearly plans
const averageYearlyDiscount = computed(() => {
  const plansWithBothIntervals = props.plans.filter(plan => {
    const hasMonth = plan.intervals?.some(i => i.code === 'month')
    const hasYear = plan.intervals?.some(i => i.code === 'year')
    return hasMonth && hasYear && !plan.metadata?.is_default
  })

  if (plansWithBothIntervals.length === 0) return 0

  const discounts = plansWithBothIntervals.map(plan => {
    const monthlyPrice = plan.intervals.find(i => i.code === 'month')?.price || 0
    const yearlyPrice = plan.intervals.find(i => i.code === 'year')?.price || 0
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

// Search filters state
const searchType = ref('atacado')
const searchFilters = ref({
  categoria: '',
  tipoLoja: '',
  cidade: '',
  estado: '',
  genero: ''
})

const categorias = ['Roupas', 'Calçados', 'Acessórios']
const tiposLoja = ['Física', 'Virtual']
const generos = ['Masculino', 'Feminino', 'Unissex']

function handleSearch() {
  console.log('Buscando com filtros:', {
    tipo: searchType.value,
    ...searchFilters.value
  })
  // Aqui você implementará a lógica de busca/navegação
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

const pricingFeatures = [
  'Production-ready Docker setup',
  'Advanced authentication system',
  'AI Integrations',
  'Payment integration ready',
  'API endpoints with Sanctum',
  'Comprehensive documentation',
]
const sponsorLinks = {
  github: 'https://github.com/sponsors/pushpak1300',
  x: 'https://x.com/pushpak1300',
}

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
    value: 'item-9',
    title: 'A Tá na Vitrine é segura?',
    content: 'Sim. Todos os dados são protegidos, e as comunicações ocorrem diretamente entre comprador e vendedor, sem intermediações nem acesso a informações sensíveis.',
  },
]

const githubUrl = 'https://github.com/shipfastlabs/larasonic-vue'
</script>

<template>
  <WebLayout :can-login="canLogin" :can-register="canRegister">
    <!-- Hero Section -->
    <section class="relative overflow-hidden border-b border-orange-200 bg-linear-to-r from-teal-900 via-teal-700 to-teal-900 py-10 sm:py-20">
      <div class="container mx-auto px-4 text-center">

        <!-- Main Heading -->
        <div class="mx-auto max-w-4xl">
          <h1
            class="text-4xl font-extrabold tracking-tight sm:text-5xl md:text-6xl lg:text-7xl"
            :style="{ contain: 'layout paint' }"
          >
            <span class="block text-white">Divulgue sua Vitrine</span>
            <span
              class="mt-2 block bg-linear-to-r from-yellow-500 via-rose-400 to-amber-500 bg-clip-text text-transparent"
            >
              No maior catálogo atacadista do Brasil
            </span>
          </h1>
        </div>

        <!-- Subtitle - Add priority hint -->
        <p
          class="mx-auto mt-6 max-w-2xl text-center text-base text-white sm:text-lg md:text-xl"
          :style="{ contain: 'layout paint' }"
          fetchpriority="high"
        > Sem comissão, sem complicação, mais visibilidade, mais contatos e mais oportunidades para o seu negócio.
        </p>
      </div>

      <!-- Background Effects -->
      <div
        class="absolute inset-0 -z-10 h-full w-full bg-[linear-gradient(to_right,#4f4f4f2e_1px,transparent_1px),linear-gradient(to_bottom,#4f4f4f2e_1px,transparent_1px)] bg-[size:14px_24px]"
      />
      <div
        class="absolute left-0 right-0 top-0 -z-10 m-auto h-[310px] w-[310px] rounded-full bg-primary/20 opacity-20 blur-[100px]"
      />
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

    <section id="pricing" class="border-t border-orange-200 bg-linear-to-r from-orange-100 via-amber-100/40 to-yellow-100/50">
      <div class="container mx-auto px-4 py-16 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mx-auto max-w-3xl text-center mb-12">
          <h2 class="text-center text-2xl font-bold tracking-tight sm:text-4xl">
            Escolha o Plano Ideal Para Você
          </h2>
          <p class="mx-auto mt-4 max-w-2xl text-center text-muted-foreground">
            Selecione o plano que melhor atende às necessidades da sua clínica ou petshop
          </p>

          <!-- Billing Toggle - Only show if multiple intervals exist -->
          <div v-if="hasMultipleIntervals" class="mt-8 flex items-center justify-center gap-4 flex-wrap">
            <span :class="!isYearly ? 'font-semibold text-foreground' : 'text-muted-foreground'">
              Mensal
            </span>
            <Switch
              v-model:checked="isYearly"
              class="data-[state=checked]:bg-primary"
            />
            <div class="flex items-center gap-2">
              <span :class="isYearly ? 'font-semibold text-foreground' : 'text-muted-foreground'">
                Anual
              </span>
              <Badge v-if="averageYearlyDiscount > 0" variant="secondary" class="animate-pulse">
                <Icon icon="lucide:trending-down" class="size-3 mr-1" aria-hidden="true" />
                {{ averageYearlyDiscount }}% OFF
              </Badge>
            </div>
          </div>
        </div>

        <!-- Pricing Cards Grid -->
        <div v-if="plansWithSelectedInterval.length > 0" class="grid gap-8 md:grid-cols-2 lg:grid-cols-2 mt-16 mx-auto max-w-3xl">
          <Card
            v-for="plan in plansWithSelectedInterval.filter(p => !p.metadata?.is_default)"
            :key="`${plan.id}-${selectedInterval}`"
            :class="[
              'relative flex flex-col transition-all duration-300 overflow-visible',
              plan.is_featured ? 'ring-2 ring-primary shadow-2xl md:scale-105' : 'hover:shadow-lg'
            ]"
          >
            <!-- Featured Badge -->
            <Badge v-if="plan.is_featured" class="absolute -top-3 left-1/2 -translate-x-1/2 z-10">
              <Icon icon="lucide:star" class="size-3 mr-1" aria-hidden="true" />
              Mais Popular
            </Badge>

            <div class="p-6 sm:p-8 flex flex-col h-full">
              <!-- Header -->
              <div class="text-center mb-6">
                <h3 class="text-2xl font-bold mb-2">
                  {{ plan.name }}
                </h3>
                <p class="text-sm text-muted-foreground">
                  {{ plan.description }}
                </p>
              </div>

              <!-- Price -->
              <div v-if="plan.intervals && plan.intervals.length > 0" class="text-center mb-6 pb-6 border-b">
                <div v-for="interval in plan.intervals" :key="interval.id">
                  <div class="text-4xl sm:text-5xl font-bold text-primary mb-2">
                    R$ {{ interval.price.toFixed(2).replace('.', ',') }}
                  </div>
                  <p class="text-sm font-medium text-muted-foreground">
                    {{ interval.name }}
                  </p>
                  <p v-if="isYearly" class="text-xs text-muted-foreground mt-1">
                    R$ {{ (interval.price / 12).toFixed(2).replace('.', ',') }}/mês
                  </p>
                </div>
              </div>

              <!-- CTA Button -->
              <div class="mb-6">
                <Button
                  v-for="interval in plan.intervals"
                  :key="`btn-${interval.id}`"
                  as="a"
                  :href="`/onboarding/start?plan=${interval.id}`"
                  class="w-full"
                  size="lg"
                  :variant="plan.is_featured ? 'default' : 'outline'"
                >
                  <Icon icon="lucide:zap" class="size-4 mr-2" aria-hidden="true" />
                  Escolher Este Plano
                </Button>
              </div>

              <!-- Features List -->
              <div class="flex-1">
                <ul class="space-y-3">
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
            />
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
