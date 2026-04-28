<script setup>
import Badge from '@/Components/shadcn/ui/badge/Badge.vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import Skeleton from '@/Components/shadcn/ui/skeleton/Skeleton.vue'
import { useSeoMetaTags } from '@/Composables/useSeoMetaTags.js'
import WebLayout from '@/Layouts/WebLayout.vue'
import { Icon } from '@iconify/vue'
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'

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
  lastUpdated: {
    type: String,
    default: '',
  },
  metrics: {
    type: Array,
    default: () => [],
  },
})

useSeoMetaTags(props.seo)

const numberFormatter = new Intl.NumberFormat('pt-BR')

const formattedMetrics = computed(() => {
  return props.metrics.map((metric) => {
    const parsedValue = Number(metric.value)
    const hasNumericValue = Number.isFinite(parsedValue)

    return {
      ...metric,
      formattedValue: hasNumericValue ? numberFormatter.format(parsedValue) : metric.value,
    }
  })
})

const hasMetrics = computed(() => formattedMetrics.value.length > 0)
const metricSkeletonItems = [1, 2, 3, 4]

const howItWorks = [
  {
    icon: 'lucide:building-2',
    title: 'Cadastro Exclusivo',
    description: 'Lojas se cadastram na plataforma com um processo direto e sem fricção.',
  },
  {
    icon: 'lucide:store',
    title: 'Monte sua Vitrine',
    description: 'Publique fotos, catálogo e informações essenciais para atrair o cliente certo.',
  },
  {
    icon: 'lucide:smartphone',
    title: 'Contato Direto',
    description: 'Clientes encontram seus produtos e chamam você no WhatsApp para fechar negócio.',
  },
]

const sellerBenefits = [
  {
    title: 'Mais visibilidade',
    description: 'Apareça para lojistas que estão procurando fornecedores todos os dias.',
  },
  {
    title: 'Leads qualificados',
    description: 'Receba contatos de quem realmente tem intenção de compra.',
  },
  {
    title: 'Presença profissional',
    description: 'Tenha uma vitrine com aparência premium sem depender de time técnico.',
  },
]

const buyerBenefits = [
  {
    title: 'Fornecedores confiáveis',
    description: 'Consulte rapidamente o perfil de fábricas e atacadistas em um só lugar.',
  },
  {
    title: 'Acesso direto no WhatsApp',
    description: 'Sem etapas longas: encontrou, gostou e iniciou a negociação.',
  },
  {
    title: 'Variedade de nichos',
    description: 'Explore diferentes segmentos de moda com filtros simples e objetivos.',
  },
]

const differentials = [
  {
    icon: 'lucide:zap',
    title: 'Zero Comissão',
    description: 'Sem taxa sobre vendas geradas na plataforma.',
  },
  {
    icon: 'lucide:users',
    title: 'Contato Direto',
    description: 'Sem intermediário entre você e seu cliente.',
  },
  {
    icon: 'lucide:award',
    title: 'Foco no Setor',
    description: 'Uma plataforma pensada para o mercado da moda.',
  },
  {
    icon: 'lucide:check-circle-2',
    title: 'Simples e Rápido',
    description: 'Sua vitrine online ativa em poucos minutos.',
  },
]

const missionCards = [
  {
    icon: 'lucide:globe-2',
    title: 'Nossa Missão',
    description: 'Conectar de forma eficiente o mercado da moda no Brasil, aproximando quem produz com qualidade de quem busca os melhores produtos para vender.',
    classes: 'border-zinc-200 bg-zinc-100/80',
    iconClasses: 'text-zinc-700',
  },
  {
    icon: 'lucide:trending-up',
    title: 'Nossa Visão',
    description: 'Ser a principal referência digital para fornecedores de moda do país, fortalecendo a economia do setor com conexões comerciais relevantes.',
    classes: 'border-emerald-200 bg-gradient-to-br from-emerald-100 to-zinc-50',
    iconClasses: 'text-emerald-600',
  },
]

const brandPillars = [
  {
    icon: 'lucide:shield-check',
    title: 'Confiança',
    description: 'Relacionamentos comerciais com transparência e contato direto entre as partes.',
  },
  {
    icon: 'lucide:gauge',
    title: 'Eficiência',
    description: 'Menos etapas, mais velocidade para descobrir, apresentar e negociar coleções.',
  },
  {
    icon: 'lucide:sparkles',
    title: 'Curadoria',
    description: 'Foco no mercado de moda para gerar conexões realmente relevantes para o setor.',
  },
]
</script>

