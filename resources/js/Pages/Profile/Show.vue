<script setup>
import { Head } from '@inertiajs/vue3'
import Separator from '@/Components/shadcn/ui/separator/Separator.vue'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/shadcn/ui/tabs'
import { Card, CardContent } from '@/Components/shadcn/ui/card'
import AppLayout from '@/Layouts/AppLayout.vue'
import DeleteUserForm from '@/Pages/Profile/Partials/DeleteUserForm.vue'
import LinkedAccountsForm from '@/Pages/Profile/Partials/LinkedAccountsForm.vue'
import LogoutOtherBrowserSessionsForm from '@/Pages/Profile/Partials/LogoutOtherBrowserSessionsForm.vue'
import TwoFactorAuthenticationForm from '@/Pages/Profile/Partials/TwoFactorAuthenticationForm.vue'
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm.vue'
import UpdateProfileInformationForm from '@/Pages/Profile/Partials/UpdateProfileInformationForm.vue'
import { User, Lock, Shield, Trash2 } from 'lucide-vue-next'

defineProps({
  confirmsTwoFactorAuthentication: {
    type: Boolean,
    default: false,
  },
  sessions: {
    type: Array,
    default: () => [],
  },
  availableOauthProviders: {
    type: Object,
    default: () => {},
  },
  activeOauthProviders: {
    type: Array,
    default: () => [],
  },
})
</script>

<template>
  <Head title="Configurações" />
  <AppLayout title="Configurações">
    <div class="p-6">
      <div class="max-w-5xl mx-auto">
        <div class="mb-6">
          <h1 class="text-3xl font-bold">
            Configurações
          </h1>
          <p class="text-muted-foreground mt-1">
            Gerencie suas informações pessoais e preferências de segurança
          </p>
        </div>

        <Card>
          <CardContent class="p-6">
            <Tabs default-value="profile" class="w-full">
              <TabsList class="grid w-full grid-cols-2 lg:grid-cols-4 mb-6">
                <TabsTrigger v-if="$page.props.jetstream.canUpdateProfileInformation" value="profile" class="flex items-center gap-2">
                  <User class="h-4 w-4" />
                  <span class="hidden sm:inline">Perfil</span>
                </TabsTrigger>
                <TabsTrigger v-if="$page.props.jetstream.canUpdatePassword" value="password" class="flex items-center gap-2">
                  <Lock class="h-4 w-4" />
                  <span class="hidden sm:inline">Senha</span>
                </TabsTrigger>
                <TabsTrigger v-if="$page.props.jetstream.canManageTwoFactorAuthentication" value="security" class="flex items-center gap-2">
                  <Shield class="h-4 w-4" />
                  <span class="hidden sm:inline">Segurança</span>
                </TabsTrigger>
                <TabsTrigger v-if="$page.props.jetstream.hasAccountDeletionFeatures" value="danger" class="flex items-center gap-2">
                  <Trash2 class="h-4 w-4" />
                  <span class="hidden sm:inline">Excluir Conta</span>
                </TabsTrigger>
              </TabsList>

              <TabsContent v-if="$page.props.jetstream.canUpdateProfileInformation" value="profile" class="space-y-6">
                <UpdateProfileInformationForm :user="$page.props.auth.user" />
              </TabsContent>

              <TabsContent v-if="$page.props.jetstream.canUpdatePassword" value="password" class="space-y-6">
                <UpdatePasswordForm />
              </TabsContent>

              <TabsContent v-if="$page.props.jetstream.canManageTwoFactorAuthentication" value="security" class="space-y-6">
                <TwoFactorAuthenticationForm :requires-confirmation="confirmsTwoFactorAuthentication" />

                <Separator class="my-6" />

                <LogoutOtherBrowserSessionsForm v-if="sessions" :sessions="sessions" />
              </TabsContent>

              <TabsContent v-if="$page.props.jetstream.hasAccountDeletionFeatures" value="danger" class="space-y-6">
                <div class="rounded-lg border border-destructive/50 bg-destructive/10 p-4 mb-4">
                  <p class="text-sm text-destructive font-medium">
                    ⚠️ Zona de Perigo
                  </p>
                  <p class="text-xs text-muted-foreground mt-1">
                    Esta ação é permanente e não pode ser desfeita
                  </p>
                </div>
                <DeleteUserForm />
              </TabsContent>
            </Tabs>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
