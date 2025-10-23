<script setup>
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
import InputError from '@/Components/InputError.vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import Checkbox from '@/Components/shadcn/ui/checkbox/Checkbox.vue'
import Input from '@/Components/shadcn/ui/input/Input.vue'
import Label from '@/Components/shadcn/ui/label/Label.vue'
import { Link } from '@inertiajs/vue3'
import { inject } from 'vue'

const route = inject('route')
const form = defineModel()
const emit = defineEmits(['next', 'prev'])

const props = defineProps({
  errors: {
    type: Object,
    default: () => ({}),
  },
})

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

function formatPhone(value) {
  const numbers = value.replace(/\D/g, '')
  if (numbers.length <= 10) {
    return numbers.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3')
  }
  return numbers.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3')
}

function handlePhoneInput(e) {
  form.value.user_phone = formatPhone(e.target.value)
}

function formatCPF(value) {
  const numbers = value.replace(/\D/g, '')
  return numbers.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4')
}

function handleCPFInput(e) {
  form.value.cpf = formatCPF(e.target.value)
}
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
  </div>
</template>
