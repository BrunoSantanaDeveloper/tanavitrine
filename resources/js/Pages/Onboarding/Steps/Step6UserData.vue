<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import { Icon } from '@iconify/vue'
import InputError from '@/Components/InputError.vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import Checkbox from '@/Components/shadcn/ui/checkbox/Checkbox.vue'
import Input from '@/Components/shadcn/ui/input/Input.vue'
import Label from '@/Components/shadcn/ui/label/Label.vue'
import { inject } from 'vue'
import { formatPhone } from '@/utils/formatters'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/shadcn/ui/dialog'

const route = inject('route')
const form = defineModel()
const emit = defineEmits(['next', 'prev'])

const props = defineProps({
  errors: {
    type: Object,
    default: () => ({}),
  },
  plan: {
    type: Object,
    required: false,
  },
})

// Exit Intent Modal
const showExitModal = ref(false)
const exitCoupon = ref(null)
const exitModalShown = ref(false)

const isValid = computed(() => {
  return (
    form.value.name &&
    form.value.email &&
    form.value.user_phone &&
    form.value.password &&
    form.value.password_confirmation &&
    form.value.password === form.value.password_confirmation &&
    form.value.password.length >= 8 &&
    form.value.terms
  )
})

const planPrice = computed(() => {
  return parseFloat(props.plan?.price || 0)
})

const hasCouponApplied = computed(() => {
  return form.value.coupon_code !== null && form.value.coupon_code !== undefined
})

const isSpecialCoupon = computed(() => {
  // Se já tem cupom aplicado, verificar se é de 100%
  return hasCouponApplied.value && exitCoupon.value?.is_special
})

function handlePhoneInput(e) {
  form.value.user_phone = formatPhone(e.target.value)
}

// Exit Intent Functions
async function fetchExitIntentCoupon() {
  try {
    const response = await axios.post('/api/coupons/exit-intent', {
      plan_price: planPrice.value,
    })

    if (response.data.found) {
      exitCoupon.value = response.data.coupon
    }
  } catch (error) {
    console.error('Error fetching exit intent coupon:', error)
  }
}

function handleMouseLeave(e) {
  // Só mostrar se:
  // 1. Mouse está saindo pela parte superior da página (indo para barra de endereços/tabs)
  // 2. Não aplicou cupom de 100%
  // 3. Modal ainda não foi mostrado
  // 4. Existe cupom de exit intent disponível
  const shouldShow = e.clientY <= 0 &&
                     !isSpecialCoupon.value &&
                     !exitModalShown.value &&
                     exitCoupon.value

  if (shouldShow) {
    showExitModal.value = true
    exitModalShown.value = true
  }
}

function applyExitCoupon() {
  if (exitCoupon.value) {
    form.value.coupon_code = exitCoupon.value.code
    showExitModal.value = false

    // Mostrar mensagem de sucesso
    alert(`Cupom ${exitCoupon.value.code} aplicado! Você ganhou ${exitCoupon.value.duration_months} meses grátis!`)
  }
}

function closeExitModal() {
  showExitModal.value = false
}

// Lifecycle
onMounted(() => {
  // Buscar cupom de exit intent ao montar componente
  fetchExitIntentCoupon()

  // Adicionar listener de mouseleave para detectar quando mouse sai da página
  document.addEventListener('mouseleave', handleMouseLeave)
})

onUnmounted(() => {
  // Remover listener ao desmontar
  document.removeEventListener('mouseleave', handleMouseLeave)
})
</script>

