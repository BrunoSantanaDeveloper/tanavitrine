<script setup>
import { Card, CardContent } from '@/Components/shadcn/ui/card'
import { Icon } from '@iconify/vue'

defineProps({
  title: {
    type: String,
    required: true,
  },
  value: {
    type: [String, Number],
    required: true,
  },
  icon: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: '',
  },
  trend: {
    type: Object,
    default: null,
  },
})
</script>

<template>
  <Card>
    <CardContent class="p-6">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-medium text-muted-foreground">
            {{ title }}
          </p>
          <div class="flex items-baseline gap-2">
            <h2 class="text-2xl font-bold">
              {{ value }}
            </h2>
            <span
              v-if="trend"
              class="text-sm"
              :class="trend.isPositive ? 'text-green-600' : 'text-red-600'"
            >
              {{ trend.isPositive ? '+' : '-' }}{{ trend.value }}%
            </span>
          </div>
        </div>
        <div class="rounded-full bg-primary/10 p-3">
          <Icon :icon="icon" class="h-5 w-5 text-primary" />
        </div>
      </div>
      <p v-if="description" class="mt-2 text-xs text-muted-foreground">
        {{ description }}
      </p>
    </CardContent>
  </Card>
</template>
