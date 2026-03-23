<script setup>
import {
  SidebarContent,
  SidebarGroup,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuBadge,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/Components/shadcn/ui/sidebar'
import { Icon } from '@iconify/vue'
import { Link } from '@inertiajs/vue3'
import { useColorMode } from '@vueuse/core'
import { computed, inject } from 'vue'

const props = defineProps({
  store: Object,
  subscriptionNav: {
    type: Object,
    default: null,
  },
})

const route = inject('route')
const mode = useColorMode({
  attribute: 'class',
  modes: { light: '', dark: 'dark' },
})

const subscriptionMenuBadge = computed(() => {
  const data = props.subscriptionNav
  if (!data?.has_subscription) {
    return null
  }

  if (!data.has_active_access) {
    return {
      text: 'Exp',
      class: 'bg-red-100 text-red-700',
    }
  }

  if (data.is_formalized) {
    return {
      text: 'Ativo',
      class: 'bg-emerald-100 text-emerald-700',
    }
  }

  if (data.is_trial && Number(data.trial_days_remaining) > 0) {
    return {
      text: `${Math.ceil(Number(data.trial_days_remaining))}d`,
      class: 'bg-amber-100 text-amber-700',
    }
  }

  if (data.has_active_discount && Number(data.discount_days_remaining) > 0) {
    return {
      text: `${Math.ceil(Number(data.discount_days_remaining))}d`,
      class: 'bg-blue-100 text-blue-700',
    }
  }

  return {
    text: 'Ativo',
    class: 'bg-emerald-100 text-emerald-700',
  }
})

const navigationConfig = computed(() => {
  const config = [
    {
      label: 'Menu Principal',
      items: [
        { name: 'Dashboard', icon: 'lucide:layout-dashboard', route: 'dashboard' },
        {
          name: 'Assinatura',
          icon: 'lucide:credit-card',
          route: 'subscriptions.create',
          badge: subscriptionMenuBadge.value,
        },
      ],
    },
  ]

  // Add store management items if store exists
  if (props.store) {
    config.push({
      label: 'Minha Vitrine',
      items: [
        {
          name: 'Editar Vitrine',
          icon: 'lucide:pencil-line',
          route: 'dashboard.stores.edit',
          params: { slug: props.store.slug }
        },
        {
          name: 'Galeria',
          icon: 'lucide:image',
          route: 'dashboard.stores.photos',
          params: { slug: props.store.slug }
        },
        {
          name: 'Analytics',
          icon: 'lucide:bar-chart',
          route: 'dashboard.stores.analytics',
          params: { slug: props.store.slug }
        },
      ],
    })
  }

  config.push({
    label: 'Minha Conta',
    items: [
      {
        name: 'Perfil e Segurança',
        icon: 'lucide:user-cog',
        route: 'profile.show',
      },
    ],
  })



  return config
})

const isDarkMode = computed(() => mode.value === 'dark')

function renderLink(item) {
  if (item.external) {
    return {
      is: 'a',
      href: item.href || route(item.route),
      target: '_blank',
    }
  }
  return {
    is: Link,
    href: item.params ? route(item.route, item.params) : route(item.route),
  }
}
</script>

<template>
  <SidebarContent>
    <SidebarGroup v-for="(group, index) in navigationConfig" :key="index" :class="group.class">
      <SidebarGroupLabel v-if="group.label">
        {{ group.label }}
      </SidebarGroupLabel>
      <SidebarMenu>
        <SidebarMenuItem
          v-for="item in group.items"
          :key="item.name"
          :class="{ 'font-semibold text-sidebar-accent-foreground bg-sidebar-accent rounded': !item.external && route().current(item.route) }"
        >
          <SidebarMenuButton as-child>
            <component v-bind="renderLink(item)" :is="item.external ? 'a' : Link" prefetch>
              <Icon :icon="item.icon" />
              <span>{{ item.name }}</span>
            </component>
          </SidebarMenuButton>
          <SidebarMenuBadge v-if="item.badge" :class="item.badge.class">
            {{ item.badge.text }}
          </SidebarMenuBadge>
        </SidebarMenuItem>
        <SidebarMenuItem v-if="index === navigationConfig.length - 1" class="hidden">
          <SidebarMenuButton @click="mode = isDarkMode ? 'light' : 'dark'">
            <Icon :icon="isDarkMode ? 'lucide:moon' : 'lucide:sun'" />
            Modo {{ isDarkMode ? 'Escuro' : 'Claro' }}
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarGroup>
  </SidebarContent>
</template>
