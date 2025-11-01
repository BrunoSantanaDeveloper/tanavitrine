<script setup>
import { Button } from '@/Components/shadcn/ui/button'
import Separator from '@/Components/shadcn/ui/separator/Separator.vue'
import SidebarTrigger from '@/Components/shadcn/ui/sidebar/SidebarTrigger.vue'
import { useToast } from '@/Components/shadcn/ui/toast/use-toast'
import { usePage } from '@inertiajs/vue3'
import { Bell, HelpCircle, Search } from 'lucide-vue-next'

const { toast } = useToast()
const page = usePage()

function handleNotificationClick() {
  toast({
    title: 'Notificações',
    description: 'Você não tem novas notificações',
  })
}

function handleHelpClick() {
  // Pega o nome da loja do usuário atual (se existir)
  const storeName = page.props.auth?.user?.current_team?.name || 'Minha Loja'

  // Cria a mensagem de suporte
  const message = `Olá! Preciso de suporte.\n\nLoja: ${storeName}\nUsuário: ${page.props.auth?.user?.name}`

  // Codifica a mensagem para URL
  const encodedMessage = encodeURIComponent(message)

  // Abre o WhatsApp em uma nova aba
  window.open(`https://wa.me/556231900204?text=${encodedMessage}`, '_blank')
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
        <Button
         variant="outline"
          class="relative"
          @click="handleHelpClick"
          title="Solicitar suporte via WhatsApp"
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
