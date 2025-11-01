<script setup>
import { __ } from '@/Composables/useTranslations.js'
import AppLayout from '@/Layouts/AppLayout.vue'
import SubscriptionManager from '@/Pages/Subscriptions/Partials/SubscriptionManager.vue'

defineProps({
  activeSubscriptions: {
    type: Array,
    default: () => [],
  },
  availableSubscriptions: {
    type: Array,
    default: () => [],
  },
  activeInvoices: {
    type: Array,
    default: () => [],
  },
  plans: {
    type: Array,
    required: true,
  },
  currentPlan: {
    type: Object,
    default: null,
  },
  limits: {
    type: Object,
    required: true,
  },
  usage: {
    type: Object,
    required: true,
  },
})
</script>

<template>
  <AppLayout :title="__('subscriptions.title')">
    <SubscriptionManager
      :active-subscriptions="activeSubscriptions"
      :available-subscriptions="availableSubscriptions"
      :plans="plans"
      :current-plan="currentPlan"
      :limits="limits"
      :usage="usage"
    />
  </AppLayout>
</template>
