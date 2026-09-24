<script setup>
import { useSeoMetaTags } from '@/Composables/useSeoMetaTags.js'
import WebLayout from '@/Layouts/WebLayout.vue'
import { Icon } from '@iconify/vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  canLogin: Boolean,
  canRegister: Boolean,
  categories: {
    type: Array,
    default: () => [],
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
  <WebLayout :can-login="canLogin" :can-register="canRegister" :show-floating-whats-app="true">
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

          <p class="mb-2 text-sm font-semibold uppercase tracking-wider text-teal-700">Diretório de fornecedores</p>
          <h1 class="max-w-3xl text-3xl font-bold tracking-tight text-foreground sm:text-4xl">
            Categorias de fornecedores de moda
          </h1>
          <p class="mt-4 max-w-3xl text-base leading-7 text-muted-foreground sm:text-lg">
            Escolha uma categoria para conhecer lojas e fornecedores, visualizar suas vitrines e entrar em contato diretamente.
          </p>
        </div>
      </section>

      <section class="container mx-auto px-4 py-10 sm:px-6 lg:px-8" aria-labelledby="categories-title">
        <h2 id="categories-title" class="sr-only">Categorias disponíveis</h2>

        <div v-if="categories.length" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          <Link
            v-for="category in categories"
            :key="category.id"
            :href="category.url"
            class="group rounded-2xl border bg-card p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-teal-300 hover:shadow-md"
          >
            <div class="flex items-start justify-between gap-4">
              <div>
                <Icon
                  v-if="category.icon"
                  :icon="category.icon"
                  class="mb-3 size-8 text-teal-700"
                  aria-hidden="true"
                />
                <h3 class="text-xl font-bold text-foreground group-hover:text-teal-700">{{ category.name }}</h3>
              </div>
              <Icon icon="lucide:arrow-up-right" class="mt-1 size-5 text-muted-foreground group-hover:text-teal-700" aria-hidden="true" />
            </div>
            <p v-if="category.description" class="mt-3 line-clamp-3 text-sm leading-6 text-muted-foreground">
              {{ category.description }}
            </p>
            <p class="mt-5 text-sm font-medium text-teal-700">
              {{ category.stores_count }} {{ category.stores_count === 1 ? 'loja' : 'lojas' }}
            </p>
          </Link>
        </div>

        <div v-else class="rounded-2xl border border-dashed p-10 text-center text-muted-foreground">
          Nenhuma categoria com lojas públicas está disponível no momento.
        </div>
      </section>
    </main>
  </WebLayout>
</template>
