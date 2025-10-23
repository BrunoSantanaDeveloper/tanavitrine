<script setup>
import StatCard from '@/Components/dashboard/StatCard.vue'
import { Button } from '@/Components/shadcn/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import { Badge } from '@/Components/shadcn/ui/badge'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'

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
            v-if="store"
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
                <div>
                  <CardTitle class="text-xl">{{ store.name }}</CardTitle>
                  <p class="text-sm text-muted-foreground mt-1">
                    {{ store.category }} • {{ store.subcategory }} • {{ store.sale_type }}
                  </p>
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
        <Card class="bg-gradient-to-r from-teal-50 to-orange-50">
          <CardContent class="p-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
              <div>
                <h2 class="text-lg font-semibold">
                  Plano {{ plan.name }}
                </h2>
                <p class="text-muted-foreground mt-1">
                  Máximo de {{ plan.max_photos }} fotos por vitrine
                </p>
                <div v-if="store && store.photos_count" class="text-sm text-gray-600 mt-1">
                  Você está usando {{ store.photos_count }} de {{ plan.max_photos }} fotos
                </div>
              </div>
              <div class="mt-4 md:mt-0">
                <Link :href="route('subscriptions.index')">
                  <Button class="bg-teal-600 hover:bg-teal-700">
                    <Icon icon="lucide:sparkles" class="mr-2 h-4 w-4" />
                    Fazer Upgrade
                  </Button>
                </Link>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
