<script setup>
defineProps({
  icon: {
    type: String,
    required: true,
  },
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    required: true,
  },
})
</script>

<template>
  <div class="flex flex-col items-left text-left rounded-lg border bg-card p-6 shadow-xs">
    <div class="flex items-center  justify-center gap-3 ">
      <div class="w-20">
        <img :src="icon">
      </div>
      <h3 class="mt-4 text-xl font-semibold">
        {{ title }}
      </h3>
    </div>
    <p class="mt-2 text-muted-foreground text-center">
      {{ description }}
    </p>
  </div>
</template>
