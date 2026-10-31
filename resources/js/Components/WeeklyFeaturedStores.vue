<script setup>
import StoreCard from '@/Components/StoreCard.vue'

defineProps({
  stores: {
    type: Array,
    default: () => [],
  },
})

</script>

<template>
  <section
    v-if="stores.length"
    aria-label="Vitrines da semana"
    class="bg-white pb-10 pt-4 sm:py-14 lg:py-6"
  >
    <div class="site-shell">
      <div class="store-grid">
        <StoreCard
          v-for="store in stores"
          :key="store.id"
          :store="store"
          weekly-featured
        />
      </div>
    </div>
  </section>
</template>
