<script setup>
import FeaturesCard from '@/Components/FeaturesCard.vue'
import PricingCard from '@/Components/PricingCard.vue'
import StoreCard from '@/Components/StoreCard.vue'
import WeeklyFeaturedStores from '@/Components/WeeklyFeaturedStores.vue'
import Accordion from '@/Components/shadcn/ui/accordion/Accordion.vue'
import AccordionContent from '@/Components/shadcn/ui/accordion/AccordionContent.vue'
import AccordionItem from '@/Components/shadcn/ui/accordion/AccordionItem.vue'
import AccordionTrigger from '@/Components/shadcn/ui/accordion/AccordionTrigger.vue'
import Badge from '@/Components/shadcn/ui/badge/Badge.vue'
import Switch from '@/Components/shadcn/ui/switch/Switch.vue'
import Terminal from '@/Components/Terminal.vue'
import { useSeoMetaTags } from '@/Composables/useSeoMetaTags.js'
import WebLayout from '@/Layouts/WebLayout.vue'
import { Icon } from '@iconify/vue'
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

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
  featuredStores: {
    type: Array,
    default: () => [],
  },
  weeklyFeaturedStores: {
    type: Array,
    default: () => [],
  },
  quickSearchSubcategories: {
    type: Array,
    default: () => [],
  },
  categoriesAtacado: {
    type: Array,
    default: () => [],
  },
  categoriesVarejo: {
    type: Array,
    default: () => [],
  },
  states: {
    type: Array,
    default: () => [],
  },
})

useSeoMetaTags(props.seo)

function quickSearchHref(subcategory) {
  return `/buscar?${new URLSearchParams({ 'subcategorias[]': subcategory })}`
}

// Toggle state for pricing interval (false = monthly, true = yearly)
const isYearly = ref(false)

