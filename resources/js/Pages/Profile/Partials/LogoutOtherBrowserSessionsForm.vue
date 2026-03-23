<script setup>
import ActionSection from '@/Components/ActionSection.vue'
import ConfirmsPassword from '@/Components/ConfirmsPassword.vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import { Icon } from '@iconify/vue'
import { useForm } from '@inertiajs/vue3'
import { inject } from 'vue'
import { toast } from 'vue-sonner'
import { __ } from '@/Composables/useTranslations.js'

defineProps({
  sessions: Array,
})

const route = inject('route')
const form = useForm({
  password: '',
})

function logoutOtherBrowserSessions(password) {
  form.transform(data => ({
    ...data,
    password,
  })).delete(route('other-browser-sessions.destroy'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
      toast.success(__('profile.sessions.logged_out'))
    },
    onFinish: () => form.reset(),
  })
}
</script>

<template>
  <ActionSection>
    <template #title>
      {{ __('profile.sessions.title') }}
    </template>

    <template #description>
      {{ __('profile.sessions.description') }}
    </template>

    <template #content>
      <div class="max-w-xl text-sm ">
        {{ __('profile.sessions.content') }}
      </div>

      <!-- Other Browser Sessions -->
      <div v-if="sessions.length > 0" class="mt-5 space-y-6">
        <div v-for="(session, i) in sessions" :key="i" class="flex items-center">
          <div>
            <Icon v-if="session.agent.is_desktop" icon="lucide:laptop" class="size-8" />
            <Icon v-else icon="lucide:tablet-smartphone" class="size-8" />
          </div>

          <div class="ms-3">
            <div class="text-sm">
              {{ session.agent.platform ? session.agent.platform : __('profile.sessions.unknown') }} - {{
                session.agent.browser ? session.agent.browser : __('profile.sessions.unknown')
              }}
            </div>

            <div>
              <div class="text-xs">
                {{ session.ip_address }},

                <span v-if="session.is_current_device" class="font-semibold text-green-400">
                  {{ __('profile.sessions.this_device') }}
                </span>
                <span v-else>
                  {{ __('profile.sessions.last_active', { date: session.last_active }) }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-5 flex items-center">
        <ConfirmsPassword
          :title="__('profile.sessions.confirmation.title')"
          :content="__('profile.sessions.confirmation.content')"
          :button="__('profile.sessions.confirmation.button')"
          @confirmed="logoutOtherBrowserSessions"
        >
          <Button>
            {{ __('profile.sessions.confirmation.button') }}
          </Button>
        </ConfirmsPassword>
      </div>
    </template>
  </ActionSection>
</template>
