<script setup>
import ActionSection from '@/Components/ActionSection.vue'
import ConfirmsPassword from '@/Components/ConfirmsPassword.vue'
import InputError from '@/Components/InputError.vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import Input from '@/Components/shadcn/ui/input/Input.vue'
import Label from '@/Components/shadcn/ui/label/Label.vue'

import { router, useForm, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { computed, inject, ref, watch } from 'vue'
import { __ } from '@/Composables/useTranslations.js'

const props = defineProps({
  requiresConfirmation: Boolean,
})
const route = inject('route')
const page = usePage()
const enabling = ref(false)
const confirming = ref(false)
const disabling = ref(false)
const qrCode = ref(null)
const setupKey = ref(null)
const recoveryCodes = ref([])

const confirmationForm = useForm({
  code: '',
})

const twoFactorEnabled = computed(
  () => !enabling.value && page.props.auth.user?.two_factor_enabled,
)

watch(twoFactorEnabled, () => {
  if (!twoFactorEnabled.value) {
    confirmationForm.reset()
    confirmationForm.clearErrors()
  }
})

function enableTwoFactorAuthentication() {
  enabling.value = true

  router.post(route('two-factor.enable'), {}, {
    preserveScroll: true,
    onSuccess: () => Promise.all([
      showQrCode(),
      showSetupKey(),
      showRecoveryCodes(),
    ]),
    onFinish: () => {
      enabling.value = false
      confirming.value = props.requiresConfirmation
    },
  })
}

function showQrCode() {
  return axios.get(route('two-factor.qr-code')).then((response) => {
    qrCode.value = response.data.svg
  })
}

function showSetupKey() {
  return axios.get(route('two-factor.secret-key')).then((response) => {
    setupKey.value = response.data.secretKey
  })
}

function showRecoveryCodes() {
  return axios.get(route('two-factor.recovery-codes')).then((response) => {
    recoveryCodes.value = response.data
  })
}

function confirmTwoFactorAuthentication() {
  confirmationForm.post(route('two-factor.confirm'), {
    errorBag: 'confirmTwoFactorAuthentication',
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      confirming.value = false
      qrCode.value = null
      setupKey.value = null
    },
  })
}

function regenerateRecoveryCodes() {
  axios
    .post(route('two-factor.recovery-codes'))
    .then(() => showRecoveryCodes())
}

function disableTwoFactorAuthentication() {
  disabling.value = true

  router.delete(route('two-factor.disable'), {
    preserveScroll: true,
    onSuccess: () => {
      disabling.value = false
      confirming.value = false
    },
  })
}
</script>

<template>
  <ActionSection>
    <template #title>
      {{ __('profile.two_factor.title') }}
    </template>

    <template #description>
      {{ __('profile.two_factor.description') }}
    </template>

    <template #content>
      <h3 v-if="twoFactorEnabled && !confirming" class="text-lg font-medium">
        {{ __('profile.two_factor.enabled') }}
      </h3>

      <h3 v-else-if="twoFactorEnabled && confirming" class="text-lg font-medium">
        {{ __('profile.two_factor.finish_enabling') }}
      </h3>

      <h3 v-else class="text-lg font-medium">
        {{ __('profile.two_factor.not_enabled') }}
      </h3>

      <div class="mt-3 max-w-xl text-sm">
        <p>
          {{ __('profile.two_factor.explanation') }}
        </p>
      </div>

      <div v-if="twoFactorEnabled">
        <div v-if="qrCode">
          <div class="mt-4 max-w-xl text-sm">
            <p v-if="confirming" class="font-semibold">
              {{ __('profile.two_factor.finish_setup') }}
            </p>

            <p v-else>
              {{ __('profile.two_factor.setup_complete') }}
            </p>
          </div>

          <div class="mt-4 inline-block p-2" v-html="qrCode" />

          <div v-if="setupKey" class="mt-4 max-w-xl text-sm">
            <p class="font-semibold">
              {{ __('profile.two_factor.setup_key') }}: <span v-html="setupKey" />
            </p>
          </div>

          <div v-if="confirming" class="mt-4">
            <Label for="code">{{ __('profile.two_factor.code') }}</Label>

            <Input
              id="code"
              v-model="confirmationForm.code"
              type="text"
              name="code"
              class="mt-1 block w-1/2"
              inputmode="numeric"
              autofocus
              autocomplete="one-time-code"
              @keyup.enter="confirmTwoFactorAuthentication"
            />

            <InputError :message="confirmationForm.errors.code" class="mt-2" />
          </div>
        </div>

        <div v-if="recoveryCodes.length > 0 && !confirming">
          <div class="mt-4 max-w-xl text-sm text-gray-600 dark:text-gray-400">
            <p class="font-semibold">
              {{ __('profile.two_factor.recovery_codes.title') }}
            </p>
          </div>

          <div class="mt-4 grid max-w-xl gap-1 rounded-lg bg-gray-100 p-4 font-mono text-sm dark:bg-gray-900 dark:text-gray-100">
            <div v-for="code in recoveryCodes" :key="code">
              {{ code }}
            </div>
          </div>
        </div>
      </div>

      <div class="mt-5">
        <div v-if="!twoFactorEnabled">
          <ConfirmsPassword @confirmed="enableTwoFactorAuthentication">
            <Button type="button" :class="{ 'opacity-25': enabling }" :disabled="enabling">
              {{ __('profile.two_factor.buttons.enable') }}
            </Button>
          </ConfirmsPassword>
        </div>

        <div v-else>
          <ConfirmsPassword @confirmed="confirmTwoFactorAuthentication">
            <Button
              v-if="confirming"
              type="button"
              class="me-3"
              :class="{ 'opacity-25': enabling }"
              :disabled="enabling"
            >
              {{ __('profile.two_factor.buttons.confirm') }}
            </Button>
          </ConfirmsPassword>

          <ConfirmsPassword @confirmed="regenerateRecoveryCodes">
            <Button
              v-if="recoveryCodes.length > 0 && !confirming"
              variant="secondary"
              class="me-3"
            >
              {{ __('profile.two_factor.recovery_codes.regenerate') }}
            </Button>
          </ConfirmsPassword>

          <ConfirmsPassword @confirmed="showRecoveryCodes">
            <Button
              v-if="recoveryCodes.length === 0 && !confirming"
              variant="secondary"
              class="me-3"
            >
              {{ __('profile.two_factor.recovery_codes.show') }}
            </Button>
          </ConfirmsPassword>

          <ConfirmsPassword @confirmed="disableTwoFactorAuthentication">
            <Button
              v-if="confirming"
              variant="secondary"
              :class="{ 'opacity-25': disabling }"
              :disabled="disabling"
            >
              {{ __('profile.two_factor.buttons.cancel') }}
            </Button>
          </ConfirmsPassword>

          <ConfirmsPassword @confirmed="disableTwoFactorAuthentication">
            <Button
              v-if="!confirming"
              variant="destructive"
              :class="{ 'opacity-25': disabling }"
              :disabled="disabling"
            >
              {{ __('profile.two_factor.buttons.disable') }}
            </Button>
          </ConfirmsPassword>
        </div>
      </div>
    </template>
  </ActionSection>
</template>
