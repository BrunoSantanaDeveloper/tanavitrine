<script setup>
import FeaturesCard from '@/Components/FeaturesCard.vue'
import PricingCard from '@/Components/PricingCard.vue'
import StoreCard from '@/Components/StoreCard.vue'
import WeeklyFeaturedStores from '@/Components/WeeklyFeaturedStores.vue'
import Accordion from '@/Components/shadcn/ui/accordion/Accordion.vue'
import AccordionContent from '@/Components/shadcn/ui/accordion/AccordionContent.vue'
import AccordionItem from '@/Components/shadcn/ui/accordion/AccordionItem.vue'
import AccordionTrigger from '@/Components/shadcn/ui/accordion/AccordionTrigger.vue'
import HomeHero from '@/Components/HomeHero.vue'
import Switch from '@/Components/shadcn/ui/switch/Switch.vue'
import Terminal from '@/Components/Terminal.vue'
import { useSeoMetaTags } from '@/Composables/useSeoMetaTags.js'
import WebLayout from '@/Layouts/WebLayout.vue'
import { Icon } from '@iconify/vue'
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  hero: {
    type: Object,
    required: true,
  },
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

const hasHeroVideo = computed(() => props.hero.background_type === 'video' && !!props.hero.video_url)

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
  <WebLayout :can-login="canLogin" :can-register="canRegister" :show-floating-whats-app="false" :overlay-hero-header="hasHeroVideo">
    <HomeHero
      :settings="hero"
      :quick-search-subcategories="quickSearchSubcategories"
      :single-card="!weeklyFeaturedStores.length && featuredStores.length > 0"
      :under-header="hasHeroVideo"
      :class="{ 'home-hero-with-cards': hasHeroVideo && (weeklyFeaturedStores.length || featuredStores.length) }"
    />

    <WeeklyFeaturedStores
      :stores="weeklyFeaturedStores"
      :class="{ 'home-cards-overlap': hasHeroVideo && weeklyFeaturedStores.length > 0 }"
    />

    <!-- Store Listings Section -->
    <section
      class="bg-white pb-16 pt-4 sm:pt-16 lg:pt-6"
      :class="{ 'home-cards-overlap': hasHeroVideo && !weeklyFeaturedStores.length && featuredStores.length > 0 }"
      aria-label="Vitrines em destaque"
    >
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
.home-hero-with-cards,
.home-cards-overlap {
  --hero-card-overlap: clamp(1.5rem, 3vw, 2.5rem);
}

.home-hero-with-cards {
  padding-bottom: calc(1.5rem + var(--hero-card-overlap));
}

.home-cards-overlap {
  position: relative;
  z-index: 20;
  margin-top: calc(-1 * var(--hero-card-overlap));
  padding-top: 0;
  background: linear-gradient(to bottom, transparent var(--hero-card-overlap), white var(--hero-card-overlap));
}
</style>
