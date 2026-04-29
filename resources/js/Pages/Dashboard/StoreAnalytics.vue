<script setup>
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { Button } from '@/Components/shadcn/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import { Icon } from '@iconify/vue'

const props = defineProps({
  store: {
    type: Object,
    required: true,
  },
  leads: {
    type: Array,
    default: () => [],
  },
  availableMetrics: {
    type: Array,
    default: () => [],
  },
})

// Helper functions to check if metric is available
const hasMetric = (metric) => {
  return props.availableMetrics.includes(metric)
}

// Computed properties for available metrics
const showViews = computed(() => hasMetric('views'))
const showWhatsappClicks = computed(() => hasMetric('whatsapp_clicks'))
const showWebsiteClicks = computed(() => hasMetric('website_clicks'))
const showMapClicks = computed(() => hasMetric('map_clicks'))
const showShares = computed(() => hasMetric('shares'))
const showInstagramClicks = computed(() => hasMetric('instagram_clicks'))
const showFacebookClicks = computed(() => hasMetric('facebook_clicks'))
const showTikTokClicks = computed(() => hasMetric('tiktok_clicks'))

// Show upgrade message if not all metrics are available
const hasAllMetrics = computed(() => {
  const allMetrics = ['views', 'whatsapp_clicks', 'website_clicks', 'map_clicks', 'shares', 'instagram_clicks', 'facebook_clicks', 'tiktok_clicks']
  return allMetrics.every(metric => props.availableMetrics.includes(metric))
})

// Helper function to get metric value (real or placeholder)
const getMetricValue = (metric) => {
  if (hasMetric(metric)) {
    // Return real value based on metric name
    const metricMap = {
      'views': props.store.views_count,
      'whatsapp_clicks': props.store.whatsapp_clicks,
      'website_clicks': props.store.website_clicks,
      'map_clicks': props.store.map_clicks,
      'shares': props.store.shares_count,
      'instagram_clicks': props.store.instagram_clicks,
      'facebook_clicks': props.store.facebook_clicks,
      'tiktok_clicks': props.store.tiktok_clicks,
    }
    return metricMap[metric] || 0
  }

  // Return placeholder value for locked metrics
  const placeholders = {
    'website_clicks': 127,
    'map_clicks': 156,
    'shares': 43,
    'instagram_clicks': 234,
    'facebook_clicks': 178,
    'tiktok_clicks': 92,
  }
  return placeholders[metric] || 0
}
</script>

