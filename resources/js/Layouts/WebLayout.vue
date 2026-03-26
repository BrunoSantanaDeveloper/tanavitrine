<script setup>
import Button from '@/Components/shadcn/ui/button/Button.vue'
import { Icon } from '@iconify/vue'
import { Link } from '@inertiajs/vue3'
import { useColorMode } from '@vueuse/core'
import { ref } from 'vue'

const props = defineProps({
  canLogin: {
    type: Boolean,
  },
  canRegister: {
    type: Boolean,
  },
  showFloatingWhatsApp: {
    type: Boolean,
    default: true,
  },
})

const mode = useColorMode({
  attribute: 'class',
  modes: { light: '', dark: 'dark' },
  initialValue: 'light',
})

const navLinks = [
  { label: 'Atacado', href: '/atacado', external: false },
  { label: 'Varejo', href: '/varejo', external: false },
  { label: 'Planos', href: '/prices', external: false },
  { label: 'Contato', href: '#', external: false, action: 'whatsapp' },
]

function handleNavClick(link, event) {
  if (link.action === 'whatsapp') {
    event.preventDefault()
    toggleWhatsApp()
    if (isMenuOpen.value) {
      toggleMenu()
    }
  }
}

const githubUrl = 'https://github.com/shipfastlabs/larasonic-vue'
const twitterUrl = 'https://x.com/pushpak1300?ref=larasonic'

const isMenuOpen = ref(false)
const isWhatsAppOpen = ref(false)
const whatsappMessage = ref('')

function toggleMenu() {
  isMenuOpen.value = !isMenuOpen.value
}

function toggleWhatsApp() {
  isWhatsAppOpen.value = !isWhatsAppOpen.value
  if (!isWhatsAppOpen.value) {
    whatsappMessage.value = ''
  }
}

