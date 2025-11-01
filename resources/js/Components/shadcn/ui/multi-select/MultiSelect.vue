<script setup>
import { computed, ref } from 'vue'
import { Icon } from '@iconify/vue'
import { Badge } from '@/Components/shadcn/ui/badge'
import { Button } from '@/Components/shadcn/ui/button'
import { Checkbox } from '@/Components/shadcn/ui/checkbox'
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from '@/Components/shadcn/ui/command'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/Components/shadcn/ui/popover'
import { cn } from '@/lib/utils'

const props = defineProps({
  options: {
    type: Array,
    required: true,
  },
  modelValue: {
    type: Array,
    default: () => [],
  },
  placeholder: {
    type: String,
    default: 'Select options...',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  maxCount: {
    type: Number,
    default: null,
  },
})

const emit = defineEmits(['update:modelValue'])

const open = ref(false)

const selectedValues = computed({
  get: () => props.modelValue || [],
  set: (value) => emit('update:modelValue', value),
})

function toggleOption(optionValue) {
  const currentValues = [...selectedValues.value]
  const index = currentValues.indexOf(optionValue)

  if (index > -1) {
    currentValues.splice(index, 1)
  }
  else {
    if (props.maxCount && currentValues.length >= props.maxCount) {
      return
    }
    currentValues.push(optionValue)
  }

  selectedValues.value = currentValues
}

function removeOption(optionValue) {
  selectedValues.value = selectedValues.value.filter(v => v !== optionValue)
}

function clearAll() {
  selectedValues.value = []
}

const selectedLabels = computed(() => {
  return selectedValues.value
    .map((value) => {
      const option = props.options.find(opt => opt.value === value)
      return option?.label || value
    })
})

const buttonText = computed(() => {
  if (selectedValues.value.length === 0) {
    return props.placeholder
  }

  if (selectedValues.value.length === 1) {
    return selectedLabels.value[0]
  }

  return `${selectedValues.value.length} selecionado(s)`
})
</script>

<template>
  <Popover v-model:open="open">
    <PopoverTrigger as-child>
      <Button
        variant="outline"
        role="combobox"
        :aria-expanded="open"
        :disabled="disabled"
        class="w-full justify-between h-auto min-h-12 px-3 py-2"
      >
        <div class="flex flex-wrap gap-1 items-center flex-1">
          <template v-if="selectedValues.length === 0">
            <span class="text-muted-foreground">{{ placeholder }}</span>
          </template>
          <template v-else-if="selectedValues.length <= 3">
            <Badge
              v-for="value in selectedValues"
              :key="value"
              variant="secondary"
              class="mr-1 mb-1"
            >
              {{ props.options.find(opt => opt.value === value)?.label || value }}
              <button
                type="button"
                class="ml-1 rounded-full outline-none ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2"
                @click.stop="removeOption(value)"
              >
                <Icon icon="lucide:x" class="h-3 w-3" />
              </button>
            </Badge>
          </template>
          <template v-else>
            <Badge variant="secondary" class="mr-1 mb-1">
              {{ selectedValues.length }} selecionado(s)
            </Badge>
          </template>
        </div>
        <Icon
          icon="lucide:chevron-down"
          :class="cn('h-4 w-4 shrink-0 opacity-50 transition-transform', open && 'rotate-180')"
        />
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-full p-0" align="start">
      <Command>
        <CommandInput placeholder="Buscar..." />
        <CommandList>
          <CommandEmpty>Nenhuma opção encontrada.</CommandEmpty>
          <CommandGroup>
            <CommandItem
              v-for="option in options"
              :key="option.value"
              :value="option.value"
              @select="toggleOption(option.value)"
            >
              <div
                :class="cn(
                  'mr-2 flex h-4 w-4 items-center justify-center rounded-sm border border-primary',
                  selectedValues.includes(option.value)
                    ? 'bg-primary text-primary-foreground'
                    : 'opacity-50 [&_svg]:invisible',
                )"
              >
                <Icon icon="lucide:check" class="h-4 w-4" />
              </div>
              <span>{{ option.label }}</span>
            </CommandItem>
          </CommandGroup>
        </CommandList>
        <div
          v-if="selectedValues.length > 0"
          class="flex items-center justify-between p-2 border-t"
        >
          <span class="text-xs text-muted-foreground">
            {{ selectedValues.length }} selecionado(s)
          </span>
          <Button
            variant="ghost"
            size="sm"
            class="h-8 px-2 text-xs"
            @click="clearAll"
          >
            Limpar tudo
          </Button>
        </div>
      </Command>
    </PopoverContent>
  </Popover>
</template>
