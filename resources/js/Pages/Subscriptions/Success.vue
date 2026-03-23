<script setup>
import { computed, inject, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import { useColorMode } from '@vueuse/core'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import Card from '@/Components/shadcn/ui/card/Card.vue'
import CardContent from '@/Components/shadcn/ui/card/CardContent.vue'

const route = inject('route')

// Mantém visual consistente e claro na confirmação.
useColorMode({
  attribute: 'class',
  modes: {
    light: '',
    dark: 'dark',
  },
  initialValue: 'light',
})

const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
  subscription: {
    type: Object,
    required: true,
  },
  store: {
    type: Object,
    default: null,
  },
  local_mode: {
    type: Boolean,
    default: false,
  },
})

const formattedPrice = computed(() => {
  const value = Number(props.subscription?.price || 0)
  return Number.isFinite(value) ? value.toFixed(2).replace('.', ',') : '0,00'
})
const planFeatures = computed(() => {
  if (!Array.isArray(props.subscription?.features))
    return []

  return props.subscription.features
    .filter(feature => typeof feature === 'string' && feature.trim() !== '')
    .map(feature => feature.trim())
})

onMounted(() => {
  // Após assinatura concluída, limpa a jornada de cadastro da vitrine.
  localStorage.removeItem('onboarding_progress_tanavitrine')
})

const hasStore = computed(() => Boolean(props.store?.slug))
const statusLabel = computed(() => {
  if (!props.store?.status)
    return 'Status indisponível'

  return props.store.status === 'ativo' ? 'Ativa no catálogo' : props.store.status
})

function goDashboard() {
  router.visit(route('dashboard'))
}

function viewStore() {
  if (!hasStore.value)
    return

  router.visit(route('store.show', props.store.slug))
}
</script>

<template>
  <div class="relative min-h-screen overflow-hidden bg-gradient-to-b from-teal-50 via-white to-amber-50 py-10">
    <div class="pointer-events-none absolute -top-28 -left-20 h-72 w-72 rounded-full bg-teal-200/40 blur-3xl" />
    <div class="pointer-events-none absolute -right-20 top-1/2 h-72 w-72 rounded-full bg-orange-200/40 blur-3xl" />

    <div class="container relative mx-auto max-w-4xl px-4">
      <Card class="border-teal-100 shadow-xl">
        <CardContent class="p-6 sm:p-10">
          <div class="mb-8 text-center">
            <div class="mx-auto mb-4 inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-1 text-sm font-semibold text-emerald-700">
              <Icon icon="lucide:shield-check" class="h-4 w-4" />
              Pagamento aprovado
            </div>
            <h1 class="mb-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">
              Assinatura confirmada
            </h1>
            <p class="mx-auto max-w-2xl text-sm text-slate-600 sm:text-base">
              Tudo certo, <span class="font-semibold text-slate-900">{{ user.name }}</span>. Sua assinatura foi ativada e sua vitrine já está pronta para vender.
            </p>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <Card class="border-2 border-teal-200 bg-gradient-to-br from-teal-50 to-cyan-50">
              <CardContent class="space-y-3 p-5">
                <p class="flex items-center gap-2 text-sm font-semibold text-teal-800">
                  <Icon icon="lucide:crown" class="h-4 w-4" />
                  Plano contratado
                </p>
                <p class="text-2xl font-extrabold text-slate-900">{{ subscription.plan_name }}</p>
                <p class="text-sm font-medium text-slate-700">
                  R$ {{ formattedPrice }}/{{ subscription.interval }}
                </p>
                <div v-if="planFeatures.length > 0" class="border-t border-teal-200/80 pt-3">
                  <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-teal-700">
                    Recursos do plano
                  </p>
                  <ul class="space-y-1.5">
                    <li
                      v-for="(feature, index) in planFeatures"
                      :key="`${feature}-${index}`"
                      class="flex items-start gap-2 text-sm text-slate-700"
                    >
                      <Icon icon="lucide:check-circle-2" class="mt-0.5 h-4 w-4 flex-shrink-0 text-emerald-600" />
                      <span>{{ feature }}</span>
                    </li>
                  </ul>
                </div>
                <p v-if="local_mode" class="text-xs text-slate-600">
                  Ativação realizada sem cobrança imediata.
                </p>
              </CardContent>
            </Card>

            <Card v-if="hasStore" class="border border-slate-200 bg-white">
              <CardContent class="space-y-3 p-5">
                <p class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                  <Icon icon="lucide:store" class="h-4 w-4 text-teal-600" />
                  Status da vitrine
                </p>
                <p class="text-base font-semibold text-slate-900">
                  {{ store.name }}
                </p>
                <p class="text-sm text-slate-600">
                  {{ statusLabel }}
                </p>
              </CardContent>
            </Card>
          </div>

          <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="mb-2 text-sm font-semibold text-slate-800">Próximos passos</p>
            <div class="grid gap-2 text-sm text-slate-600 sm:grid-cols-3">
              <p class="flex items-center gap-2"><Icon icon="lucide:check-circle-2" class="h-4 w-4 text-teal-600" /> Finalizar dados da vitrine</p>
              <p class="flex items-center gap-2"><Icon icon="lucide:check-circle-2" class="h-4 w-4 text-teal-600" /> Publicar coleções e fotos</p>
              <p class="flex items-center gap-2"><Icon icon="lucide:check-circle-2" class="h-4 w-4 text-teal-600" /> Compartilhar sua loja</p>
            </div>
          </div>

          <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
            <Button size="lg" class="bg-teal-700 hover:bg-teal-800" @click="goDashboard">
              <Icon icon="lucide:layout-dashboard" class="mr-2 h-5 w-5" />
              Ir para o painel
            </Button>
            <Button
              v-if="hasStore"
              size="lg"
              variant="outline"
              @click="viewStore"
            >
              <Icon icon="lucide:store" class="mr-2 h-5 w-5" />
              Ver minha loja
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
