<script setup>
import InputError from '@/Components/InputError.vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import Checkbox from '@/Components/shadcn/ui/checkbox/Checkbox.vue'
import Input from '@/Components/shadcn/ui/input/Input.vue'
import Label from '@/Components/shadcn/ui/label/Label.vue'
import { useSeoMetaTags } from '@/Composables/useSeoMetaTags.js'
import { __ } from '@/Composables/useTranslations.js'
import { Link, useForm } from '@inertiajs/vue3'
import { useColorMode } from '@vueuse/core'
import { inject } from 'vue'

const props = defineProps({
  plan: {
    type: String,
    default: null,
  },
})

useColorMode({
  attribute: 'class',
  modes: {
    light: '',
    dark: 'dark',
  },
  initialValue: 'light',
})

useSeoMetaTags({
  title: __('register.title'),
})

const route = inject('route')

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  terms: false,
  plan: props.plan || null,
})

function submit() {
  form.post(route('register'), {
    onSuccess: () => {
      // The redirection will be handled by the server-side RegisterResponse
    },
    onError: (errors) => {
      console.error('Registration errors:', errors)
    },
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<template>
  <div class="flex min-h-screen flex-col items-center justify-center bg-linear-to-b from-background/50 to-background">
    <Card class="mx-auto w-[420px] shadow-lg transition-all duration-300 hover:shadow-xl">
      <CardHeader>
        <CardTitle class="flex justify-center">
          <img src="/tanavitrine_light_icon1.png" alt="Tanavitrine" class="w-20 h-20">
        </CardTitle>
        <CardDescription class="text-center text-2xl font-light">
          {{ __('register.description') }}
        </CardDescription>
      </CardHeader>

      <CardContent>
        <!-- Plan Selection Display -->
        <div v-if="props.plan" class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm font-medium text-blue-800">Plano selecionado: {{ props.plan }}</span>
          </div>
          <p class="text-xs text-blue-600 mt-1">
            Após o registro, você será direcionado para o pagamento
          </p>
        </div>

        <form @submit.prevent="submit">
          <div class="grid gap-4">
            <div class="grid gap-2">
              <Label for="name">{{ __('register.name') }}</Label>
              <Input id="name" v-model="form.name" type="text" required autofocus autocomplete="name" />
              <InputError :message="form.errors.name" />
            </div>

            <div class="grid gap-2">
              <Label for="email">{{ __('register.email') }}</Label>
              <Input id="email" v-model="form.email" type="email" required autocomplete="username" />
              <InputError :message="form.errors.email" />
            </div>

            <div class="grid gap-2">
              <Label for="password">{{ __('register.password') }}</Label>
              <Input
                id="password" v-model="form.password" type="password" required
                autocomplete="new-password"
              />
              <InputError :message="form.errors.password" />
            </div>

            <div class="grid gap-2">
              <Label for="password_confirmation">{{ __('register.confirm_password') }}</Label>
              <Input
                id="password_confirmation" v-model="form.password_confirmation" type="password"
                required autocomplete="new-password"
              />
              <InputError :message="form.errors.password_confirmation" />
            </div>

            <div v-if="$page.props.jetstream.hasTermsAndPrivacyPolicyFeature">
              <div class="flex items-center space-x-2">
                <Checkbox id="terms" v-model:checked="form.terms" name="terms" required />
                <label for="terms" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                  {{ __('register.terms') }}
                  <a target="_blank" :href="route('terms.show')" class="rounded-md text-sm underline">{{ __('register.terms_of_service') }}</a>
                  {{ __('register.and') }}
                  <a target="_blank" :href="route('policy.show')" class="rounded-md text-sm underline">{{ __('register.privacy_policy') }}</a>
                </label>
              </div>
              <InputError :message="form.errors.terms" />
            </div>

            <!-- Hidden fields for plan and interval -->
            <input v-if="form.plan" v-model="form.plan" type="hidden" />

            <div class="flex items-center justify-end gap-4">
              <Link :href="route('login')" class="text-sm underline">
                {{ __('register.already_registered') }}
              </Link>

              <Button :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                {{ __('register.register') }}
              </Button>
            </div>
          </div>
        </form>
      </CardContent>
    </Card>
  </div>
</template>