<template>
  <div class="space-y-6">
    <div class="text-center mb-6">
      <div class="w-16 h-16 bg-teal-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <Icon icon="lucide:user-circle" class="h-8 w-8 text-teal-600" />
      </div>
      <h2 class="text-2xl font-bold mb-2">Seus Dados</h2>
      <p class="text-muted-foreground">
        Crie sua conta para gerenciar sua vitrine no TanaVitrine
      </p>
    </div>

    <div class="grid gap-4">
      <div class="grid gap-2">
        <Label for="name">Nome Completo *</Label>
        <Input
          id="name"
          v-model="form.name"
          type="text"
          required
          autocomplete="name"
        />
        <InputError :message="errors.name" />
      </div>

      <div class="grid gap-2">
        <Label for="email">Email *</Label>
        <Input
          id="email"
          v-model="form.email"
          type="email"
          required
          autocomplete="username"
        />
        <InputError :message="errors.email" />
        <p class="text-xs text-muted-foreground">
          Use este email para fazer login no painel
        </p>
      </div>

      <div class="grid gap-2">
        <Label for="user_phone">Telefone/WhatsApp *</Label>
        <Input
          id="user_phone"
          v-model="form.user_phone"
          type="tel"
          placeholder="(62) 99999-9999"
          maxlength="15"
          @input="handlePhoneInput"
        />
        <InputError :message="errors.user_phone" />
      </div>

      <div class="grid gap-2">
        <Label for="password">Senha *</Label>
        <Input
          id="password"
          v-model="form.password"
          type="password"
          required
          autocomplete="new-password"
        />
        <InputError :message="errors.password" />
        <p class="text-xs text-muted-foreground">
          Mínimo 8 caracteres
        </p>
      </div>

      <div class="grid gap-2">
        <Label for="password_confirmation">Confirmar Senha *</Label>
        <Input
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          required
          autocomplete="new-password"
        />
        <InputError :message="errors.password_confirmation" />
        <p
          v-if="form.password && form.password_confirmation && form.password !== form.password_confirmation"
          class="text-xs text-destructive"
        >
          As senhas não coincidem
        </p>
      </div>

      <div class="flex items-start space-x-2">
        <Checkbox
          id="terms"
          v-model:checked="form.terms"
          name="terms"
          required
        />
        <label
          for="terms"
          class="text-sm leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
        >
          Eu aceito os
          <a target="_blank" :href="route('terms.show')" class="rounded-md text-sm underline text-primary">
            Termos de Serviço
          </a>
          e a
          <a target="_blank" :href="route('policy.show')" class="rounded-md text-sm underline text-primary">
            Política de Privacidade
          </a>
        </label>
      </div>
      <InputError :message="errors.terms" />
    </div>

    <!-- Alerta de Cupom Aplicado -->
    <div v-if="hasCouponApplied" class="mt-4 p-4 bg-green-50 border-2 border-green-300 rounded-lg">
      <div class="flex items-center gap-2">
        <Icon icon="lucide:check-circle" class="h-5 w-5 text-green-600" />
        <p class="text-sm font-semibold text-green-800">
          Cupom <span class="font-mono">{{ form.coupon_code }}</span> aplicado!
        </p>
      </div>
      <p class="text-xs text-green-700 mt-1">
        Você receberá seu desconto especial após completar o cadastro.
      </p>
    </div>

    <!-- Botões -->
    <div class="flex justify-between pt-4">
      <Button
        variant="outline"
        size="lg"
        @click="emit('prev')"
      >
        <Icon icon="lucide:arrow-left" class="mr-2 h-5 w-5" />
        Voltar
      </Button>
      <Button
        size="lg"
        @click="emit('next')"
        :disabled="!isValid"
      >
        Continuar
        <Icon icon="lucide:arrow-right" class="ml-2 h-5 w-5" />
      </Button>
    </div>

    <!-- Exit Intent Modal -->
    <Dialog v-model:open="showExitModal">
      <DialogContent class="max-w-lg">
        <DialogHeader class="space-y-1">
          <div class="flex items-center justify-center mb-1">
            <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center animate-pulse">
              <Icon icon="lucide:gift" class="h-6 w-6 text-white" />
            </div>
          </div>
          <DialogTitle class="text-center text-lg font-bold bg-gradient-to-r from-yellow-600 to-orange-600 bg-clip-text text-transparent">
            🎉 ESPERE! Você Ganhou um Presente Especial! 🎉
          </DialogTitle>
          <DialogDescription class="text-center text-xs sm:text-sm">
            Antes de sair, saiba que você acaba de ganhar <span class="font-bold text-base text-yellow-600">100% DE DESCONTO</span> por <span class="font-bold">{{ exitCoupon?.duration_months }} meses</span>!
          </DialogDescription>
        </DialogHeader>

        <div class="py-2 space-y-2.5">
          <!-- Benefícios -->
          <div class="bg-gradient-to-br from-yellow-50 to-orange-50 p-2.5 rounded-lg border-2 border-yellow-300">
            <h3 class="font-bold text-sm mb-1.5 text-center text-gray-800">
              Com este cupom VIP você terá:
            </h3>
            <ul class="space-y-1">
              <li class="flex items-start gap-1.5">
                <Icon icon="lucide:check-circle" class="h-4 w-4 text-green-600 flex-shrink-0 mt-0.5" />
                <span class="text-xs text-gray-700"><strong>{{ exitCoupon?.duration_months }} meses GRÁTIS</strong> do plano {{ plan?.name || 'escolhido' }}</span>
              </li>
              <li class="flex items-start gap-1.5">
                <Icon icon="lucide:check-circle" class="h-4 w-4 text-green-600 flex-shrink-0 mt-0.5" />
                <span class="text-xs text-gray-700">Acesso completo a <strong>todos os recursos</strong></span>
              </li>
              <li class="flex items-start gap-1.5">
                <Icon icon="lucide:check-circle" class="h-4 w-4 text-green-600 flex-shrink-0 mt-0.5" />
                <span class="text-xs text-gray-700">Economia de <strong class="text-sm text-green-600">R$ {{ (planPrice * (exitCoupon?.duration_months || 1)).toFixed(2) }}</strong></span>
              </li>
              <li class="flex items-start gap-1.5">
                <Icon icon="lucide:check-circle" class="h-4 w-4 text-green-600 flex-shrink-0 mt-0.5" />
                <span class="text-xs text-gray-700">Sem compromisso - cancele quando quiser</span>
              </li>
            </ul>
          </div>

          <!-- Código do Cupom -->
          <div class="bg-white border-3 border-dashed border-yellow-400 p-2.5 rounded-lg text-center">
            <p class="text-xs text-gray-600 mb-1">Seu código VIP exclusivo:</p>
            <div class="bg-gradient-to-r from-yellow-100 to-orange-100 py-1.5 px-3 rounded-lg mb-1">
              <p class="text-xl sm:text-2xl font-bold text-yellow-700 tracking-wider">
                {{ exitCoupon?.code }}
              </p>
            </div>
            <p class="text-xs text-gray-500">
              Este cupom é válido apenas para você e expira em breve!
            </p>
          </div>

          <!-- Urgência -->
          <div class="bg-red-50 border-2 border-red-300 rounded-lg p-2 text-center">
            <p class="text-xs font-bold text-red-800">
              ⚠️ Complete seu cadastro agora para garantir este desconto!
            </p>
            <p class="text-xs text-red-600 mt-0.5">
              Oferta válida apenas para novos cadastros
            </p>
          </div>
        </div>

        <DialogFooter class="flex-col sm:flex-row gap-2">
          <Button
            variant="outline"
            size="sm"
            class="w-full sm:w-auto"
            @click="closeExitModal"
          >
            Não, obrigado
          </Button>
          <Button
            size="sm"
            class="w-full sm:w-auto bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-600 hover:to-orange-600 text-white font-bold shadow-lg"
            @click="applyExitCoupon"
          >
            <Icon icon="lucide:sparkles" class="mr-2 h-4 w-4" />
            SIM! Aplicar e Finalizar
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
