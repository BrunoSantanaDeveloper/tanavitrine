<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { Button } from '@/Components/shadcn/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import { Icon } from '@iconify/vue'
import { Badge } from '@/Components/shadcn/ui/badge'

const props = defineProps({
  store: {
    type: Object,
    required: true
  },
  leads: {
    type: Array,
    default: () => []
  }
})
</script>

<template>
  <Head :title="`Analytics - ${store.name}`" />

  <AppLayout :title="`Analytics - ${store.name}`">
    <div class="min-h-screen bg-gray-50 p-6">
      <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Analytics</h1>
            <p class="text-muted-foreground">{{ store.name }}</p>
          </div>
          <Button :as="Link" :href="route('dashboard')" variant="outline">
            <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
            Voltar
          </Button>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
          <Card>
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

          <Card>
            <CardHeader class="flex flex-row items-center justify-between pb-2">
              <CardTitle class="text-sm font-medium text-muted-foreground">
                Cliques WhatsApp
              </CardTitle>
              <Icon icon="lucide:message-circle" class="h-5 w-5 text-teal-600" />
            </CardHeader>
            <CardContent>
              <div class="text-3xl font-bold">{{ store.whatsapp_clicks || 0 }}</div>
              <p class="text-xs text-muted-foreground mt-1">
                Cliques no botão de WhatsApp
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader class="flex flex-row items-center justify-between pb-2">
              <CardTitle class="text-sm font-medium text-muted-foreground">
                Cliques Site
              </CardTitle>
              <Icon icon="lucide:globe" class="h-5 w-5 text-teal-600" />
            </CardHeader>
            <CardContent>
              <div class="text-3xl font-bold">{{ store.website_clicks || 0 }}</div>
              <p class="text-xs text-muted-foreground mt-1">
                Cliques no botão do site
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader class="flex flex-row items-center justify-between pb-2">
              <CardTitle class="text-sm font-medium text-muted-foreground">
                Cliques Mapa
              </CardTitle>
              <Icon icon="lucide:map-pin" class="h-5 w-5 text-teal-600" />
            </CardHeader>
            <CardContent>
              <div class="text-3xl font-bold">{{ store.map_clicks || 0 }}</div>
              <p class="text-xs text-muted-foreground mt-1">
                Cliques para ver localização
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader class="flex flex-row items-center justify-between pb-2">
              <CardTitle class="text-sm font-medium text-muted-foreground">
                Cliques Telefone
              </CardTitle>
              <Icon icon="lucide:phone" class="h-5 w-5 text-teal-600" />
            </CardHeader>
            <CardContent>
              <div class="text-3xl font-bold">{{ store.phone_clicks || 0 }}</div>
              <p class="text-xs text-muted-foreground mt-1">
                Cliques no número de telefone
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader class="flex flex-row items-center justify-between pb-2">
              <CardTitle class="text-sm font-medium text-muted-foreground">
                Compartilhamentos
              </CardTitle>
              <Icon icon="lucide:share-2" class="h-5 w-5 text-teal-600" />
            </CardHeader>
            <CardContent>
              <div class="text-3xl font-bold">{{ store.shares_count || 0 }}</div>
              <p class="text-xs text-muted-foreground mt-1">
                Vezes que sua vitrine foi compartilhada
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader class="flex flex-row items-center justify-between pb-2">
              <CardTitle class="text-sm font-medium text-muted-foreground">
                Leads Capturados
              </CardTitle>
              <Icon icon="lucide:users" class="h-5 w-5 text-teal-600" />
            </CardHeader>
            <CardContent>
              <div class="text-3xl font-bold">{{ leads?.length || 0 }}</div>
              <p class="text-xs text-muted-foreground mt-1">
                Pessoas interessadas cadastradas
              </p>
            </CardContent>
          </Card>
        </div>

        <!-- Featured Status -->
        <Card class="mb-6">
          <CardHeader>
            <CardTitle>Status da Vitrine</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium mb-1">Vitrine em Destaque</p>
                <p class="text-xs text-muted-foreground">
                  Vitrines em destaque aparecem primeiro nas buscas
                </p>
              </div>
              <Badge :variant="store.featured ? 'default' : 'secondary'">
                {{ store.featured ? 'Ativo' : 'Inativo' }}
              </Badge>
            </div>
          </CardContent>
        </Card>

        <!-- Conversion Rate -->
        <Card>
          <CardHeader>
            <CardTitle>Taxa de Conversão</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-medium">WhatsApp</span>
                  <span class="text-sm text-muted-foreground">
                    {{ store.views_count > 0 ? Math.round((store.whatsapp_clicks / store.views_count) * 100) : 0 }}%
                  </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div
                    class="bg-teal-600 h-2 rounded-full transition-all"
                    :style="{ width: `${store.views_count > 0 ? (store.whatsapp_clicks / store.views_count) * 100 : 0}%` }"
                  ></div>
                </div>
              </div>

              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-medium">Site</span>
                  <span class="text-sm text-muted-foreground">
                    {{ store.views_count > 0 ? Math.round((store.website_clicks / store.views_count) * 100) : 0 }}%
                  </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div
                    class="bg-orange-500 h-2 rounded-full transition-all"
                    :style="{ width: `${store.views_count > 0 ? (store.website_clicks / store.views_count) * 100 : 0}%` }"
                  ></div>
                </div>
              </div>

              <div class="pt-4 border-t">
                <div class="flex items-center justify-between">
                  <span class="text-sm font-medium">Total de Conversões</span>
                  <span class="text-lg font-bold text-teal-600">
                    {{ (store.whatsapp_clicks || 0) + (store.website_clicks || 0) }}
                  </span>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Leads Table -->
        <Card class="mt-6">
          <CardHeader>
            <CardTitle>Leads Capturados</CardTitle>
          </CardHeader>
          <CardContent>
            <div v-if="leads && leads.length > 0" class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr class="border-b">
                    <th class="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Nome</th>
                    <th class="text-left py-3 px-4 text-sm font-medium text-muted-foreground">WhatsApp</th>
                    <th class="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Ação</th>
                    <th class="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Data</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="lead in leads" :key="lead.id" class="border-b hover:bg-muted/50 transition-colors">
                    <td class="py-3 px-4 text-sm">{{ lead.name }}</td>
                    <td class="py-3 px-4 text-sm">{{ lead.whatsapp }}</td>
                    <td class="py-3 px-4">
                      <div class="flex items-center gap-2">
                        <Icon
                          v-if="lead.action === 'whatsapp'"
                          icon="lucide:message-circle"
                          class="h-4 w-4 text-green-600"
                        />
                        <Icon
                          v-else-if="lead.action === 'map'"
                          icon="lucide:map-pin"
                          class="h-4 w-4 text-blue-600"
                        />
                        <Icon
                          v-else-if="lead.action === 'website'"
                          icon="lucide:globe"
                          class="h-4 w-4 text-purple-600"
                        />
                        <Icon
                          v-else-if="lead.action === 'phone'"
                          icon="lucide:phone"
                          class="h-4 w-4 text-orange-600"
                        />
                        <span class="text-sm capitalize">{{ lead.action }}</span>
                      </div>
                    </td>
                    <td class="py-3 px-4 text-sm text-muted-foreground">{{ lead.created_at }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else class="py-12 text-center">
              <Icon icon="lucide:users" class="h-16 w-16 mx-auto text-gray-400 mb-4" />
              <h3 class="text-lg font-semibold mb-2">Nenhum Lead Capturado</h3>
              <p class="text-sm text-muted-foreground">
                Quando visitantes interagirem com sua vitrine, os dados deles aparecerão aqui
              </p>
            </div>
          </CardContent>
        </Card>

        <!-- Coming Soon -->
        <Card class="mt-6">
          <CardContent class="py-12 text-center">
            <Icon icon="lucide:chart-line" class="h-16 w-16 mx-auto text-gray-400 mb-4" />
            <h3 class="text-lg font-semibold mb-2">Mais Métricas em Breve</h3>
            <p class="text-sm text-muted-foreground mb-4">
              Estamos trabalhando em novos recursos de analytics para você acompanhar melhor o desempenho da sua vitrine
            </p>
            <Badge variant="secondary">Em Desenvolvimento</Badge>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
