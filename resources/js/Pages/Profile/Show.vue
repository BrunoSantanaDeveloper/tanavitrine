<script setup>
import { Head } from '@inertiajs/vue3'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/shadcn/ui/tabs'
import { Card, CardContent } from '@/Components/shadcn/ui/card'
import { Button } from '@/Components/shadcn/ui/button'
import AppLayout from '@/Layouts/AppLayout.vue'
import DeleteUserForm from '@/Pages/Profile/Partials/DeleteUserForm.vue'
import LinkedAccountsForm from '@/Pages/Profile/Partials/LinkedAccountsForm.vue'
import LogoutOtherBrowserSessionsForm from '@/Pages/Profile/Partials/LogoutOtherBrowserSessionsForm.vue'
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm.vue'
import UpdateProfileInformationForm from '@/Pages/Profile/Partials/UpdateProfileInformationForm.vue'
import { User, Lock, Shield, Trash2, Phone, MessageCircle, Mail, Store } from 'lucide-vue-next'
import { Link, usePage } from '@inertiajs/vue3'
import { computed, inject } from 'vue'

const route = inject('route')
const page = usePage()

const defaultTab = computed(() => {
  const query = new URLSearchParams((page.url || '').split('?')[1] || '')
  const requestedTab = query.get('tab')
  const allowedTabs = ['profile', 'password', 'security', 'danger']

  if (requestedTab && allowedTabs.includes(requestedTab)) {
    return requestedTab
  }

  return 'profile'
})

const accountContact = computed(() => {
  const team = page.props.auth?.user?.current_team || {}

  return {
    email: page.props.auth?.user?.email || '-',
    phone: team.phone || '-',
    whatsapp: team.whatsapp || '-',
    storeSlug: team.slug || null,
  }
})

defineProps({
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
            Minha Conta
          </h1>
          <p class="text-muted-foreground mt-1">
            Gerencie dados do responsável pela vitrine e segurança de acesso
          </p>
        </div>

        <Card class="mb-6 border-teal-200 bg-teal-50/40">
          <CardContent class="p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
              <div class="space-y-3">
                <h2 class="text-base font-semibold text-slate-900">Dados do responsável</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                  <div class="rounded-md border border-slate-200 bg-white px-3 py-2">
                    <p class="text-xs text-slate-500">Nome</p>
                    <p class="text-sm font-semibold text-slate-900">{{ $page.props.auth.user.name }}</p>
                  </div>
                  <div class="rounded-md border border-slate-200 bg-white px-3 py-2">
                    <p class="text-xs text-slate-500 flex items-center gap-1">
                      <Mail class="h-3.5 w-3.5" />
                      E-mail
                    </p>
                    <p class="text-sm font-semibold text-slate-900">{{ accountContact.email }}</p>
                  </div>
                  <div class="rounded-md border border-slate-200 bg-white px-3 py-2">
                    <p class="text-xs text-slate-500 flex items-center gap-1">
                      <Phone class="h-3.5 w-3.5" />
                      Telefone
                    </p>
                    <p class="text-sm font-semibold text-slate-900">{{ accountContact.phone }}</p>
                  </div>
                  <div class="rounded-md border border-slate-200 bg-white px-3 py-2">
                    <p class="text-xs text-slate-500 flex items-center gap-1">
                      <MessageCircle class="h-3.5 w-3.5" />
                      WhatsApp
                    </p>
                    <p class="text-sm font-semibold text-slate-900">{{ accountContact.whatsapp }}</p>
                  </div>
                </div>
              </div>

              <div class="flex flex-col gap-2">
                <Button
                  v-if="accountContact.storeSlug"
                  :as="Link"
                  :href="route('dashboard.stores.edit', accountContact.storeSlug)"
                  variant="outline"
                  class="justify-start"
                >
                  <Store class="h-4 w-4" />
                  Editar dados da vitrine
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-6">
            <Tabs :default-value="defaultTab" class="w-full">
              <TabsList class="grid w-full grid-cols-2 lg:grid-cols-4 mb-6">
                <TabsTrigger v-if="$page.props.jetstream.canUpdateProfileInformation" value="profile" class="flex items-center gap-2">
                  <User class="h-4 w-4" />
                  <span class="hidden sm:inline">Perfil</span>
                </TabsTrigger>
                <TabsTrigger v-if="$page.props.jetstream.canUpdatePassword" value="password" class="flex items-center gap-2">
                  <Lock class="h-4 w-4" />
                  <span class="hidden sm:inline">Senha</span>
                </TabsTrigger>
                <TabsTrigger v-if="sessions" value="security" class="flex items-center gap-2">
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

              <TabsContent v-if="sessions" value="security" class="space-y-6">
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
