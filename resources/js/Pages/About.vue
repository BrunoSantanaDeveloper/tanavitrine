<script setup>
import Badge from '@/Components/shadcn/ui/badge/Badge.vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import Card from '@/Components/shadcn/ui/card/Card.vue'
import { useSeoMetaTags } from '@/Composables/useSeoMetaTags.js'
import WebLayout from '@/Layouts/WebLayout.vue'
import { Icon } from '@iconify/vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  canLogin: {
    type: Boolean,
  },
  canRegister: {
    type: Boolean,
  },
  seo: {
    type: Object,
    default: () => ({
      title: 'Sobre - TanaVitrine',
      description: 'Conheça a TanaVitrine, o maior marketplace de moda atacado e varejo do Brasil. Conectando fornecedores e lojistas.',
    }),
  },
})

useSeoMetaTags(props.seo)

const features = [
  {
    icon: 'lucide:users',
    title: 'Conecte-se com Fornecedores',
    description: 'Acesse um catálogo completo de fornecedores atacadistas e lojistas varejistas verificados em todo o Brasil.',
  },
  {
    icon: 'lucide:shield-check',
    title: 'Fornecedores Verificados',
    description: 'Todos os anunciantes passam por um processo de verificação para garantir segurança e qualidade nas transações.',
  },
  {
    icon: 'lucide:trending-up',
    title: 'Aumente suas Vendas',
    description: 'Divulgue seus produtos para milhares de compradores em potencial e expanda seu negócio de moda.',
  },
  {
    icon: 'lucide:smartphone',
    title: 'Plataforma Moderna',
    description: 'Interface intuitiva e responsiva, com filtros avançados para encontrar exatamente o que você procura.',
  },
]

const stats = [
  { value: '500+', label: 'Fornecedores Cadastrados' },
  { value: '50+', label: 'Cidades Atendidas' },
  { value: '10k+', label: 'Visitas Mensais' },
  { value: '95%', label: 'Satisfação dos Usuários' },
]

const benefits = {
  buyers: [
    'Encontre fornecedores atacadistas em um só lugar',
    'Compare preços e condições de pagamento',
    'Contato direto via WhatsApp',
    'Filtros avançados por categoria, localização e tipo',
    'Acesso gratuito a todos os fornecedores',
  ],
  sellers: [
    'Divulgue seus produtos para milhares de compradores',
    'Gerenciamento fácil da sua vitrine',
    'Analytics completo de visualizações e cliques',
    'Planos acessíveis para todos os tamanhos de negócio',
    'Destaque sua loja para mais visibilidade',
  ],
}
</script>

