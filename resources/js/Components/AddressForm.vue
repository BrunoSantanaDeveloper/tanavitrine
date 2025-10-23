<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'
import { Icon } from '@iconify/vue'
import Button from '@/Components/shadcn/ui/button/Button.vue'
import Input from '@/Components/shadcn/ui/input/Input.vue'
import Label from '@/Components/shadcn/ui/label/Label.vue'

const address = defineModel({
  default: () => ({
    cep: '',
    street: '',
    number: '',
    complement: '',
    neighborhood: '',
    city: '',
    state: '',
  })
})

const isLoadingCEP = ref(false)
const cepError = ref('')

async function searchCEP() {
  if (!address.value.cep || address.value.cep.replace(/\D/g, '').length < 8) {
    cepError.value = 'CEP inválido'
    return
  }

  isLoadingCEP.value = true
  cepError.value = ''

  try {
    const cep = address.value.cep.replace(/\D/g, '')
    const response = await axios.get(`https://viacep.com.br/ws/${cep}/json/`)

    if (response.data.erro) {
      cepError.value = 'CEP não encontrado'
    } else {
      address.value = {
        ...address.value,
        street: response.data.logradouro || '',
        neighborhood: response.data.bairro || '',
        city: response.data.localidade || '',
        state: response.data.uf || '',
      }
    }
  } catch (error) {
    console.error('Erro ao buscar CEP:', error)
    cepError.value = 'Erro ao buscar CEP. Digite manualmente.'
  } finally {
    isLoadingCEP.value = false
  }
}

// Auto-search quando CEP for completado
watch(() => address.value.cep, (newCEP) => {
  cepError.value = ''
  if (newCEP && newCEP.replace(/\D/g, '').length === 8) {
    searchCEP()
  }
})

// Formatar CEP automaticamente
function formatCEP(value) {
  const numbers = value.replace(/\D/g, '')
  if (numbers.length <= 5) return numbers
  return `${numbers.slice(0, 5)}-${numbers.slice(5, 8)}`
}

function handleCEPInput(e) {
  address.value.cep = formatCEP(e.target.value)
}
</script>

<template>
  <div class="space-y-4">
    <div>
      <Label for="cep">CEP *</Label>
      <div class="flex gap-2 mt-1">
        <Input
          id="cep"
          v-model="address.cep"
          placeholder="00000-000"
          maxlength="9"
          @input="handleCEPInput"
        />
        <Button
          type="button"
          variant="outline"
          size="icon"
          @click="searchCEP"
          :disabled="isLoadingCEP"
        >
          <Icon v-if="isLoadingCEP" icon="lucide:loader-2" class="h-4 w-4 animate-spin" />
          <Icon v-else icon="lucide:search" class="h-4 w-4" />
        </Button>
      </div>
      <p v-if="cepError" class="text-xs text-destructive mt-1">{{ cepError }}</p>
    </div>

    <div class="grid grid-cols-4 gap-4">
      <div class="col-span-3">
        <Label for="street">Rua *</Label>
        <Input
          id="street"
          v-model="address.street"
          placeholder="Nome da rua"
          class="mt-1"
        />
      </div>
      <div>
        <Label for="number">Número *</Label>
        <Input
          id="number"
          v-model="address.number"
          placeholder="123"
          class="mt-1"
        />
      </div>
    </div>

    <div>
      <Label for="complement">Complemento</Label>
      <Input
        id="complement"
        v-model="address.complement"
        placeholder="Sala, Andar, etc (opcional)"
        class="mt-1"
      />
    </div>

    <div class="grid grid-cols-2 gap-4">
      <div>
        <Label for="neighborhood">Bairro *</Label>
        <Input
          id="neighborhood"
          v-model="address.neighborhood"
          placeholder="Nome do bairro"
          class="mt-1"
        />
      </div>
      <div>
        <Label for="city">Cidade *</Label>
        <Input
          id="city"
          v-model="address.city"
          placeholder="Nome da cidade"
          class="mt-1"
        />
      </div>
    </div>

    <div>
      <Label for="state">Estado *</Label>
      <Input
        id="state"
        v-model="address.state"
        placeholder="UF"
        maxlength="2"
        class="mt-1 uppercase"
        @input="e => address.state = e.target.value.toUpperCase()"
      />
    </div>
  </div>
</template>
