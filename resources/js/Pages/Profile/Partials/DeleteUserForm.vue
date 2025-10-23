<script setup>
import ActionSection from '@/Components/ActionSection.vue'
import ConfirmsPassword from '@/Components/ConfirmsPassword.vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import { useForm } from '@inertiajs/vue3'
import { inject } from 'vue'
import { __ } from '@/Composables/useTranslations.js'

const route = inject('route')
const form = useForm({})

function deleteUser(password) {
  form.transform(data => ({
    ...data,
    password,
  })).delete(route('current-user.destroy'), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
    onFinish: () => form.reset(),
  })
}
</script>

<template>
  <ActionSection>
    <template #title>
      {{ __('profile.delete_account.title') }}
    </template>

    <template #description>
      {{ __('profile.delete_account.description') }}
    </template>

    <template #content>
      <div class="max-w-xl text-sm text-gray-600 dark:text-gray-400">
        {{ __('profile.delete_account.warning') }}
      </div>

      <div class="mt-5">
        <ConfirmsPassword
          :title="__('profile.delete_account.confirmation.title')"
          :content="__('profile.delete_account.confirmation.content')"
          :button="__('profile.delete_account.confirmation.button')"
          @confirmed="deleteUser"
        >
          <Button variant="destructive">
            {{ __('profile.delete_account.confirmation.button') }}
          </Button>
        </ConfirmsPassword>
      </div>
    </template>
  </ActionSection>
</template>
