<script setup>
import { Icon } from '@iconify/vue'
import { Link } from '@inertiajs/vue3'
import axios from 'axios'
import WeeklyStoreImages from '@/Components/WeeklyStoreImages.vue'

defineProps({
  stores: {
    type: Array,
    default: () => [],
  },
})

const advertiseUrl = `https://wa.me/556231900204?text=${encodeURIComponent('Olá! Quero saber como colocar minha loja nas Vitrines da semana na página inicial do Tá na Vitrine.')}`

function trackOpen(store) {
  axios.post(`/loja/${store.slug}/track/open`).catch(() => {
    // Opening a storefront must remain independent of tracking availability.
  })
}
</script>

<template>
  <section
    v-if="stores.length"
    aria-labelledby="weekly-stores-heading"
    class="border-b border-amber-100 bg-[#fffcf5] py-10 sm:py-14"
  >
    <div class="container mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="mb-7 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <div class="mb-3 inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] text-teal-700">
            <span class="flex size-7 items-center justify-center rounded-full bg-amber-200 text-teal-900">
              <Icon icon="lucide:sparkles" class="size-4" aria-hidden="true" />
            </span>
            Uma semana no centro das atenções
          </div>
          <h2 id="weekly-stores-heading" class="text-3xl font-extrabold tracking-tight text-teal-950 sm:text-4xl">
            Vitrines da <span class="text-teal-700">semana</span>
          </h2>
          <p class="mt-3 max-w-lg text-sm leading-relaxed text-slate-600 sm:text-base">
            Conheça as lojas que estão em evidência e encontre seu próximo fornecedor.
          </p>
        </div>
        <a
          :href="advertiseUrl"
          target="_blank"
          rel="noopener noreferrer"
          class="inline-flex min-h-11 w-fit shrink-0 items-center justify-center gap-2 rounded-xl border border-teal-800/20 bg-white px-5 py-3 text-sm font-semibold text-teal-900 transition-colors hover:border-teal-700 hover:bg-teal-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-700 focus-visible:ring-offset-2"
        >
          Quero aparecer aqui
          <Icon icon="lucide:arrow-up-right" class="size-4" aria-hidden="true" />
        </a>
      </div>

      <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
        <article
          v-for="store in stores"
          :key="store.id"
          class="group flex h-full flex-col overflow-hidden rounded-2xl border border-amber-200/70 bg-white shadow-sm transition-shadow hover:shadow-lg"
        >
          <WeeklyStoreImages :store="store" />

          <Link
            :href="`/loja/${store.slug}`"
            class="flex flex-1 flex-col p-5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-teal-700"
            @click="trackOpen(store)"
          >
            <div class="mb-3 flex flex-wrap items-center gap-2 text-xs font-medium text-teal-700">
              <span v-if="store.category">{{ store.category }}</span>
              <span v-if="store.category && store.saleType" class="size-1 rounded-full bg-teal-300" aria-hidden="true" />
              <span v-if="store.saleType">{{ store.saleType === 'Ambos' ? 'Atacado e varejo' : store.saleType }}</span>
            </div>
            <h3 class="line-clamp-2 text-xl font-bold text-teal-950">{{ store.name }}</h3>
            <p v-if="store.location" class="mt-2 flex items-center gap-1.5 text-xs text-slate-500">
              <Icon icon="lucide:map-pin" class="size-3.5 shrink-0" aria-hidden="true" />
              {{ store.location }}
            </p>
            <p v-if="store.description" class="mb-5 mt-3 line-clamp-2 text-sm leading-relaxed text-slate-600">{{ store.description }}</p>
            <div class="mt-auto flex items-center justify-between border-t border-slate-100 pt-4 text-sm font-bold text-teal-800">
              Ver vitrine
              <span class="flex size-8 items-center justify-center rounded-full bg-teal-50 transition-colors group-hover:bg-amber-200">
                <Icon icon="lucide:arrow-right" class="size-4" aria-hidden="true" />
              </span>
            </div>
          </Link>
        </article>
      </div>
    </div>
  </section>
</template>
