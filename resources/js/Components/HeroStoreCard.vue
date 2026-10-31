<script setup>
import { Icon } from '@iconify/vue'
import { Link } from '@inertiajs/vue3'
import axios from 'axios'
import WeeklyStoreImages from '@/Components/WeeklyStoreImages.vue'

defineProps({
  store: { type: Object, required: true },
  priority: { type: Boolean, default: false },
})

function trackOpen(store) {
  axios.post(`/loja/${store.slug}/track/open`).catch(() => {})
}
</script>

<template>
  <article class="flex min-w-0 flex-col overflow-hidden rounded-2xl border border-teal-800 bg-teal-950 shadow-md transition-shadow hover:shadow-lg">
    <WeeklyStoreImages :store="store" :promoted="false" compact :priority="priority" />
    <Link
      :href="`/loja/${store.slug}`"
      class="flex flex-1 flex-col focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-amber-300"
      @click="trackOpen(store)"
    >
      <div class="flex flex-1 flex-col px-4 py-3 sm:px-5">
        <h2 class="truncate text-base font-bold text-white">{{ store.name }}</h2>
        <p class="mt-1.5 text-xs font-semibold text-amber-300">
          {{ store.saleType === 'Ambos' ? 'Atacado e varejo' : store.saleType }}
          <span v-if="store.is_manufacturer" class="font-normal text-teal-100/80"> · Fabricação própria</span>
        </p>
        <p class="mt-2 truncate text-xs text-teal-100/80">{{ store.description || 'Conheça os produtos e fale diretamente com a loja.' }}</p>
      </div>
      <div class="flex items-center justify-between gap-2 border-t border-white/10 px-4 py-3 text-[11px] sm:px-5">
        <span class="flex min-w-0 items-center gap-1 text-teal-100/80">
          <Icon :icon="store.location ? 'lucide:map-pin' : 'lucide:store'" class="size-3 shrink-0" aria-hidden="true" />
          <span class="truncate">{{ store.location || 'Conheça a loja' }}</span>
        </span>
        <span class="flex shrink-0 items-center gap-1 font-semibold text-amber-300">
          Ver vitrine
          <Icon icon="lucide:arrow-up-right" class="size-3.5" aria-hidden="true" />
        </span>
      </div>
    </Link>
  </article>
</template>
