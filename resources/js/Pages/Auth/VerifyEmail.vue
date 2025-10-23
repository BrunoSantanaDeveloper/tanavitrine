<script setup>
import AuthenticationCardLogo from '@/Components/LogoRedirect.vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import { useSeoMetaTags } from '@/Composables/useSeoMetaTags.js'
import { __ } from '@/Composables/useTranslations.js'
import { Link, useForm } from '@inertiajs/vue3'
import { useColorMode } from '@vueuse/core'
import { computed, inject } from 'vue'

const props = defineProps({
  status: String,
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
  title: __('auth.verification.title'),
})

const route = inject('route')

const form = useForm({})

function submit() {
  form.post(route('verification.send'))
}

const verificationLinkSent = computed(() => props.status === 'verification-link-sent')
</script>

<template>
  <div class="flex min-h-screen flex-col items-center justify-center">
    <Card class="mx-auto max-w-lg">
      <CardHeader>
        <CardTitle class="flex justify-center">
          <AuthenticationCardLogo />
        </CardTitle>
        <CardDescription class="text-center text-2xl">
          {{ __('auth.verification.title') }}
        </CardDescription>
      </CardHeader>

      <CardContent>
        <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
          {{ __('auth.verification.description') }}
        </div>

        <div v-if="verificationLinkSent" class="mb-4 text-sm font-medium text-green-600 dark:text-green-400">
          {{ __('auth.verification.link_sent') }}
        </div>

        <form @submit.prevent="submit">
          <div class="mt-4 flex items-center justify-between">
            <Button :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
              {{ __('auth.verification.resend') }}
            </Button>

            <div>
              <Link
                :href="route('profile.show')"
                class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
              >
                {{ __('auth.verification.edit_profile') }}
              </Link>

              <Link
                :href="route('logout')" method="post" as="button"
                class="ms-2 rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
              >
                {{ __('auth.verification.logout') }}
              </Link>
            </div>
          </div>
        </form>
      </CardContent>
    </Card>
  </div>
</template>