<template>
  <WebLayout :can-login="canLogin" :can-register="canRegister" :show-floating-whats-app="true">
    <div class="bg-gradient-to-b from-teal-950 via-[#072b2b] to-teal-950 text-zinc-50">
      <section class="relative overflow-hidden border-b border-teal-800/60 pt-20 pb-28">
        <div class="absolute inset-0 bg-[url('/images/home_about.png')] bg-cover bg-center" />
        <div class="absolute inset-0 bg-black/30" />
        <div class="absolute inset-0 bg-[linear-gradient(to_right,rgba(255,255,255,0.05)_1px,transparent_1px),linear-gradient(to_bottom,rgba(255,255,255,0.05)_1px,transparent_1px)] bg-[size:24px_24px] opacity-15" />

        <div class="container relative z-10 mx-auto px-4">
          <div class="mx-auto max-w-4xl text-center">
            <Badge variant="outline" class="mb-8 rounded-full border-teal-700/70 bg-teal-900/40 px-4 py-1 text-zinc-200">
              <span class="mr-2 inline-flex h-2 w-2 rounded-full bg-orange-500" />
              Sobre o Tá na Vitrine
            </Badge>

            <h1 class="text-5xl font-bold leading-tight tracking-tight text-white sm:text-6xl lg:text-7xl">
              A vitrine digital para
              <span class="bg-gradient-to-r from-emerald-400 to-teal-300 bg-clip-text text-transparent"> fornecedores de moda </span>
              no Brasil
            </h1>

            <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-zinc-300 sm:text-xl">
              Conectamos lojistas, fornecedores e fabricantes de forma simples, rápida e direta,
              sem intermediários no caminho da negociação.
            </p>

            <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
              <Button :as="Link" href="/prices" size="lg" class="w-full bg-orange-500 text-white hover:bg-orange-600 sm:w-auto">
                Cadastrar minha vitrine
                <Icon icon="lucide:arrow-right" class="ml-2 size-5" />
              </Button>
              <Button :as="Link" href="/atacado" size="lg" variant="outline" class="w-full border-teal-700/70 bg-teal-950/60 text-zinc-200 hover:bg-teal-900/60 sm:w-auto">
                Explorar fornecedores
              </Button>
            </div>

          </div>
        </div>
      </section>

      <section class="border-y border-teal-100 bg-[#eef6f4] py-20">
        <div class="container mx-auto px-4">
          <div class="grid items-center gap-12 lg:grid-cols-2">
            <div>
              <h2 class="text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl">
                Não somos um marketplace tradicional
              </h2>
              <p class="mt-6 text-lg leading-relaxed text-zinc-700">
                O Tá na Vitrine é um hub digital para gerar conexão comercial entre quem compra e quem vende moda.
                A venda acontece direto entre as partes.
              </p>

              <ul class="mt-8 space-y-4">
                <li class="flex items-start gap-3 text-zinc-700">
                  <Icon icon="lucide:check-circle-2" class="mt-0.5 size-5 text-orange-500" />
                  <span>Sem intermediação de pagamento nas suas vendas.</span>
                </li>
                <li class="flex items-start gap-3 text-zinc-700">
                  <Icon icon="lucide:check-circle-2" class="mt-0.5 size-5 text-orange-500" />
                  <span>Vitrine criada para gerar leads e oportunidades reais.</span>
                </li>
                <li class="flex items-start gap-3 text-zinc-700">
                  <Icon icon="lucide:check-circle-2" class="mt-0.5 size-5 text-orange-500" />
                  <span>Contato direto no WhatsApp entre cliente e loja.</span>
                </li>
              </ul>
            </div>

            <div class="rounded-3xl border border-teal-200 bg-gradient-to-br from-teal-50 to-zinc-100 p-8 shadow-sm">
              <div class="grid grid-cols-2 gap-4">
                <div class="rounded-2xl border border-teal-200 bg-zinc-50 p-4 text-center">
                  <Icon icon="lucide:store" class="mx-auto mb-2 size-8 text-emerald-400" />
                  <p class="text-sm font-medium text-zinc-700">Fornecedor</p>
                </div>
                <div class="rounded-2xl border border-teal-200 bg-zinc-50 p-4 text-center">
                  <Icon icon="lucide:search" class="mx-auto mb-2 size-8 text-orange-500" />
                  <p class="text-sm font-medium text-zinc-700">Lojista</p>
                </div>
              </div>
              <div class="mt-4 rounded-2xl border border-teal-200 bg-zinc-50 p-4 text-center">
                <Icon icon="lucide:message-circle" class="mx-auto mb-2 size-7 text-emerald-500" />
                <p class="text-sm font-medium text-zinc-700">Conexão direta e sem fricção</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="border-y border-teal-800/60 bg-gradient-to-b from-teal-900 to-teal-950 py-24">
        <div class="container mx-auto px-4">
          <div class="mx-auto mb-14 max-w-2xl text-center">
            <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">Como funciona</h2>
            <p class="mt-4 text-lg text-teal-100">Três passos simples para conectar oferta e demanda com velocidade.</p>
          </div>

          <div class="relative grid gap-6 md:grid-cols-3">
            <div class="pointer-events-none absolute top-7 left-[17%] right-[17%] hidden h-px bg-gradient-to-r from-transparent via-teal-700 to-transparent md:block" />
            <article
              v-for="(step, index) in howItWorks"
              :key="step.title"
              class="relative rounded-3xl border border-teal-700/70 bg-teal-900/60 p-7 shadow-[0_12px_40px_rgba(0,0,0,0.25)] backdrop-blur-sm"
            >
              <div class="absolute top-0 left-8 h-1 w-14 rounded-b-full bg-orange-500/80" />
              <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-2xl border border-teal-700 bg-teal-950/80">
                <Icon :icon="step.icon" class="size-7 text-orange-500" />
              </div>
              <span class="absolute top-6 right-6 text-5xl font-black text-teal-700/60">0{{ index + 1 }}</span>
              <h3 class="text-xl font-semibold text-white">{{ step.title }}</h3>
              <p class="mt-3 leading-relaxed text-teal-100">{{ step.description }}</p>
            </article>
          </div>
        </div>
      </section>

      <section class="border-y border-orange-100/80 bg-[#f7f2eb] py-24">
        <div class="container mx-auto px-4">
          <div class="grid gap-6 lg:grid-cols-2">
            <article class="rounded-[2rem] border border-zinc-200 bg-zinc-100 p-8 sm:p-10 shadow-sm">
              <Badge class="mb-6 rounded-full bg-emerald-500/10 text-emerald-700">Para quem anuncia</Badge>
              <h3 class="text-3xl font-bold text-zinc-900">Escale seus resultados com visibilidade qualificada</h3>
              <ul class="mt-8 space-y-5">
                <li v-for="benefit in sellerBenefits" :key="benefit.title" class="flex items-start gap-3">
                  <Icon icon="lucide:check-circle-2" class="mt-1 size-5 text-emerald-500" />
                  <div>
                    <p class="font-semibold text-zinc-900">{{ benefit.title }}</p>
                    <p class="text-zinc-600">{{ benefit.description }}</p>
                  </div>
                </li>
              </ul>
            </article>

            <article class="rounded-[2rem] border border-orange-200 bg-gradient-to-br from-orange-100 to-zinc-100 p-8 sm:p-10 shadow-sm">
              <Badge class="mb-6 rounded-full bg-orange-500/10 text-orange-700">Para quem busca</Badge>
              <h3 class="text-3xl font-bold text-zinc-900">Encontre as melhores coleções do mercado</h3>
              <ul class="mt-8 space-y-5">
                <li v-for="benefit in buyerBenefits" :key="benefit.title" class="flex items-start gap-3">
                  <Icon icon="lucide:check-circle-2" class="mt-1 size-5 text-orange-500" />
                  <div>
                    <p class="font-semibold text-zinc-900">{{ benefit.title }}</p>
                    <p class="text-zinc-600">{{ benefit.description }}</p>
                  </div>
                </li>
              </ul>
            </article>
          </div>
        </div>
      </section>

      <section class="bg-gradient-to-b from-[#ecf4f3] to-[#f6fbfa] py-24">
        <div class="container mx-auto px-4">
          <div class="mx-auto mb-14 max-w-2xl text-center">
            <h2 class="text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl">Por que Tá na Vitrine</h2>
            <p class="mt-4 text-lg text-zinc-600">Diferenciais para aumentar resultado sem complicar sua operação.</p>
          </div>

          <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <article v-for="item in differentials" :key="item.title" class="rounded-3xl border border-teal-100 bg-white/70 p-6 shadow-sm">
              <Icon :icon="item.icon" class="mb-4 size-9 text-emerald-500" />
              <h3 class="text-lg font-semibold text-zinc-900">{{ item.title }}</h3>
              <p class="mt-2 text-zinc-600">{{ item.description }}</p>
            </article>
          </div>
        </div>
      </section>

      <section class="relative overflow-hidden border-y border-teal-800/60 bg-teal-950/85 py-16">
        <div class="absolute inset-0 bg-[linear-gradient(to_right,rgba(255,255,255,0.04)_1px,transparent_1px),linear-gradient(to_bottom,rgba(255,255,255,0.04)_1px,transparent_1px)] bg-[size:24px_24px]" />
        <div class="container relative mx-auto px-4">
          <p class="mb-4 text-center text-sm text-zinc-400">
            Dados da plataforma atualizados em {{ lastUpdated || 'processamento' }}.
          </p>

          <div v-if="hasMetrics" class="grid gap-8 md:grid-cols-4">
            <article v-for="metric in formattedMetrics" :key="metric.key" class="rounded-2xl border border-teal-900/70 bg-teal-950/55 p-6 text-center">
              <p class="text-4xl font-bold text-white lg:text-5xl">{{ metric.formattedValue }}</p>
              <p class="mt-2 font-medium text-emerald-300">{{ metric.label }}</p>
              <p class="mt-2 text-sm text-zinc-400">{{ metric.description }}</p>
            </article>
          </div>

          <div v-else class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
              <div
                v-for="item in metricSkeletonItems"
                :key="item"
                class="rounded-2xl border border-teal-900/70 bg-teal-950/55 p-5"
              >
                <Skeleton class="h-4 w-2/3 bg-teal-800/70" />
                <Skeleton class="mt-3 h-8 w-1/2 bg-teal-800/70" />
                <Skeleton class="mt-4 h-3 w-full bg-teal-800/70" />
                <Skeleton class="mt-2 h-3 w-5/6 bg-teal-800/70" />
              </div>
            </div>
            <p class="text-center text-sm text-zinc-400">Atualizando métricas da plataforma...</p>
          </div>
        </div>
      </section>

      <section class="bg-[#f2f4f6] py-24">
        <div class="container mx-auto px-4">
          <div class="mx-auto mb-12 max-w-3xl text-center">
            <Badge class="rounded-full bg-teal-100 text-teal-800">Nossa essência</Badge>
            <h2 class="mt-5 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl">Uma marca construída para fortalecer o atacado de moda</h2>
            <p class="mt-4 text-lg text-zinc-600">
              Combinamos tecnologia, foco setorial e relacionamento comercial direto para acelerar negócios com qualidade.
            </p>
          </div>

          <div class="grid gap-8 lg:grid-cols-3">
            <article
              v-for="card in missionCards"
              :key="card.title"
              class="rounded-[2rem] border p-8"
              :class="card.classes"
            >
              <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-xl bg-zinc-100">
                <Icon :icon="card.icon" class="size-6" :class="card.iconClasses" />
              </div>
              <h3 class="text-2xl font-bold text-zinc-900">{{ card.title }}</h3>
              <p class="mt-4 text-lg leading-relaxed text-zinc-700">{{ card.description }}</p>
            </article>

            <article class="rounded-[2rem] border border-teal-200 bg-gradient-to-b from-teal-50 to-zinc-100 p-8">
              <h3 class="text-2xl font-bold text-zinc-900">Nossos princípios</h3>
              <ul class="mt-6 space-y-5">
                <li v-for="pillar in brandPillars" :key="pillar.title" class="flex items-start gap-3">
                  <div class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-lg border border-teal-200 bg-white">
                    <Icon :icon="pillar.icon" class="size-4 text-teal-700" />
                  </div>
                  <div>
                    <p class="font-semibold text-zinc-900">{{ pillar.title }}</p>
                    <p class="text-sm leading-relaxed text-zinc-600">{{ pillar.description }}</p>
                  </div>
                </li>
              </ul>
            </article>
          </div>
        </div>
      </section>

      <section class="relative overflow-hidden py-28">
        <div class="absolute inset-0 bg-orange-500/5" />
        <div class="absolute left-1/2 top-1/2 h-[340px] w-full max-w-4xl -translate-x-1/2 -translate-y-1/2 rounded-full bg-orange-500/15 blur-[120px]" />

        <div class="container relative mx-auto max-w-4xl px-4 text-center">
          <h2 class="text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
            Sua loja já está na
            <span class="text-orange-500"> vitrine certa? </span>
          </h2>
          <p class="mx-auto mt-6 max-w-2xl text-xl text-zinc-300">
            Junte-se às lojas que já recebem contatos qualificados diretamente no WhatsApp.
          </p>
          <Button :as="Link" href="/prices" size="lg" class="mt-10 bg-orange-500 px-10 py-5 text-lg font-bold text-white hover:bg-orange-600">
            Cadastrar minha vitrine agora
            <Icon icon="lucide:arrow-right" class="ml-3 size-6" />
          </Button>
        </div>
      </section>
    </div>
  </WebLayout>
</template>
