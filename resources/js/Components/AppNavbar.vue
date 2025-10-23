<script setup>
import { Button } from '@/Components/shadcn/ui/button'
import Separator from '@/Components/shadcn/ui/separator/Separator.vue'
import SidebarTrigger from '@/Components/shadcn/ui/sidebar/SidebarTrigger.vue'
import { useToast } from '@/Components/shadcn/ui/toast/use-toast'
import { useOnboarding } from '@/Composables/useOnboarding'
import { Icon } from '@iconify/vue'
import { Link } from '@inertiajs/vue3'
import { Bell, HelpCircle, Search } from 'lucide-vue-next'
import { ref, watch } from 'vue'

const { toast } = useToast()
const { showTutorial } = useOnboarding()

// Adicionar um listener para o evento show-tutorial
const emit = defineEmits(['show-tutorial'])
watch(() => emit('show-tutorial'), () => {
  showTutorial()
})

function handleNotificationClick() {
  toast({
    title: 'Notificações',
    description: 'Você não tem novas notificações',
  })
}

function handleTutorialClick() {
  showTutorial()
}
</script>

<template>
  <nav class="sticky top-0 z-10 bg-white border-b border-gray-200 w-full">
    <div class="container flex items-center justify-between px-4 py-2 mx-auto">
      <div class="flex items-center space-x-6">
        <SidebarTrigger class="-ml-1" />
        <Separator orientation="vertical" class="mr-2 h-4 hidden md:block" />

        
      </div>

      <div class="flex items-center space-x-4">
        <div class="relative hidden md:block">
          <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
          <input
            type="text"
            placeholder="Buscar conteúdo..."
            class="pl-10 pr-4 py-2 rounded-full bg-gray-100 border-none focus:outline-none focus:ring-2 focus:ring-primary/20 w-64"
          >
        </div>
        <Button
         variant="outline"
          class="relative"
          @click="handleTutorialClick"
          title="Ver tutorial"
        >
          <HelpCircle class="h-5 w-5" />
          Ajuda
        </Button>

        <Button
          variant="ghost"
          size="icon"
          class="relative"
          @click="handleNotificationClick"
        >
          <Bell class="h-5 w-5" />
          <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full" />
        </Button>
      </div>
    </div>
  </nav>
</template>