<template>
  <Head :title="`Analytics - ${store.name}`" />

  <AppLayout :title="`Analytics - ${store.name}`">
    <div class="min-h-screen bg-gray-50 p-6">
      <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
          <div class="flex items-center gap-2 text-sm text-muted-foreground mb-2">
            <Link :href="route('dashboard')" class="hover:text-teal-600 transition-colors">
              Dashboard
            </Link>
            <Icon icon="lucide:chevron-right" class="h-4 w-4" />
            <span class="text-gray-900 font-medium">Analytics</span>
          </div>
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-2xl font-bold text-gray-900">Analytics</h1>
              <p class="text-muted-foreground mt-1">{{ store.name }}</p>
            </div>
            <Button :as="Link" :href="route('dashboard')" variant="outline">
              <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
              Voltar
            </Button>
          </div>
        </div>

        <!-- Upgrade Banner (if not all metrics available) -->
        <Card v-if="!hasAllMetrics" class="mb-6 bg-gradient-to-r from-teal-50 to-cyan-50 border-teal-200">
          <CardContent class="py-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-teal-100 rounded-full flex items-center justify-center">
                  <Icon icon="lucide:trending-up" class="h-5 w-5 text-teal-600" />
                </div>
                <div>
                  <h3 class="font-semibold text-teal-900">Desbloqueie Analytics Completo</h3>
                  <p class="text-sm text-teal-700">
                    Faça upgrade para o plano Destaque e tenha acesso a todas as métricas de desempenho
                  </p>
                </div>
              </div>
              <Button :as="Link" :href="route('subscriptions.create')" size="sm" class="bg-teal-600 hover:bg-teal-700">
                <Icon icon="lucide:crown" class="mr-2 h-4 w-4" />
                Fazer Upgrade
              </Button>
            </div>
          </CardContent>
        </Card>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
          <!-- Views -->
          <Card v-if="showViews">
            <CardHeader class="flex flex-row items-center justify-between pb-2">
              <CardTitle class="text-sm font-medium text-muted-foreground">
                Visualizações Totais
              </CardTitle>
              <Icon icon="lucide:eye" class="h-5 w-5 text-teal-600" />
            </CardHeader>
            <CardContent>
              <div class="text-3xl font-bold">{{ store.views_count || 0 }}</div>
              <p class="text-xs text-muted-foreground mt-1">
                Número de visitas na sua vitrine
              </p>
            </CardContent>
          </Card>

          <!-- WhatsApp Clicks -->
          <Card v-if="showWhatsappClicks">
            <CardHeader class="flex flex-row items-center justify-between pb-2">
              <CardTitle class="text-sm font-medium text-muted-foreground">
                Cliques WhatsApp
              </CardTitle>
              <Icon icon="lucide:message-circle" class="h-5 w-5 text-green-600" />
            </CardHeader>
            <CardContent>
              <div class="text-3xl font-bold">{{ store.whatsapp_clicks || 0 }}</div>
              <p class="text-xs text-muted-foreground mt-1">
                Cliques no botão de WhatsApp
              </p>
            </CardContent>
          </Card>

          <!-- Website Clicks -->
          <Card class="relative overflow-hidden">
            <CardHeader class="flex flex-row items-center justify-between pb-2">
              <CardTitle class="text-sm font-medium text-muted-foreground">
                Cliques Site
              </CardTitle>
              <Icon icon="lucide:globe" class="h-5 w-5 text-blue-600" />
            </CardHeader>
            <CardContent :class="{ 'blur-sm select-none': !showWebsiteClicks }">
              <div class="text-3xl font-bold">{{ getMetricValue('website_clicks') }}</div>
              <p class="text-xs text-muted-foreground mt-1">
                Cliques no botão do site
              </p>
            </CardContent>
            <div v-if="!showWebsiteClicks" class="absolute inset-0 flex items-center justify-center bg-white/70 backdrop-blur-[1px]">
              <div class="text-center px-4">
                <Icon icon="lucide:lock" class="h-6 w-6 text-gray-500 mx-auto mb-1" />
                <p class="text-xs font-medium text-gray-600 mb-1.5">Plano Destaque</p>
                <Button size="sm" :as="Link" :href="route('subscriptions.create')" class="h-7 text-xs px-3">
                  Upgrade
                </Button>
              </div>
            </div>
          </Card>

          <!-- Map Clicks -->
          <Card class="relative overflow-hidden">
            <CardHeader class="flex flex-row items-center justify-between pb-2">
              <CardTitle class="text-sm font-medium text-muted-foreground">
                Cliques Ver Localização
              </CardTitle>
              <Icon icon="lucide:map-pin" class="h-5 w-5 text-purple-600" />
            </CardHeader>
            <CardContent :class="{ 'blur-sm select-none': !showMapClicks }">
              <div class="text-3xl font-bold">{{ getMetricValue('map_clicks') }}</div>
              <p class="text-xs text-muted-foreground mt-1">
                Cliques para ver localização
              </p>
            </CardContent>
            <div v-if="!showMapClicks" class="absolute inset-0 flex items-center justify-center bg-white/70 backdrop-blur-[1px]">
              <div class="text-center px-4">
                <Icon icon="lucide:lock" class="h-6 w-6 text-gray-500 mx-auto mb-1" />
                <p class="text-xs font-medium text-gray-600 mb-1.5">Plano Destaque</p>
                <Button size="sm" :as="Link" :href="route('subscriptions.create')" class="h-7 text-xs px-3">
                  Upgrade
                </Button>
              </div>
            </div>
          </Card>

          <!-- Shares -->
          <Card class="relative overflow-hidden">
            <CardHeader class="flex flex-row items-center justify-between pb-2">
              <CardTitle class="text-sm font-medium text-muted-foreground">
                Compartilhamentos
              </CardTitle>
              <Icon icon="lucide:share-2" class="h-5 w-5 text-pink-600" />
            </CardHeader>
            <CardContent :class="{ 'blur-sm select-none': !showShares }">
              <div class="text-3xl font-bold">{{ getMetricValue('shares') }}</div>
              <p class="text-xs text-muted-foreground mt-1">
                Vezes que sua vitrine foi compartilhada
              </p>
            </CardContent>
            <div v-if="!showShares" class="absolute inset-0 flex items-center justify-center bg-white/70 backdrop-blur-[1px]">
              <div class="text-center px-4">
                <Icon icon="lucide:lock" class="h-6 w-6 text-gray-500 mx-auto mb-1" />
                <p class="text-xs font-medium text-gray-600 mb-1.5">Plano Destaque</p>
                <Button size="sm" :as="Link" :href="route('subscriptions.create')" class="h-7 text-xs px-3">
                  Upgrade
                </Button>
              </div>
            </div>
          </Card>

          <!-- Instagram Clicks -->
          <Card class="relative overflow-hidden">
            <CardHeader class="flex flex-row items-center justify-between pb-2">
              <CardTitle class="text-sm font-medium text-muted-foreground">
                Cliques Instagram
              </CardTitle>
              <Icon icon="lucide:instagram" class="h-5 w-5 text-pink-600" />
            </CardHeader>
            <CardContent :class="{ 'blur-sm select-none': !showInstagramClicks }">
              <div class="text-3xl font-bold">{{ getMetricValue('instagram_clicks') }}</div>
              <p class="text-xs text-muted-foreground mt-1">
                Cliques no perfil do Instagram
              </p>
            </CardContent>
            <div v-if="!showInstagramClicks" class="absolute inset-0 flex items-center justify-center bg-white/70 backdrop-blur-[1px]">
              <div class="text-center px-4">
                <Icon icon="lucide:lock" class="h-6 w-6 text-gray-500 mx-auto mb-1" />
                <p class="text-xs font-medium text-gray-600 mb-1.5">Plano Destaque</p>
                <Button size="sm" :as="Link" :href="route('subscriptions.create')" class="h-7 text-xs px-3">
                  Upgrade
                </Button>
              </div>
            </div>
          </Card>

          <!-- Facebook Clicks -->
          <Card class="relative overflow-hidden">
            <CardHeader class="flex flex-row items-center justify-between pb-2">
              <CardTitle class="text-sm font-medium text-muted-foreground">
                Cliques Facebook
              </CardTitle>
              <Icon icon="lucide:facebook" class="h-5 w-5 text-blue-700" />
            </CardHeader>
            <CardContent :class="{ 'blur-sm select-none': !showFacebookClicks }">
              <div class="text-3xl font-bold">{{ getMetricValue('facebook_clicks') }}</div>
              <p class="text-xs text-muted-foreground mt-1">
                Cliques na página do Facebook
              </p>
            </CardContent>
            <div v-if="!showFacebookClicks" class="absolute inset-0 flex items-center justify-center bg-white/70 backdrop-blur-[1px]">
              <div class="text-center px-4">
                <Icon icon="lucide:lock" class="h-6 w-6 text-gray-500 mx-auto mb-1" />
                <p class="text-xs font-medium text-gray-600 mb-1.5">Plano Destaque</p>
                <Button size="sm" :as="Link" :href="route('subscriptions.create')" class="h-7 text-xs px-3">
                  Upgrade
                </Button>
              </div>
            </div>
          </Card>

          <!-- TikTok Clicks -->
          <Card class="relative overflow-hidden">
            <CardHeader class="flex flex-row items-center justify-between pb-2">
              <CardTitle class="text-sm font-medium text-muted-foreground">
                Cliques TikTok
              </CardTitle>
              <Icon icon="lucide:video" class="h-5 w-5 text-gray-900" />
            </CardHeader>
            <CardContent :class="{ 'blur-sm select-none': !showTikTokClicks }">
              <div class="text-3xl font-bold">{{ getMetricValue('tiktok_clicks') }}</div>
              <p class="text-xs text-muted-foreground mt-1">
                Cliques no perfil do TikTok
              </p>
            </CardContent>
            <div v-if="!showTikTokClicks" class="absolute inset-0 flex items-center justify-center bg-white/70 backdrop-blur-[1px]">
              <div class="text-center px-4">
                <Icon icon="lucide:lock" class="h-6 w-6 text-gray-500 mx-auto mb-1" />
                <p class="text-xs font-medium text-gray-600 mb-1.5">Plano Destaque</p>
                <Button size="sm" :as="Link" :href="route('subscriptions.create')" class="h-7 text-xs px-3">
                  Upgrade
                </Button>
              </div>
            </div>
          </Card>
        </div>


        <!-- Conversion Rate -->
        <Card v-if="showViews && (showWhatsappClicks || showWebsiteClicks)">
          <CardHeader>
            <CardTitle>Taxa de Conversão</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <div v-if="showWhatsappClicks">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-medium">WhatsApp</span>
                  <span class="text-sm text-muted-foreground">
                    {{ store.views_count > 0 ? Math.round((store.whatsapp_clicks / store.views_count) * 100) : 0 }}%
                  </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div
                    class="bg-green-600 h-2 rounded-full transition-all"
                    :style="{ width: `${store.views_count > 0 ? (store.whatsapp_clicks / store.views_count) * 100 : 0}%` }"
                  />
                </div>
              </div>

              <div v-if="showWebsiteClicks">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-medium">Site</span>
                  <span class="text-sm text-muted-foreground">
                    {{ store.views_count > 0 ? Math.round((store.website_clicks / store.views_count) * 100) : 0 }}%
                  </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div
                    class="bg-blue-600 h-2 rounded-full transition-all"
                    :style="{ width: `${store.views_count > 0 ? (store.website_clicks / store.views_count) * 100 : 0}%` }"
                  />
                </div>
              </div>

              <div v-if="showMapClicks">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-medium">Ver Localização</span>
                  <span class="text-sm text-muted-foreground">
                    {{ store.views_count > 0 ? Math.round((store.map_clicks / store.views_count) * 100) : 0 }}%
                  </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div
                    class="bg-purple-600 h-2 rounded-full transition-all"
                    :style="{ width: `${store.views_count > 0 ? (store.map_clicks / store.views_count) * 100 : 0}%` }"
                  />
                </div>
              </div>

              <div v-if="showInstagramClicks">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-medium">Instagram</span>
                  <span class="text-sm text-muted-foreground">
                    {{ store.views_count > 0 ? Math.round((store.instagram_clicks / store.views_count) * 100) : 0 }}%
                  </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div
                    class="bg-pink-600 h-2 rounded-full transition-all"
                    :style="{ width: `${store.views_count > 0 ? (store.instagram_clicks / store.views_count) * 100 : 0}%` }"
                  />
                </div>
              </div>

              <div v-if="showFacebookClicks">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-medium">Facebook</span>
                  <span class="text-sm text-muted-foreground">
                    {{ store.views_count > 0 ? Math.round((store.facebook_clicks / store.views_count) * 100) : 0 }}%
                  </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div
                    class="bg-blue-700 h-2 rounded-full transition-all"
                    :style="{ width: `${store.views_count > 0 ? (store.facebook_clicks / store.views_count) * 100 : 0}%` }"
                  />
                </div>
              </div>

              <div v-if="showTikTokClicks">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-medium">TikTok</span>
                  <span class="text-sm text-muted-foreground">
                    {{ store.views_count > 0 ? Math.round((store.tiktok_clicks / store.views_count) * 100) : 0 }}%
                  </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div
                    class="bg-gray-900 h-2 rounded-full transition-all"
                    :style="{ width: `${store.views_count > 0 ? (store.tiktok_clicks / store.views_count) * 100 : 0}%` }"
                  />
                </div>
              </div>

              <div class="pt-4 border-t">
                <div class="flex items-center justify-between">
                  <span class="text-sm font-medium">Total de Conversões</span>
                  <span class="text-lg font-bold text-teal-600">
                    {{ (store.whatsapp_clicks || 0) + (store.website_clicks || 0) + (store.map_clicks || 0) + (store.instagram_clicks || 0) + (store.facebook_clicks || 0) + (store.tiktok_clicks || 0) }}
                  </span>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

      </div>
    </div>
  </AppLayout>
</template>
