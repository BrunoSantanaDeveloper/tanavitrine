<script setup>
import InputError from '@/Components/InputError.vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import Checkbox from '@/Components/shadcn/ui/checkbox/Checkbox.vue'
import Input from '@/Components/shadcn/ui/input/Input.vue'
import Label from '@/Components/shadcn/ui/label/Label.vue'
import Sonner from '@/Components/shadcn/ui/sonner/Sonner.vue'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/shadcn/ui/tabs'
import SocialLoginButton from '@/Components/SocialLoginButton.vue'
import { useSeoMetaTags } from '@/Composables/useSeoMetaTags.js'
import { __ } from '@/Composables/useTranslations.js'
import WebLayout from '@/Layouts/WebLayout.vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { useLocalStorage } from '@vueuse/core'
import { computed, inject, onMounted } from 'vue'
import { toast } from 'vue-sonner'

const props = defineProps({
  canResetPassword: Boolean,
  status: String,
  availableOauthProviders: Object,
})

const page = usePage()
const route = inject('route')
const activeTab = useLocalStorage('login-active-tab', 'password')
const accessProfile = useLocalStorage('login-access-profile', 'supplier')

// Form state
const passwordForm = useForm({
  email: '',
  password: '',
  remember: false,
})

const loginLinkForm = useForm({
  email: '',
})

// Computed
const hasOauthProviders = computed(() =>
  Object.keys(props.availableOauthProviders || {}).length > 0,
)

const isProcessing = computed(() =>
  passwordForm.processing || loginLinkForm.processing,
)

const isSupplierProfile = computed(() => accessProfile.value === 'supplier')

// Methods
function handlePasswordLogin() {
  passwordForm
    .transform(data => ({
      ...data,
      remember: data.remember ? 'on' : '',
    }))
    .post(route('login'), {
      onFinish: () => passwordForm.reset('password'),
    })
}

function handleLoginLink() {
  loginLinkForm.post(route('login-link.store'), {
    onSuccess: () => {
      loginLinkForm.reset()
      if (page.props.flash.success) {
        toast.success(page.props.flash.success)
      }
    },
    onError: () => {
      if (page.props.flash.error) {
        toast.error(page.props.flash.error)
      }
    },
  })
}

// Lifecycle
onMounted(() => {
  if (page.props.flash.error) {
    toast.error(page.props.flash.error)
  }

  if (page.props.flash.success) {
    toast.success(page.props.flash.success)
  }
})

// SEO
useSeoMetaTags({
  title: __('login.title'),
})
</script>

