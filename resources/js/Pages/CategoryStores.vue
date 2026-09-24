<script setup>
import StoreCard from '@/Components/StoreCard.vue'
import { useSeoMetaTags } from '@/Composables/useSeoMetaTags.js'
import WebLayout from '@/Layouts/WebLayout.vue'
import { Icon } from '@iconify/vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  canLogin: Boolean,
  canRegister: Boolean,
  category: {
    type: Object,
    required: true,
  },
  stores: {
    type: Object,
    required: true,
  },
  breadcrumbs: {
    type: Array,
    default: () => [],
  },
  seo: {
    type: Object,
    required: true,
  },
})

useSeoMetaTags(props.seo)
</script>

<template>
  <WebLayout :can-login="canLogin" :can-register="canRegister">
    <main class="bg-background">
      <section class="border-b bg-gradient-to-b from-teal-50 to-background">
        <div class="container mx-auto px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
          <nav aria-label="Navegação estrutural" class="mb-6 flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
            <template v-for="(breadcrumb, index) in breadcrumbs" :key="breadcrumb.url">
              <Icon v-if="index > 0" icon="lucide:chevron-right" class="size-4" aria-hidden="true" />
              <span v-if="breadcrumb.current" class="text-foreground" aria-current="page">{{ breadcrumb.name }}</span>
              <Link v-else :href="breadcrumb.url" class="hover:text-foreground hover:underline">{{ breadcrumb.name }}</Link>
            </template>
          </nav>

          <p class="mb-2 text-sm font-semibold uppercase tracking-wider text-teal-700">Categoria</p>
          <h1 class="max-w-4xl text-3xl font-bold tracking-tight text-foreground sm:text-4xl">
            Fornecedores de {{ category.name }} no atacado e varejo
          </h1>
          <p class="mt-4 max-w-3xl text-base leading-7 text-muted-foreground sm:text-lg">
            {{ category.description || `Conheça lojas e fornecedores de ${category.name}, veja suas vitrines e fale diretamente com cada empresa.` }}
          </p>
          <p class="mt-4 text-sm font-medium text-foreground">
            {{ category.stores_count }} {{ category.stores_count === 1 ? 'loja encontrada' : 'lojas encontradas' }}
          </p>
        </div>
      </section>

      <section class="container mx-auto px-4 py-10 sm:px-6 lg:px-8" :aria-label="`Lojas de ${category.name}`">
        <div class="grid gap-6 lg:grid-cols-2">
          <StoreCard
            v-for="store in stores.data"
            :key="store.id"
            :store="store"
            :show-favorite-button="false"
          />
        </div>

        <nav v-if="stores.last_page > 1" class="mt-10 flex flex-wrap justify-center gap-2" aria-label="Paginação">
          <template v-for="(link, index) in stores.links" :key="`${index}-${link.label}`">
            <span
              v-if="!link.url"
              class="inline-flex min-h-10 items-center rounded-lg border px-3 py-2 text-sm text-muted-foreground opacity-50"
              v-html="link.label"
            />
            <Link
              v-else
              :href="link.url"
              preserve-scroll
              class="inline-flex min-h-10 items-center rounded-lg border px-3 py-2 text-sm transition hover:border-teal-400 hover:text-teal-700"
              :class="link.active ? 'border-teal-700 bg-teal-700 text-white hover:text-white' : 'bg-background'"
              :aria-current="link.active ? 'page' : undefined"
            >
              <span v-html="link.label" />
            </Link>
          </template>
        </nav>
      </section>
    </main>
  </WebLayout>
</template>