function sendWhatsApp() {
  const phone = '556231900204'
  const message = whatsappMessage.value || 'Olá! Gostaria de saber mais sobre a Tá na Vitrine.'
  const url = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`
  window.open(url, '_blank')
  toggleWhatsApp()
}
</script>

<template>
  <div class="min-h-screen overflow-x-hidden">
    <header
      class="sticky top-0 z-50 w-full bg-linear-to-r from-teal-900 via-teal-700 to-teal-900 backdrop-blur-sm supports-backdrop-filter:bg-orange-100/40"
    >
      <div class="container flex h-16 items-center justify-between">
        <div class="flex items-center">
          <a class="flex items-center text-white text-2xl font-bold " href="/" :aria-label="$page.props.name">
            <img src="/tanavitrine_light_icon1.png" alt="tanavitrine" class="h-12">
            <div class="">
                <span class="block mt-2">Tá na</span>
                <span class="block -mt-3">Vitrine</span>
            </div>

          </a>
        </div>
        <div class="flex items-center">
          <nav class="hidden md:flex items-center space-x-6 text-md font-medium sm:ml-4">
            <a
              v-for="link in navLinks" :key="link.href" :href="link.href"
              class="transition-colors hover:text-yellow-500 cursor-pointer" :class="[
                link.href.startsWith('http') ? '' : 'text-white',
              ]" :target="link.href.startsWith('http') ? '_blank' : undefined"
              :rel="link.href.startsWith('http') ? 'noreferrer' : undefined"
              @click="handleNavClick(link, $event)"
            >
              {{ link.label }}
            </a>
          </nav>
        </div>
        <div class="flex items-center space-x-4">
          <div class="hidden sm:flex space-x-2">
            <template v-if="!$page.props.auth.user">
              <Button variant="outline" :as="Link" href="/login" prefetch="mount">
                Entrar
              </Button>
              <Button :as="Link" href="/prices/#pricing" prefetch="mount">
                Anunciar
              </Button>
            </template>
            <Button v-else variant="outline" :as="Link" href="/dashboard" prefetch="mount">
              Acessar Painel
            </Button>
          </div>

          <!-- Mobile Anunciar Button (visible only on mobile) -->
          <div class="sm:hidden">
            <template v-if="!$page.props.auth.user">
              <Button size="sm" :as="Link" href="/prices/#pricing" prefetch="mount" class="text-xs px-3">
                Anunciar
              </Button>
            </template>
            <Button v-else size="sm" variant="outline" :as="Link" href="/dashboard" prefetch="mount" class="text-xs px-3">
              Painel
            </Button>
          </div>

          <Button
            variant="ghost" size="icon" aria-label="Toggle Theme"
            @click="mode = mode === 'dark' ? 'light' : 'dark'"
            class="hidden"
          >
            <Icon
              class="text-muted-foreground h-6 w-6"
              :icon="mode === 'dark' ? 'lucide:sun' : 'lucide:moon'"
            />
          </Button>
          <Button class="md:hidden text-white hover:text-yellow-500" variant="ghost" size="icon" aria-label="Toggle menu" @click="toggleMenu">
            <Icon :icon="isMenuOpen ? 'lucide:x' : 'lucide:menu'" class="h-6 w-6 text-white" aria-hidden="true" />
          </Button>
        </div>
      </div>
      <!-- Mobile menu -->
      <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-2"
      >
        <div v-show="isMenuOpen" class="md:hidden border-t bg-background/95 backdrop-blur-sm">
          <nav class="flex flex-col p-4 space-y-3">
            <a
              v-for="link in navLinks" :key="link.href" :href="link.href"
              class="px-4 py-2 text-sm font-medium transition-colors hover:bg-muted rounded-lg cursor-pointer"
              :target="link.href.startsWith('http') ? '_blank' : undefined"
              :rel="link.href.startsWith('http') ? 'noreferrer' : undefined"
              @click="handleNavClick(link, $event)"
            >
              {{ link.label }}
            </a>

            <div class="pt-3 border-t space-y-2">
              <template v-if="!$page.props.auth.user">
                <Button
                  variant="outline" :as="Link" href="/login" class="w-full" prefetch="mount"
                  @click="toggleMenu"
                >
                  <Icon icon="lucide:log-in" class="size-4 mr-2" aria-hidden="true" />
                  Entrar
                </Button>
                <Button
                  :as="Link" href="/prices" class="w-full" prefetch="mount"
                  @click="toggleMenu"
                >
                  <Icon icon="lucide:rocket" class="size-4 mr-2" aria-hidden="true" />
                  Anunciar
                </Button>
              </template>
              <Button
                v-else :as="Link" href="/dashboard" class="w-full"
                prefetch="mount" @click="toggleMenu"
              >
                <Icon icon="lucide:layout-dashboard" class="size-4 mr-2" aria-hidden="true" />
                Acessar Painel
              </Button>
            </div>
          </nav>
        </div>
      </Transition>
    </header>

    <!-- Sticky Selector Slot (background only applied when content is visible) -->
    <div class="sticky top-16 z-40">
      <slot name="sticky-selector" />
    </div>

    <slot />

    <!-- Footer -->
    <footer class="border-t bg-muted/30">
      <div class="container mx-auto px-4 py-12 sm:px-6 lg:px-8">
        <!-- Main Footer Content -->
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4 mb-8">
          <!-- Brand Column -->
          <div class="space-y-4">
            <a class="flex items-center text-teal-900 text-2xl font-bold " href="/" :aria-label="$page.props.name">
            <img src="/tanavitrine_light_icon1.png" alt="tanavitrine" class="h-12">
            <div class="">
                <span class="block mt-2">Tá na</span>
                <span class="block -mt-3">Vitrine</span>
            </div>

          </a>
            <p class="text-sm text-muted-foreground">
              Conectando lojas de moda com compradores. Sua vitrine online de atacado e varejo.
            </p>
          </div>

          <!-- Navigation Column -->
          <div>
            <h3 class="font-semibold mb-4">Explorar</h3>
            <ul class="space-y-3 text-sm">
              <li>
                <a href="/atacado" class="text-muted-foreground hover:text-foreground transition-colors">
                  Atacado
                </a>
              </li>
              <li>
                <a href="/varejo" class="text-muted-foreground hover:text-foreground transition-colors">
                  Varejo
                </a>
              </li>
              <li>
                <a href="/prices" class="text-muted-foreground hover:text-foreground transition-colors">
                  Planos e Preços
                </a>
              </li>
              <li>
                <a href="/about" class="text-muted-foreground hover:text-foreground transition-colors">
                  Sobre
                </a>
              </li>
            </ul>
          </div>

          <!-- Legal Column -->
          <div>
            <h3 class="font-semibold mb-4">Legal</h3>
            <ul class="space-y-3 text-sm">
              <li>
                <a href="/privacy-policy" class="text-muted-foreground hover:text-foreground transition-colors">
                  Política de Privacidade
                </a>
              </li>
              <li>
                <a href="/terms-of-service" class="text-muted-foreground hover:text-foreground transition-colors">
                  Termos de Uso
                </a>
              </li>
              <li>
                <a href="#" @click.prevent="toggleWhatsApp" class="text-muted-foreground hover:text-foreground transition-colors cursor-pointer">
                  Fale Conosco
                </a>
              </li>
            </ul>
          </div>

          <!-- Contact Column -->
          <div>
            <h3 class="font-semibold mb-4">Contato</h3>
            <ul class="space-y-3 text-sm text-muted-foreground">
              <li class="flex items-center gap-2">
                <Icon icon="lucide:mail" class="size-4" aria-hidden="true" />
                <a href="mailto:contato@tanavitrine.com.br" class="hover:text-foreground transition-colors">
                  contato@tanavitrine.com.br
                </a>
              </li>
              <li class="flex items-center gap-2">
                <Icon icon="lucide:phone" class="size-4" aria-hidden="true" />
                <a href="tel:+556231900204" class="hover:text-foreground transition-colors">
                  (62) 3190- 0204
                </a>
              </li>
              <li class="flex items-center gap-2 mt-4">
                <Icon icon="lucide:instagram" class="size-5" aria-hidden="true" />
                <a
                  href="https://instagram.com/tanavitrineoficial/"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="hover:text-foreground transition-colors"
                  aria-label="Instagram"
                >
                  tanavitrineoficial
                </a>
              </li>
            </ul>
          </div>
        </div>

        <!-- Bottom Footer -->
        <div class="border-t pt-8 flex flex-col sm:flex-row items-center justify-between gap-4">
          <p class="text-sm text-muted-foreground text-center sm:text-left">
            © {{ new Date().getFullYear() }} Tá na Vitrine. Todos os direitos reservados.
          </p>
          <div class="flex items-center gap-4">
            <button
              @click="mode = mode === 'dark' ? 'light' : 'dark'"
              class="text-muted-foreground hover:text-foreground transition-colors hidden"
              aria-label="Toggle Theme"
            >
              <Icon
                class="h-5 w-5"
                :icon="mode === 'dark' ? 'lucide:sun' : 'lucide:moon'"
              />
            </button>
          </div>
        </div>
      </div>
    </footer>

    <!-- WhatsApp Floating Button -->
    <div v-if="props.showFloatingWhatsApp" class="fixed bottom-6 right-6 z-[100]">
      <!-- Chat Box -->
      <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0 translate-y-4"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-4"
      >
        <div
          v-if="isWhatsAppOpen"
          class="absolute bottom-20 right-0 w-80 sm:w-96 bg-background rounded-2xl shadow-2xl border overflow-hidden"
        >
          <!-- Header -->
          <div class="bg-[#25D366] p-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="relative">
                <img src="/tanavitrine_light_icon1.png" alt="Tá na Vitrine" class="h-10 w-10 rounded-full bg-white p-1">
                <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-400 rounded-full border-2 border-[#25D366]" />
              </div>
              <div class="text-white">
                <h3 class="font-semibold text-sm">Tá na Vitrine</h3>
                <p class="text-xs opacity-90">Online</p>
              </div>
            </div>
            <button
              @click="toggleWhatsApp"
              class="text-white hover:bg-white/10 rounded-full p-1 transition-colors"
            >
              <Icon icon="lucide:x" class="h-5 w-5" />
            </button>
          </div>

          <!-- Messages Area -->
          <div class="bg-[#ECE5DD] p-4 min-h-[200px] max-h-[300px] overflow-y-auto">
            <div class="flex justify-start mb-4">
              <div class="bg-white rounded-lg rounded-tl-none p-3 shadow-sm max-w-[80%]">
                <p class="text-sm text-gray-800">
                  Olá! 👋 Como podemos ajudar você hoje?
                </p>
                <span class="text-xs text-gray-500 mt-1 block">Agora</span>
              </div>
            </div>
          </div>

          <!-- Input Area -->
          <div class="bg-background p-4 border-t">
            <form @submit.prevent="sendWhatsApp" class="flex gap-2">
              <textarea
                v-model="whatsappMessage"
                placeholder="Digite sua mensagem..."
                class="flex-1 resize-none rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#25D366]"
                rows="2"
              />
              <Button
                type="submit"
                size="icon"
                class="bg-[#25D366] hover:bg-[#20BA5A] self-end"
              >
                <Icon icon="lucide:send" class="h-5 w-5" />
              </Button>
            </form>
          </div>
        </div>
      </Transition>

      <!-- WhatsApp Button -->
      <button
        @click="toggleWhatsApp"
        class="bg-[#25D366] hover:bg-[#20BA5A] text-white rounded-full p-4 shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-110 relative group"
        aria-label="WhatsApp"
      >
        <Icon icon="lucide:message-circle" class="h-6 w-6" />

        <!-- Pulse animation -->
        <span class="absolute inset-0 rounded-full bg-[#25D366] animate-ping opacity-75" />

        <!-- Tooltip -->
        <div class="absolute right-[calc(100%+1rem)] bottom-1 px-3 py-1.5 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-[110]">
          Fale conosco no WhatsApp
          <div class="absolute left-full bottom-3 w-0 h-0 border-t-4 border-b-4 border-l-4 border-transparent border-l-gray-900" />
        </div>
      </button>
    </div>
  </div>
</template>