<template>
  <WebLayout :can-login="true" :can-register="true">
    <Sonner position="top-center" />

    <div class="relative min-h-[calc(100vh-4rem)] overflow-hidden">
      <div class="absolute inset-0" aria-hidden="true">
        <img
          src="/images/login/tanavitrine-3840x2030.webp"
          alt=""
          class="h-full w-full object-cover object-center"
        >
      </div>
      <div class="relative z-10 flex min-h-[calc(100vh-4rem)] items-center justify-center px-3 py-4 sm:px-4 sm:py-6">
        <Card class="mx-auto w-full max-w-[420px] border border-cyan-300/35 bg-[#054d50]/72 text-cyan-50 shadow-[0_24px_55px_rgba(0,0,0,0.4)] backdrop-blur-md transition-all duration-300">
        <!-- Header -->
        <CardHeader class="px-4 pt-4 sm:px-5 sm:pt-5">
          <CardTitle class="flex justify-center">
            <img src="/tanavitrine_light_icon1.png" alt="Tanavitrine" class="h-14 w-14 sm:h-16 sm:w-16">
          </CardTitle>
          <CardDescription class="text-center text-lg font-light text-white sm:text-xl">
            {{ __('login.welcome_back') }}
          </CardDescription>

          <div class="mt-2">
            <Tabs v-model="accessProfile" class="w-full">
              <TabsList class="grid w-full grid-cols-2 rounded-xl border border-cyan-200/30 bg-[#043a3d]/65 p-1">
                <TabsTrigger value="supplier" class="h-9 rounded-lg border border-transparent text-sm font-semibold text-cyan-100/85 transition-all duration-200 data-[state=active]:border-cyan-300 data-[state=active]:bg-cyan-500 data-[state=active]:text-slate-950">
                  Fornecedor
                </TabsTrigger>
                <TabsTrigger value="buyer" class="h-9 rounded-lg border border-transparent text-sm font-semibold text-cyan-100/85 transition-all duration-200 data-[state=active]:border-cyan-300 data-[state=active]:bg-cyan-500 data-[state=active]:text-slate-950">
                  Comprador
                </TabsTrigger>
              </TabsList>
            </Tabs>
          </div>
        </CardHeader>

        <CardContent class="px-4 pb-4 sm:px-5 sm:pb-5">

          <!-- Status Message -->
          <div v-if="status" class="mb-3 rounded-lg border border-cyan-200/35 bg-cyan-400/15 px-3 py-2 text-xs font-medium text-cyan-50">
            {{ status }}
          </div>

          <!-- Login Tabs -->
          <Tabs v-model="activeTab" class="w-full">
            <TabsList class="grid w-full grid-cols-2 rounded-xl border border-cyan-200/30 bg-[#043a3d]/65 p-1">
              <TabsTrigger value="password" class="h-9 rounded-lg border border-transparent text-sm font-semibold text-cyan-100/85 transition-all duration-200 data-[state=active]:border-cyan-300 data-[state=active]:bg-cyan-500 data-[state=active]:text-slate-950">
                {{ __('login.password_tab') }}
              </TabsTrigger>
              <TabsTrigger value="login-link" class="h-9 rounded-lg border border-transparent text-sm font-semibold text-cyan-100/85 transition-all duration-200 data-[state=active]:border-cyan-300 data-[state=active]:bg-cyan-500 data-[state=active]:text-slate-950">
                {{ __('login.login_link_tab') }}
              </TabsTrigger>
            </TabsList>

            <div class="mt-4">
              <!-- Password Login -->
              <TabsContent value="password" class="space-y-3">
                <form @submit.prevent="handlePasswordLogin">
                  <div class="grid gap-3">
                    <!-- Email -->
                    <div class="grid gap-2">
                      <Label for="email" class="text-cyan-50/95">{{ __('login.email') }}</Label>
                      <Input
                        id="email"
                        v-model="passwordForm.email"
                        type="email"
                        placeholder="voce@exemplo.com.br"
                        required
                        autofocus
                        autocomplete="username"
                        class="h-10 rounded-lg border border-cyan-100/25 bg-white/96 text-sm text-teal-950 placeholder:text-slate-500 focus-visible:border-yellow-300 focus-visible:ring-1 focus-visible:ring-yellow-300/60 focus-visible:ring-offset-0"
                      />
                      <InputError :message="passwordForm.errors.email" />
                    </div>

                    <!-- Password -->
                    <div class="grid gap-2">
                      <div class="flex items-center justify-between">
                        <Label for="password" class="text-cyan-50/95">{{ __('login.password') }}</Label>
                        <Link
                          v-if="canResetPassword"
                          :href="route('password.request')"
                          class="text-xs text-cyan-100/80 hover:text-yellow-300 hover:underline underline-offset-4"
                        >
                          {{ __('login.forgot_password') }}
                        </Link>
                      </div>
                      <Input
                        id="password"
                        v-model="passwordForm.password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="h-10 rounded-lg border border-cyan-100/25 bg-white/96 text-sm text-teal-950 placeholder:text-slate-500 focus-visible:border-yellow-300 focus-visible:ring-1 focus-visible:ring-yellow-300/60 focus-visible:ring-offset-0"
                      />
                      <InputError :message="passwordForm.errors.password" />
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center space-x-2">
                      <Checkbox
                        id="remember"
                        v-model:checked="passwordForm.remember"
                        name="remember"
                      />
                      <label for="remember" class="text-xs text-cyan-100/85">
                        {{ __('login.remember_me') }}
                      </label>
                    </div>

                    <Button
                      type="submit"
                      class="h-10 w-full cursor-pointer rounded-lg bg-yellow-400 font-semibold text-teal-950 hover:bg-yellow-300"
                      :class="{ 'opacity-75': passwordForm.processing }"
                      :disabled="isProcessing"
                    >
                      {{ passwordForm.processing ? __('login.signing_in') : `Entrar como ${isSupplierProfile ? 'fornecedor' : 'comprador'}` }}
                    </Button>
                  </div>
                </form>
              </TabsContent>

              <!-- Login Link -->
              <TabsContent value="login-link" class="space-y-3">
                <div class="text-xs text-cyan-100/85">
                  {{ __('login.login_link_description') }}
                </div>
                <form @submit.prevent="handleLoginLink">
                  <div class="grid gap-3">
                    <div class="grid gap-2">
                      <Label for="login-link-email" class="text-cyan-50/95">{{ __('login.email') }}</Label>
                      <Input
                        id="login-link-email"
                        v-model="loginLinkForm.email"
                        type="email"
                        required
                        placeholder="voce@exemplo.com.br"
                        class="h-10 rounded-lg border border-cyan-100/25 bg-white/96 text-sm text-teal-950 placeholder:text-slate-500 focus-visible:border-yellow-300 focus-visible:ring-1 focus-visible:ring-yellow-300/60 focus-visible:ring-offset-0"
                      />
                      <InputError :message="loginLinkForm.errors.email" />
                    </div>

                    <Button
                      type="submit"
                      class="h-10 w-full cursor-pointer rounded-lg bg-yellow-400 font-semibold text-teal-950 hover:bg-yellow-300"
                      :class="{ 'opacity-75': loginLinkForm.processing }"
                      :disabled="isProcessing"
                    >
                      {{ loginLinkForm.processing ? __('login.sending') : __('login.send_login_link') }}
                    </Button>
                  </div>
                </form>
              </TabsContent>
            </div>
          </Tabs>

          <!-- OAuth Section -->
          <div v-if="hasOauthProviders" class="mt-6 hidden">
            <div class="relative">
              <div class="absolute inset-0 flex items-center">
                <span class="w-full border-t" />
              </div>
              <div class="relative flex justify-center text-xs uppercase">
                <span class="bg-background px-2 text-muted-foreground">
                  {{ __('login.or_continue_with') }}
                </span>
              </div>
            </div>

            <div class="mt-6 grid gap-2">
              <SocialLoginButton
                v-for="provider in availableOauthProviders"
                :key="provider.slug"
                :provider="provider"
                :disabled="isProcessing"
              />
            </div>
          </div>

          <!-- Sign Up Link -->
          <div class="mt-4 text-center text-xs text-cyan-100/85">
            {{ isSupplierProfile ? 'Ainda não anuncia na plataforma?' : __('login.dont_have_account') }}
            <Link
              :href="isSupplierProfile ? '/prices/#pricing' : route('register')"
              class="font-semibold text-yellow-300 hover:text-yellow-200 hover:underline underline-offset-4"
            >
              {{ isSupplierProfile ? 'Começar agora' : 'Criar conta de comprador' }}
            </Link>
          </div>
        </CardContent>
      </Card>
      </div>
    </div>
  </WebLayout>
</template>
