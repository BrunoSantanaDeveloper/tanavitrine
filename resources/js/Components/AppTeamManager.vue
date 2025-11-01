<script setup>
import { Avatar, AvatarFallback } from '@/Components/shadcn/ui/avatar'
import {
  Command,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandSeparator,
} from '@/Components/shadcn/ui/command'
import CommandEmpty from '@/Components/shadcn/ui/command/CommandEmpty.vue'
import CommandList from '@/Components/shadcn/ui/command/CommandList.vue'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuTrigger,
} from '@/Components/shadcn/ui/dropdown-menu'
import SidebarMenuButton from '@/Components/shadcn/ui/sidebar/SidebarMenuButton.vue'
import { Icon } from '@iconify/vue'
import { router } from '@inertiajs/vue3'
import { inject, ref } from 'vue'

const route = inject('route')
const open = ref(false)

function switchToTeam(team) {
  router.put(route('current-team.update'), {
    team_id: team.id,
  }, {
    preserveState: false,
  })
}
</script>

<template>
  <DropdownMenu v-model:open="open">
    <DropdownMenuTrigger as-child>
      <SidebarMenuButton
        size="lg"
        class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
      >
        <div class="flex aspect-square size-8 items-center justify-center rounded-lg text-primary-foreground">
          <img src="/tanavitrine_light_icon.png" alt="tanavitrine" class="w-12">
        </div>
        <div class="grid flex-1 text-left text-sm leading-tight">
          <span class="truncate font-semibold">Tanavitrine</span>
          <span class="truncate text-xs">Catalogo de Fornecedores</span>
        </div>
        <Icon icon="lucide:chevrons-up-down" class="ml-auto size-4" />
      </SidebarMenuButton>
    </DropdownMenuTrigger>
    <DropdownMenuContent
      class="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg p-0"
      align="start" side="bottom" :side-offset="4"
    >
      <Command :filter-function="(list, term) => list.filter(i => i?.name?.toLowerCase()?.includes(term))">
        <CommandList hidden="true">
          <CommandInput placeholder="Search team..." />
          <CommandEmpty>No team found.</CommandEmpty>
          <CommandGroup heading="Switch Teams">
            <CommandItem
              v-for="team in $page.props.auth.user.all_teams"
              :key="team.value" :value="team" @select="() => {
                switchToTeam(team);
                open = false;
              }"
            >
              <Avatar class="mr-2 size-5">
                <AvatarFallback>{{ team.name.charAt(0) }}</AvatarFallback>
              </Avatar>
              {{ team.name }}
              <Icon
                v-if="team.id === $page.props.auth.user.current_team_id"
                icon="lucide:check" class="ml-auto size-4"
              />
            </CommandItem>
          </CommandGroup>
        </CommandList>
        <CommandSeparator v-if="$page.props.auth.user.all_teams.length > 1" />
        <CommandGroup heading="Gerenciar Conta">
          <CommandItem
            v-if="$page.props.auth.user.current_team?.slug"
            value="team-settings"
            @select="() => {
              router.visit(route('dashboard.stores.edit', $page.props.auth.user.current_team.slug));
              open = false;
            }"
          >
            <Icon icon="lucide:settings" class="mr-2 h-4 w-4" />
            Editar Vitrine
          </CommandItem>
          <CommandItem
            value="dashboard"
            @select="() => {
              router.visit(route('dashboard'));
              open = false;
            }"
          >
            <Icon icon="lucide:layout-dashboard" class="mr-2 h-4 w-4" />
            Dashboard
          </CommandItem>
        </CommandGroup>
      </Command>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
