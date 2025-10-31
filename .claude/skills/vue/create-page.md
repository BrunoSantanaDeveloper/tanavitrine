---
name: vue-create-page
description: "Create Vue 3 + Inertia.js pages for TanaVitrine using shadcn/ui components. Use when creating new pages, forms, or views with proper layouts and component structure."
---

# Create Vue Inertia Page

## Instructions

1. Create Vue component in appropriate directory:
   - Public pages: `resources/js/Pages/[PageName].vue`
   - Dashboard pages: `resources/js/Pages/Dashboard/[PageName].vue`
   - Onboarding pages: `resources/js/Pages/Onboarding/[PageName].vue`

2. Use the appropriate layout:
   - AppLayout for authenticated pages
   - GuestLayout for public pages
   - OnboardingLayout for onboarding flow

3. Follow component structure:

   ```vue
   <script setup>
   import { Head } from '@inertiajs/vue3'
   import AppLayout from '@/Layouts/AppLayout.vue'
   import { Button } from '@/Components/shadcn/ui/button'

   const props = defineProps({
     // Define props from controller
   })
   </script>

   <template>
     <Head title="Page Title" />

     <AppLayout title="Page Title">
       <!-- Page content -->
     </AppLayout>
   </template>
   ```

4. Add route in `routes/web.php`

5. Create controller method to return Inertia response

## Key Conventions

- **Components**: Use shadcn/ui from `@/Components/shadcn/`
- **Forms**: Use `useForm` from Inertia for validation
- **Formatters**: Import from `@/utils/formatters` (formatPhone, formatCPF, formatCEP)
- **Icons**: Use `@iconify/vue`
- **Styling**: TailwindCSS (mobile-first, responsive)

## shadcn/ui Components

Button, Input, Label, Textarea, Card, CardHeader, CardTitle, CardContent,
Select, SelectContent, SelectItem, SelectTrigger, SelectValue, Checkbox,
Dialog, Separator

## Examples

### Dashboard Page with Form

```vue
<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/Components/shadcn/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import { Input } from '@/Components/shadcn/ui/input'
import { Label } from '@/Components/shadcn/ui/label'

const props = defineProps({
  store: Object,
})

const form = useForm({
  name: props.store.name,
  description: props.store.description,
})

function submit() {
  form.put(route('stores.update', props.store.slug))
}
</script>

<template>
  <Head title="Editar Loja" />

  <AppLayout title="Editar Loja">
    <div class="max-w-4xl mx-auto p-6">
      <Card>
        <CardHeader>
          <CardTitle>Informações da Loja</CardTitle>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <Label for="name">Nome</Label>
              <Input id="name" v-model="form.name" />
            </div>

            <Button type="submit" :disabled="form.processing">
              Salvar
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
```
