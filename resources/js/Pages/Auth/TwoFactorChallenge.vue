<script setup>
import InputError from '@/Components/InputError.vue'
import AuthenticationCardLogo from '@/Components/LogoRedirect.vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import Input from '@/Components/shadcn/ui/input/Input.vue'
import Label from '@/Components/shadcn/ui/label/Label.vue'
import { useSeoMetaTags } from '@/Composables/useSeoMetaTags.js'
import { __ } from '@/Composables/useTranslations.js'

import { useForm } from '@inertiajs/vue3'
import { inject, nextTick, ref } from 'vue'

useSeoMetaTags({
  title: __('auth.two_factor.title'),
})

const route = inject('route')
const recovery = ref(false)

const form = useForm({
  code: '',
  recovery_code: '',
})

const recoveryCodeInput = ref(null)
const codeInput = ref(null)

async function toggleRecovery() {
  recovery.value ^= true

  await nextTick()

  if (recovery.value) {
    recoveryCodeInput.value.focus()
    form.code = ''
  }
  else {
    codeInput.value.focus()
    form.recovery_code = ''
  }
}

function submit() {
  form.post(route('two-factor.login'))
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
          {{ __('auth.two_factor.title') }}
        </CardDescription>
      </CardHeader>

      <CardContent>
        <div class="mb-4 text-sm">
          <template v-if="!recovery">
            {{ __('auth.two_factor.description.code') }}
          </template>

          <template v-else>
            {{ __('auth.two_factor.description.recovery') }}
          </template>
        </div>

        <form @submit.prevent="submit">
          <div v-if="!recovery">
            <Label for="code">{{ __('auth.two_factor.code') }}</Label>
            <Input
              id="code" ref="codeInput" v-model="form.code" type="text" inputmode="numeric"
              class="mt-1 block w-full" autofocus autocomplete="one-time-code"
            />
            <InputError class="mt-2" :message="form.errors.code" />
          </div>

          <div v-else>
            <Label for="recovery_code">{{ __('auth.two_factor.recovery_code') }}</Label>
            <Input
              id="recovery_code" ref="recoveryCodeInput" v-model="form.recovery_code" type="text"
              class="mt-1 block w-full" autocomplete="one-time-code"
            />
            <InputError class="mt-2" :message="form.errors.recovery_code" />
          </div>

          <div class="mt-4 flex items-center justify-end">
            <button type="button" class="cursor-pointer text-sm" @click.prevent="toggleRecovery">
              <template v-if="!recovery">
                {{ __('auth.two_factor.use_recovery') }}
              </template>

              <template v-else>
                {{ __('auth.two_factor.use_code') }}
              </template>
            </button>

            <Button class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
              {{ __('auth.two_factor.login') }}
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  </div>
</template>
