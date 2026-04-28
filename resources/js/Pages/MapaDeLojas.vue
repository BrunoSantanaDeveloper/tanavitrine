<script setup>
import StoreMap from '@/Components/StoreMap.vue'
import WebLayout from '@/Layouts/WebLayout.vue'
import Card from '@/Components/shadcn/ui/card/Card.vue'
import Badge from '@/Components/shadcn/ui/badge/Badge.vue'
import { Icon } from '@iconify/vue'

const props = defineProps({
  canLogin: {
    type: Boolean,
  },
  canRegister: {
    type: Boolean,
  },
  stores: {
    type: Array,
    default: () => [],
  },
})
</script>

<template>
  <WebLayout :can-login="canLogin" :can-register="canRegister" :show-floating-whats-app="false">
    <section class="py-12 sm:py-16">
      <div class="container mx-auto px-4">
        <div class="mx-auto mb-8 max-w-5xl text-center">
          <div class="mb-4 flex justify-center">
            <Badge class="bg-primary text-primary-foreground shadow-lg">
              <Icon icon="lucide:star" class="mr-1 size-3" />
              Destaque
            </Badge>
          </div>
          <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">Mapa de Lojas</h1>
          <p class="mt-3 text-muted-foreground">
            Encontre lojas em destaque com localização física e navegue pelas vitrines direto no mapa.
          </p>
        </div>

        <Card class="mx-auto max-w-6xl p-4 sm:p-6">
          <div class="h-[65vh] min-h-[420px]">
            <StoreMap :stores="stores" />
          </div>
        </Card>
      </div>
    </section>
  </WebLayout>
</template>
