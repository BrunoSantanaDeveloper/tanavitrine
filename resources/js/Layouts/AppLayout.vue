<script setup>
import AppNavbar from '@/Components/AppNavbar.vue'
import AppSidebarContent from '@/Components/AppSidebarContent.vue'
import AppTeamManager from '@/Components/AppTeamManager.vue'
import AppUserManager from '@/Components/AppUserManager.vue'
import { Sidebar, SidebarFooter, SidebarHeader, SidebarInset } from '@/Components/shadcn/ui/sidebar'
import SidebarMenu from '@/Components/shadcn/ui/sidebar/SidebarMenu.vue'
import SidebarMenuItem from '@/Components/shadcn/ui/sidebar/SidebarMenuItem.vue'
import SidebarProvider from '@/Components/shadcn/ui/sidebar/SidebarProvider.vue'
import Sonner from '@/Components/shadcn/ui/sonner/Sonner.vue'
import Toast from '@/Components/shadcn/ui/toast/Toast.vue'
import ToastProvider from '@/Components/shadcn/ui/toast/ToastProvider.vue'
import { useSeoMetaTags } from '@/Composables/useSeoMetaTags.js'
import { useColorMode } from '@vueuse/core'
import OnboardingDialog from '@/Components/OnboardingDialog.vue'
import { useOnboarding } from '@/Composables/useOnboarding.js'
import { onMounted } from 'vue'

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
})

useSeoMetaTags({
  title: props.title,
})

// Definir modo claro como padrão
const mode = useColorMode({
  attribute: 'class',
  modes: { light: '', dark: 'dark' },
  initialValue: 'light',
})

const { showTutorial } = useOnboarding()

onMounted(() => {
  // Verifica se é o primeiro acesso
  const isFirstAccess = !localStorage.getItem('onboarding_completed') || localStorage.getItem('onboarding_completed') === 'false'
  console.log('isFirstAccess', isFirstAccess)
  if (isFirstAccess) {
    console.log('isFirstAccess')
  }
})
</script>

<template>
  <div class="min-h-screen bg-background">
    <Sonner position="top-center" :expand="true" rich-colors />
    <ToastProvider>
      <Toast />

      <SidebarProvider :default-open="true">
        <Sidebar collapsible="icon">
          <SidebarHeader>
            <SidebarMenu>
              <SidebarMenuItem>
                <AppTeamManager v-if="$page.props.jetstream.hasTeamFeatures" />
              </SidebarMenuItem>
            </SidebarMenu>
          </SidebarHeader>

          <AppSidebarContent
            :store="$page.props.auth?.user?.current_team"
            :subscription-nav="$page.props.subscriptionNav"
          />

          <SidebarFooter>
            <SidebarMenu>
              <SidebarMenuItem>
                <AppUserManager />
              </SidebarMenuItem>
            </SidebarMenu>
          </SidebarFooter>
        </Sidebar>

        <SidebarInset>
          <header
            class="h-12 w-full shrink-0 items-center gap-2 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12"
          >
            <div class="items-center gap-2 w-full">
              <AppNavbar />
            </div>
          </header>
          <main class="flex flex-1 flex-col gap-4 pt-0">
            <slot />
          </main>
        </SidebarInset>
      </SidebarProvider>
    </ToastProvider>
    <OnboardingDialog />
  </div>
</template>
