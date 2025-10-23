<script setup>
import FormSection from '@/Components/FormSection.vue'
import InputError from '@/Components/InputError.vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import Input from '@/Components/shadcn/ui/input/Input.vue'
import Label from '@/Components/shadcn/ui/label/Label.vue'

import { useForm } from '@inertiajs/vue3'
import { inject, ref } from 'vue'
import { toast } from 'vue-sonner'
import { __ } from '@/Composables/useTranslations.js'

const route = inject('route')

const passwordInput = ref(null)
const currentPasswordInput = ref(null)

const form = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})

function updatePassword() {
  form.put(route('user-password.update'), {
    errorBag: 'updatePassword',
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
      toast.success(__('profile.password.updated'))
    },
    onError: () => {
      if (form.errors.password) {
        form.reset('password', 'password_confirmation')
        passwordInput.value.focus()
      }

      if (form.errors.current_password) {
        form.reset('current_password')
        currentPasswordInput.value.focus()
      }
    },
  })
}
</script>

<template>
  <FormSection @submitted="updatePassword">
    <template #title>
      {{ __('profile.password.title') }}
    </template>

    <template #description>
      {{ __('profile.password.description') }}
    </template>

    <template #form>
      <div class="col-span-6 sm:col-span-4">
        <Label for="password">{{ __('profile.password.new_password') }}</Label>
        <Input
          id="password" ref="passwordInput" v-model="form.password" type="password"
          class="mt-1 block w-full" autocomplete="new-password"
        />
        <InputError :message="form.errors.password" class="mt-2" />
      </div>

      <div class="col-span-6 sm:col-span-4">
        <Label for="password_confirmation">{{ __('profile.password.confirm_password') }}</Label>
        <Input
          id="password_confirmation" v-model="form.password_confirmation" type="password"
          class="mt-1 block w-full" autocomplete="new-password"
        />
        <InputError :message="form.errors.password_confirmation" class="mt-2" />
      </div>
    </template>

    <template #actions>
      <Button :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
        {{ __('profile.password.save') }}
      </Button>
    </template>
  </FormSection>
</template>