<template>
  <WebLayout :can-login="canLogin" :can-register="canRegister">
    <!-- Hero Section -->
    <section class="relative overflow-hidden border-b border-orange-200 bg-gradient-to-br from-teal-900 via-teal-700 to-teal-900 py-20 sm:py-28">
      <div class="container mx-auto px-4 text-center relative z-10">
        <!-- Badge -->
        <div class="mb-8 inline-flex justify-center">
          <Badge variant="outline" class="rounded-full border border-yellow-500 bg-primary/10 px-4 py-1 text-xs text-white sm:text-sm">
            <Icon icon="lucide:sparkles" class="size-4 mr-1" aria-hidden="true" />
            Sobre a TanaVitrine
          </Badge>
        </div>

        <!-- Main Heading -->
        <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl md:text-6xl lg:text-7xl text-white mb-6">
          O Maior Marketplace de<br>
          <span class="bg-gradient-to-r from-yellow-400 via-orange-400 to-pink-500 bg-clip-text text-transparent">
            Moda do Brasil
          </span>
        </h1>

        <!-- Subtitle -->
        <p class="mx-auto max-w-3xl text-lg sm:text-xl text-white/90 leading-relaxed">
          Conectamos fornecedores atacadistas e lojistas varejistas de moda em uma única plataforma,
          facilitando negócios e expandindo oportunidades em todo o país.
        </p>
      </div>

      <!-- Background Effects -->
      <div class="absolute inset-0 -z-10 h-full w-full bg-[linear-gradient(to_right,#4f4f4f2e_1px,transparent_1px),linear-gradient(to_bottom,#4f4f4f2e_1px,transparent_1px)] bg-[size:14px_24px]" />
      <div class="absolute left-0 right-0 top-0 -z-10 m-auto h-[310px] w-[310px] rounded-full bg-primary/20 opacity-20 blur-[100px]" />
    </section>

    <!-- Stats Section -->
    <section class="py-12 bg-muted/30">
      <div class="container mx-auto px-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
          <div v-for="stat in stats" :key="stat.label" class="text-center">
            <div class="text-4xl font-bold text-primary mb-2">{{ stat.value }}</div>
            <div class="text-sm text-muted-foreground">{{ stat.label }}</div>
          </div>
        </div>
      </div>
    </section>

    <!-- About Section -->
    <section class="py-20 bg-background">
      <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
          <h2 class="text-3xl font-bold tracking-tight sm:text-4xl text-center mb-8">
            Nossa Missão
          </h2>

          <div class="space-y-6 text-lg text-muted-foreground leading-relaxed">
            <p>
              A <span class="font-semibold text-foreground">TanaVitrine</span> nasceu com o objetivo de revolucionar
              a forma como fornecedores e compradores de moda se conectam no Brasil. Sabemos que encontrar fornecedores
              confiáveis no atacado ou descobrir novas lojas no varejo pode ser desafiador.
            </p>

            <p>
              Por isso, criamos uma plataforma que centraliza milhares de fornecedores verificados de roupas, calçados,
              acessórios e muito mais. Tudo em um só lugar, com filtros inteligentes, contato direto e total transparência.
            </p>

            <p>
              Nossa missão é <span class="font-semibold text-foreground">democratizar o acesso ao mercado de moda</span>,
              permitindo que pequenos e grandes empreendedores tenham as mesmas oportunidades de crescimento e sucesso.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Grid -->
    <section class="py-20 bg-muted/30">
      <div class="container mx-auto px-4">
        <h2 class="text-center text-3xl font-bold tracking-tight sm:text-4xl mb-12">
          Por Que Escolher a TanaVitrine?
        </h2>

        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4 mt-12">
          <Card v-for="feature in features" :key="feature.title" class="p-6 text-center hover:shadow-lg transition-shadow">
            <div class="flex justify-center mb-4">
              <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/10">
                <Icon :icon="feature.icon" class="size-8 text-primary" />
              </div>
            </div>
            <h3 class="text-xl font-semibold mb-3">{{ feature.title }}</h3>
            <p class="text-muted-foreground">{{ feature.description }}</p>
          </Card>
        </div>
      </div>
    </section>

    <!-- Benefits Section -->
    <section class="py-20 bg-background">
      <div class="container mx-auto px-4">
        <h2 class="text-center text-3xl font-bold tracking-tight sm:text-4xl mb-16">
          Benefícios Para Todos
        </h2>

        <div class="grid md:grid-cols-2 gap-12 max-w-6xl mx-auto">
          <!-- Para Compradores -->
          <Card class="p-8">
            <div class="flex items-center gap-3 mb-6">
              <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-500/10">
                <Icon icon="lucide:shopping-bag" class="size-6 text-blue-600" />
              </div>
              <h3 class="text-2xl font-bold">Para Compradores</h3>
            </div>
            <ul class="space-y-3">
              <li v-for="benefit in benefits.buyers" :key="benefit" class="flex items-start gap-3">
                <Icon icon="lucide:check-circle" class="size-5 text-green-600 mt-0.5 flex-shrink-0" />
                <span class="text-muted-foreground">{{ benefit }}</span>
              </li>
            </ul>
            <Button as="a" href="/atacado" class="w-full mt-6" size="lg">
              <Icon icon="lucide:search" class="size-4 mr-2" />
              Encontrar Fornecedores
            </Button>
          </Card>

          <!-- Para Fornecedores -->
          <Card class="p-8">
            <div class="flex items-center gap-3 mb-6">
              <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-orange-500/10">
                <Icon icon="lucide:store" class="size-6 text-orange-600" />
              </div>
              <h3 class="text-2xl font-bold">Para Fornecedores</h3>
            </div>
            <ul class="space-y-3">
              <li v-for="benefit in benefits.sellers" :key="benefit" class="flex items-start gap-3">
                <Icon icon="lucide:check-circle" class="size-5 text-green-600 mt-0.5 flex-shrink-0" />
                <span class="text-muted-foreground">{{ benefit }}</span>
              </li>
            </ul>
            <Button as="a" href="/prices" class="w-full mt-6" size="lg" variant="secondary">
              <Icon icon="lucide:rocket" class="size-4 mr-2" />
              Anunciar Minha Loja
            </Button>
          </Card>
        </div>
      </div>
    </section>

    <!-- How It Works Section -->
    <section class="py-20 bg-muted/30">
      <div class="container mx-auto px-4">
        <div class="text-center mb-16">
          <p class="text-sm font-medium tracking-wider text-muted-foreground uppercase mb-2">
            SIMPLES E RÁPIDO
          </p>
          <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">
            Como Funciona?
          </h2>
        </div>

        <div class="grid gap-8 md:grid-cols-4 mt-12">
          <!-- Step 1 -->
          <div class="flex flex-col items-center text-center">
            <div class="relative mb-6">
              <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-primary/10">
                <Icon icon="lucide:user-plus" class="size-10 text-primary" />
              </div>
              <Badge class="absolute -bottom-2 -right-2 h-8 w-8 rounded-full flex items-center justify-center p-0 text-lg font-bold">
                1
              </Badge>
            </div>
            <h3 class="text-xl font-bold mb-4">Cadastre-se</h3>
            <p class="text-muted-foreground">
              Crie sua conta gratuita em menos de 2 minutos
            </p>
          </div>

          <!-- Step 2 -->
          <div class="flex flex-col items-center text-center">
            <div class="relative mb-6">
              <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-primary/10">
                <Icon icon="lucide:store" class="size-10 text-primary" />
              </div>
              <Badge class="absolute -bottom-2 -right-2 h-8 w-8 rounded-full flex items-center justify-center p-0 text-lg font-bold">
                2
              </Badge>
            </div>
            <h3 class="text-xl font-bold mb-4">Configure sua Vitrine</h3>
            <p class="text-muted-foreground">
              Adicione fotos, descrição e informações da sua loja
            </p>
          </div>

          <!-- Step 3 -->
          <div class="flex flex-col items-center text-center">
            <div class="relative mb-6">
              <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-primary/10">
                <Icon icon="lucide:credit-card" class="size-10 text-primary" />
              </div>
              <Badge class="absolute -bottom-2 -right-2 h-8 w-8 rounded-full flex items-center justify-center p-0 text-lg font-bold">
                3
              </Badge>
            </div>
            <h3 class="text-xl font-bold mb-4">Escolha seu Plano</h3>
            <p class="text-muted-foreground">
              Selecione o plano ideal para o seu negócio
            </p>
          </div>

          <!-- Step 4 -->
          <div class="flex flex-col items-center text-center">
            <div class="relative mb-6">
              <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-primary/10">
                <Icon icon="lucide:rocket" class="size-10 text-primary" />
              </div>
              <Badge class="absolute -bottom-2 -right-2 h-8 w-8 rounded-full flex items-center justify-center p-0 text-lg font-bold">
                4
              </Badge>
            </div>
            <h3 class="text-xl font-bold mb-4">Comece a Vender</h3>
            <p class="text-muted-foreground">
              Sua vitrine fica ativa imediatamente após o pagamento
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-gradient-to-br from-teal-900 to-teal-700">
      <div class="container mx-auto px-4 text-center">
        <h2 class="text-3xl font-bold tracking-tight sm:text-4xl text-white mb-6">
          Pronto para Expandir Seu Negócio?
        </h2>
        <p class="text-lg text-white/90 mb-8 max-w-2xl mx-auto">
          Junte-se a centenas de fornecedores que já estão vendendo mais com a TanaVitrine
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <Button as="a" href="/prices" size="lg" variant="secondary" class="text-lg">
            <Icon icon="lucide:store" class="size-5 mr-2" />
            Ver Planos
          </Button>
          <Button as="a" href="/atacado" size="lg" variant="outline" class="text-lg border-white text-white hover:bg-white/10">
            <Icon icon="lucide:search" class="size-5 mr-2" />
            Explorar Fornecedores
          </Button>
        </div>
      </div>
    </section>
  </WebLayout>
</template>
