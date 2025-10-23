<script setup>
import { Button } from '@/Components/shadcn/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import Badge from '@/Components/shadcn/ui/badge/Badge.vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'

const props = defineProps({
  stores: {
    type: Array,
    required: true
  }
})
</script>

<template>
  <AppLayout title="Minhas Vitrines">
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Minhas Vitrines
        </h2>
        <Button :as="Link" href="/dashboard/stores/create">
          <Icon icon="lucide:plus" class="size-4 mr-2" />
          Nova Vitrine
        </Button>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Empty State -->
        <div v-if="stores.length === 0" class="text-center py-12">
          <Icon icon="lucide:store" class="size-24 mx-auto text-muted-foreground mb-4" />
          <h3 class="text-xl font-semibold mb-2">Nenhuma vitrine criada</h3>
          <p class="text-muted-foreground mb-6">
            Crie sua primeira vitrine para começar a divulgar seus produtos
          </p>
          <Button :as="Link" href="/dashboard/stores/create">
            <Icon icon="lucide:plus" class="size-4 mr-2" />
            Criar Vitrine
          </Button>
        </div>

        <!-- Stores Grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <Card
            v-for="store in stores"
            :key="store.id"
            class="hover:shadow-lg transition-shadow"
          >
            <CardHeader>
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <CardTitle class="text-lg">{{ store.name }}</CardTitle>
                  <p class="text-sm text-muted-foreground mt-1">
                    {{ store.category }}
                  </p>
                </div>
                <Badge
                  :variant="
                    store.status === 'ativo'
                      ? 'default'
                      : store.status === 'pendente'
                      ? 'secondary'
                      : 'destructive'
                  "
                >
                  {{ store.status }}
                </Badge>
              </div>
            </CardHeader>

            <CardContent class="space-y-4">
              <!-- Stats -->
              <div class="grid grid-cols-3 gap-2 text-center">
                <div class="p-2 bg-muted rounded-lg">
                  <Icon icon="lucide:eye" class="size-4 mx-auto mb-1 text-muted-foreground" />
                  <p class="text-xs text-muted-foreground">Visualizações</p>
                  <p class="font-semibold">{{ store.views_count }}</p>
                </div>
                <div class="p-2 bg-muted rounded-lg">
                  <Icon icon="lucide:message-circle" class="size-4 mx-auto mb-1 text-muted-foreground" />
                  <p class="text-xs text-muted-foreground">WhatsApp</p>
                  <p class="font-semibold">{{ store.whatsapp_clicks }}</p>
                </div>
                <div class="p-2 bg-muted rounded-lg">
                  <Icon icon="lucide:globe" class="size-4 mx-auto mb-1 text-muted-foreground" />
                  <p class="text-xs text-muted-foreground">Site</p>
                  <p class="font-semibold">{{ store.website_clicks }}</p>
                </div>
              </div>

              <!-- Info -->
              <div class="space-y-2 text-sm">
                <div class="flex items-center gap-2">
                  <Icon
                    :icon="store.sale_type === 'atacado' ? 'lucide:shopping-cart' : 'lucide:store'"
                    class="size-4 text-muted-foreground"
                  />
                  <span class="capitalize">{{ store.sale_type }}</span>
                </div>

                <div class="flex items-center gap-2">
                  <Icon icon="lucide:image" class="size-4 text-muted-foreground" />
                  <span>{{ store.photos_count }} / {{ store.max_photos }} fotos</span>
                </div>

                <div v-if="store.featured" class="flex items-center gap-2 text-primary">
                  <Icon icon="lucide:star" class="size-4" />
                  <span class="font-semibold">Vitrine em destaque</span>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex gap-2 pt-4 border-t">
                <Button
                  :as="Link"
                  :href="`/dashboard/stores/${store.slug}/edit`"
                  variant="outline"
                  size="sm"
                  class="flex-1"
                >
                  <Icon icon="lucide:pencil" class="size-4 mr-2" />
                  Editar
                </Button>
                <Button
                  :as="Link"
                  :href="`/dashboard/stores/${store.slug}/analytics`"
                  variant="outline"
                  size="sm"
                  class="flex-1"
                >
                  <Icon icon="lucide:bar-chart" class="size-4 mr-2" />
                  Analytics
                </Button>
                <Button
                  :as="Link"
                  :href="`/loja/${store.slug}`"
                  variant="ghost"
                  size="sm"
                  target="_blank"
                >
                  <Icon icon="lucide:external-link" class="size-4" />
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