// Computed property to get the selected interval based on toggle
const selectedInterval = computed(() => isYearly.value ? 'year' : 'month')

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
  const message = 'Olá! Gostaria de falar com um especialista sobre a Tanavitrine.'
  const url = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`
  window.open(url, '_blank')
}

const showBackToTop = ref(false)
const lastScrollTop = ref(0)

function handleBackToTopVisibility() {
  const scrollTop = window.scrollY || document.documentElement.scrollTop || document.body.scrollTop || 0
  const delta = scrollTop - lastScrollTop.value

  if (scrollTop <= 180) {
    showBackToTop.value = false
    lastScrollTop.value = Math.max(scrollTop, 0)
    return
  }

  if (delta > 6) {
    showBackToTop.value = true
  } else if (delta < -6) {
    showBackToTop.value = false
  }

  lastScrollTop.value = Math.max(scrollTop, 0)
}

function scrollToTop() {
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

onMounted(() => {
  window.addEventListener('scroll', handleBackToTopVisibility, { passive: true })
  document.addEventListener('scroll', handleBackToTopVisibility, { passive: true, capture: true })
  handleBackToTopVisibility()
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', handleBackToTopVisibility)
  document.removeEventListener('scroll', handleBackToTopVisibility, true)
})

</script>

<template>
  <WebLayout :can-login="canLogin" :can-register="canRegister" :show-floating-whats-app="false">
    <!-- Hero Section -->
    <section
      class="relative overflow-hidden bg-white pb-4 pt-6 sm:py-12 lg:pb-6 lg:pt-8"
      :class="{ 'home-hero-single-card': !weeklyFeaturedStores.length && featuredStores.length > 0 }"
    >
      <div class="site-shell relative z-10 lg:max-w-[1600px]">
        <div class="mx-auto max-w-3xl">
          <div class="text-center">
            <!-- Badge -->
            <div class="mb-4 inline-flex justify-center sm:mb-6">
              <Badge variant="outline" class="rounded-full border border-teal-100 bg-teal-50 px-4 py-1 text-xs text-teal-800 sm:text-sm">
                <Icon icon="lucide:award" class="size-4" aria-hidden="true" /> Fornecedores Verificados
              </Badge>
            </div>

            <!-- Main Heading -->
            <div class="mx-auto max-w-3xl">
              <h1
                class="text-[clamp(1.625rem,8vw,2.125rem)] font-extrabold leading-[1.08] tracking-tight sm:text-5xl lg:text-[2.65rem] xl:text-5xl 2xl:text-[3.4rem]"
                :style="{ contain: 'layout paint' }"
              >
                <span class="block text-teal-950">Encontre fornecedores</span>
                <span class="mt-1 block text-teal-700 sm:mt-2">
                  em poucos cliques
                </span>
              </h1>
            </div>

            <!-- Subtitle - Add priority hint -->
            <p
              class="mx-auto mt-4 max-w-2xl text-center text-sm font-medium leading-5 text-slate-600 sm:mt-6 sm:text-lg sm:leading-normal"
              :style="{ contain: 'layout paint' }"
            >
              Tá na Vitrine é o maior catálogo de fornecedores de moda do Brasil, com atacado e varejo e contato direto com quem vende.
            </p>

            <form
              action="/buscar"
              method="get"
              role="search"
              aria-label="Buscar lojas e fornecedores"
              class="mt-5 flex items-center gap-2 rounded-2xl border border-slate-200 bg-white p-1.5 shadow-[0_8px_28px_rgba(15,23,42,0.08)] sm:mt-8 sm:p-2"
            >
              <div class="relative min-w-0 flex-1">
                <label for="home-store-search" class="sr-only">Nome da loja, produto ou categoria</label>
                <Icon icon="lucide:search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400 sm:left-4 sm:size-5" aria-hidden="true" />
                <input
                  id="home-store-search"
                  type="search"
                  name="busca"
                  maxlength="150"
                  placeholder="Busque por loja, produto ou categoria (ex.: jeans)"
                  class="h-11 w-full rounded-xl border-0 bg-transparent pl-9 pr-2 text-sm text-teal-950 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-600/30 sm:h-12 sm:pl-12 sm:pr-4"
                >
              </div>
              <button
                type="submit"
                class="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-teal-800 px-3 py-2 text-sm font-bold text-white transition-colors hover:bg-teal-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-700 focus-visible:ring-offset-2 sm:min-h-12 sm:gap-3 sm:px-6 sm:py-3"
              >
                <span>Buscar<span class="hidden sm:inline"> lojas</span></span>
                <Icon icon="lucide:arrow-right" class="size-4" aria-hidden="true" />
              </button>
            </form>

            <nav v-if="quickSearchSubcategories.length" aria-label="Buscas rápidas por subcategoria" class="mt-3 flex max-w-full items-center gap-2 text-xs sm:mt-4 sm:justify-center">
              <span class="mr-1 shrink-0 whitespace-nowrap text-slate-500">Buscas rápidas:</span>
              <div class="flex min-w-0 flex-1 flex-nowrap items-center gap-2 overflow-x-auto overscroll-x-contain py-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:flex-initial sm:flex-wrap sm:justify-center sm:overflow-visible">
                <a
                  v-for="subcategory in quickSearchSubcategories"
                  :key="subcategory"
                  :href="quickSearchHref(subcategory)"
                  class="inline-flex min-h-8 shrink-0 items-center whitespace-nowrap rounded-lg bg-slate-50 px-3 py-1.5 font-medium text-teal-800 transition-colors hover:bg-teal-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-700"
                >
                  {{ subcategory }}
                </a>
              </div>
            </nav>

          </div>

        </div>
      </div>

    </section>

    <WeeklyFeaturedStores :stores="weeklyFeaturedStores" />

    <!-- Store Listings Section -->
    <section class="bg-white pb-16 pt-4 sm:pt-16 lg:pt-6" aria-label="Vitrines em destaque">
      <div class="site-shell">
        <div class="store-grid">
          <div
            v-for="store in featuredStores"
            :key="store.id"
            class="h-full"
          >
            <StoreCard :store="store" />
          </div>
        </div>

        <!-- No stores message -->
        <div v-if="featuredStores.length === 0" class="flex justify-center py-16">
          <div class="text-center">
            <Icon icon="lucide:store" class="size-16 mx-auto text-muted-foreground mb-4" />
            <p class="text-muted-foreground text-lg">
              Nenhuma vitrine em destaque no momento
            </p>
          </div>
        </div>
      </div>
    </section>

  </WebLayout>
  <button
    v-show="showBackToTop"
    type="button"
    class="fixed bottom-[calc(6rem+env(safe-area-inset-bottom))] left-1/2 z-40 -translate-x-1/2 inline-flex items-center gap-2 rounded-full border border-green-700 bg-green-600 px-4 py-2.5 shadow-lg transition hover:bg-green-700 hover:shadow-xl md:bottom-6"
    aria-label="Voltar ao topo"
    title="Voltar ao topo"
    @click="scrollToTop"
  >
    <Icon icon="lucide:arrow-up" class="size-4 text-white" />
    <span class="text-sm font-medium text-white">Voltar ao topo</span>
  </button>

</template>

<style scoped>
@media (max-width: 639px) {
  .home-hero-single-card {
    /* Reserve the header, bottom navigation, one 16:10 card and its spacing. */
    min-height: calc(100svh - 8rem - env(safe-area-inset-bottom, 0px) - (62.5vw - 1.25rem) - 1.5rem - 1px);
    display: flex;
    flex-direction: column;
    justify-content: center;
  }
}
</style>
