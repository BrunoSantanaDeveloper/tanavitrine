<script setup>
import {
  SidebarContent,
  SidebarGroup,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/Components/shadcn/ui/sidebar'
import { Icon } from '@iconify/vue'
import { Link } from '@inertiajs/vue3'
import { useColorMode } from '@vueuse/core'
import { computed, inject } from 'vue'

const props = defineProps({
  store: Object,
})

const route = inject('route')
const mode = useColorMode({
  attribute: 'class',
  modes: { light: '', dark: 'dark' },
})

const navigationConfig = computed(() => {
  const config = [
    {
      label: 'Menu Principal',
      items: [
        { name: 'Dashboard', icon: 'lucide:layout-dashboard', route: 'dashboard' },
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
