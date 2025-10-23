<script setup>
import InputError from '@/Components/InputError.vue'
import AuthenticationCardLogo from '@/Components/LogoRedirect.vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import Input from '@/Components/shadcn/ui/input/Input.vue'
import Label from '@/Components/shadcn/ui/label/Label.vue'
import { useSeoMetaTags } from '@/Composables/useSeoMetaTags.js'
import { useForm } from '@inertiajs/vue3'
import { inject } from 'vue'
import { __ } from '@/Composables/useTranslations.js'

defineProps({
  status: String,
})

useSeoMetaTags({
  title: __('auth.forgot_password.title'),
})

const route = inject('route')
const form = useForm({
  email: '',
})

function submit() {
  form.post(route('password.email'))
}
</script>

<template>
  <div class="flex min-h-screen flex-col items-center justify-center">
    <Card class="mx-auto max-w-lg">
      <CardHeader>
        <CardTitle class="flex justify-center">
          <AuthenticationCardLogo />
        </CardTitle>
        <CardDescription class="text-center text-2xl">
          {{ __('auth.forgot_password.title') }}
        </CardDescription>
      </CardHeader>

      <CardContent>
        <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
          {{ __('auth.forgot_password.description') }}
        </div>

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600 dark:text-green-400">
          {{ __('auth.forgot_password.status') }}
        </div>

        <form @submit.prevent="submit">
          <div>
            <Label for="email">{{ __('auth.forgot_password.email') }}</Label>
            <Input
              id="email" v-model="form.email" type="email" class="mt-1 block w-full" required autofocus
              autocomplete="username"
            />
            <InputError class="mt-2" :message="form.errors.email" />
          </div>

          <div class="mt-4 flex items-center justify-end">
            <Button :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
              {{ __('auth.forgot_password.button') }}
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  </div>
</template>
