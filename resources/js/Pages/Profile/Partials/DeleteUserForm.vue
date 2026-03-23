<script setup>
import ActionSection from '@/Components/ActionSection.vue'
import ConfirmsPassword from '@/Components/ConfirmsPassword.vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import { useForm, usePage, Link } from '@inertiajs/vue3'
import { computed, inject } from 'vue'
import { __ } from '@/Composables/useTranslations.js'

const route = inject('route')
const page = usePage()
const form = useForm({})
const accountDeletionGuard = computed(() => page.props.accountDeletionGuard || {})
const canDeleteAccount = computed(() => accountDeletionGuard.value.can_delete_account === true)
const deletionBlockMessage = computed(() => {
  if (canDeleteAccount.value) {
    return null
  }

  return accountDeletionGuard.value.message || 'Para excluir sua conta, primeiro cancele o plano em "Planos e Cobrança".'
})

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
        <div v-if="!canDeleteAccount" class="rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">
          {{ deletionBlockMessage }}
          <div class="mt-3">
            <Button :as="Link" :href="route('subscriptions.create')" variant="outline" size="sm">
              Ir para Planos e Cobrança
            </Button>
          </div>
        </div>

        <p v-if="form.errors.delete_account" class="mt-3 text-sm text-destructive">
          {{ form.errors.delete_account }}
        </p>

        <ConfirmsPassword
          v-if="canDeleteAccount"
          :title="__('profile.delete_account.confirmation.title')"
          :content="__('profile.delete_account.confirmation.content')"
          :button="__('profile.delete_account.confirmation.button')"
          @confirmed="deleteUser"
        >
          <Button variant="destructive">
            {{ __('profile.delete_account.confirmation.button') }}
          </Button>
        </ConfirmsPassword>

        <Button v-else variant="destructive" class="mt-3 opacity-60" disabled>
          {{ __('profile.delete_account.confirmation.button') }}
        </Button>
      </div>
    </template>
  </ActionSection>
</template>
