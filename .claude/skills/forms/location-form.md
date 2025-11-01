---
name: location-form
description: "Implement location form with automatic CEP lookup (ViaCEP) and geocoding (Nominatim). Use when creating or editing forms that need address and GPS coordinates."
---

# Location Form Pattern

## Overview

Standard pattern for TanaVitrine location forms with:
- ✅ Automatic CEP lookup via ViaCEP API
- ✅ Automatic geocoding via Nominatim (OpenStreetMap)
- ✅ Visual feedback (loading, success, error states)
- ✅ Field auto-fill (street, city, state)
- ✅ GPS coordinates (latitude, longitude)

## Implementation: Vue Components

### 1. Required Imports

```vue
<script setup>
import { ref } from 'vue'
import { formatCEP } from '@/utils/formatters'
import { Icon } from '@iconify/vue'
import axios from 'axios'
</script>
```

### 2. State Management

```vue
// CEP lookup state
const isCepLoading = ref(false)
const cepError = ref(null)
const cepSuccess = ref(false)

// Geocoding state
const isGeocodingLoading = ref(false)
const geocodingError = ref(null)
const geocodingSuccess = ref(false)
```

### 3. CEP Input Handler

```vue
function handleCepInput(e) {
  form.address.cep = formatCEP(e.target.value)

  // Clear previous states when CEP is being edited
  cepError.value = null
  cepSuccess.value = false
  geocodingError.value = null
  geocodingSuccess.value = false

  // Trigger CEP lookup only when 8 digits are complete
  const cepNumbers = e.target.value.replace(/\D/g, '')
  if (cepNumbers.length === 8) {
    lookupCep(cepNumbers)
  }
}
```

### 4. CEP Lookup Function

```vue
async function lookupCep(cep) {
  isCepLoading.value = true
  cepError.value = null
  cepSuccess.value = false

  try {
    const response = await axios.get(`https://viacep.com.br/ws/${cep}/json/`)

    if (response.data.erro) {
      cepError.value = 'CEP não encontrado. Verifique o número digitado.'
      return
    }

    // Fill address fields automatically
    form.address.street = response.data.logradouro || ''
    form.address.neighborhood = response.data.bairro || ''
    form.address.city = response.data.localidade || ''
    form.address.state = response.data.uf || ''

    cepSuccess.value = true

    // Trigger geocoding automatically after successful CEP lookup
    geocodeAddress()
  }
  catch (error) {
    console.error('CEP lookup error:', error)
    cepError.value = 'Erro ao buscar CEP. Tente novamente.'
  }
  finally {
    isCepLoading.value = false
  }
}
```

### 5. Geocoding Function

```vue
async function geocodeAddress() {
  const { cep, street, city, state } = form.address

  // Check if at least city and state are filled
  if (!city || !state) {
    return
  }

  isGeocodingLoading.value = true
  geocodingError.value = null
  geocodingSuccess.value = false

  try {
    // Build address query with maximum detail available
    let query = ''

    if (cep && street) {
      // Most precise: use CEP and street
      query = `${street}, ${cep}, ${city}, ${state}, Brazil`
    }
    else if (street) {
      // Use street with city and state
      query = `${street}, ${city}, ${state}, Brazil`
    }
    else {
      // Fallback: city and state only (less precise)
      query = `${city}, ${state}, Brazil`
    }

    // Call Nominatim API
    const response = await axios.get('https://nominatim.openstreetmap.org/search', {
      params: {
        q: query,
        format: 'json',
        limit: 1,
        countrycodes: 'br',
        addressdetails: 1,
      },
      headers: {
        'User-Agent': 'TanaVitrine/1.0',
      },
    })

    if (response.data && response.data.length > 0) {
      const location = response.data[0]

      // Save coordinates to form
      form.latitude = parseFloat(location.lat)
      form.longitude = parseFloat(location.lon)

      geocodingSuccess.value = true
    }
    else {
      geocodingError.value = 'Localização não encontrada no mapa.'
    }
  }
  catch (error) {
    console.error('Geocoding error:', error)
    geocodingError.value = 'Erro ao buscar localização no mapa.'
  }
  finally {
    isGeocodingLoading.value = false
  }
}
```

### 6. Template Structure

```vue
<template>
  <!-- CEP Field (First) -->
  <div>
    <Label for="cep">
      CEP *
      <span class="text-xs text-teal-600 font-medium">
        (Digite o CEP para preencher automaticamente)
      </span>
    </Label>
    <Input
      id="cep"
      v-model="form.address.cep"
      placeholder="00000-000"
      maxlength="9"
      required
      @input="handleCepInput"
    />

    <!-- CEP Lookup Feedback -->
    <div class="mt-2">
      <!-- Loading -->
      <div v-if="isCepLoading" class="flex items-center gap-2 text-xs text-blue-600">
        <Icon icon="lucide:loader-2" class="h-3 w-3 animate-spin" />
        <span>Buscando endereço...</span>
      </div>

      <!-- Success -->
      <div v-else-if="cepSuccess" class="flex items-center gap-2 text-xs text-green-600">
        <Icon icon="lucide:check-circle" class="h-3 w-3" />
        <span>Endereço encontrado!</span>
      </div>

      <!-- Error -->
      <div v-else-if="cepError" class="flex items-center gap-2 text-xs text-red-600">
        <Icon icon="lucide:alert-circle" class="h-3 w-3" />
        <span>{{ cepError }}</span>
      </div>
    </div>
  </div>

  <!-- Street and Number -->
  <div class="grid grid-cols-3 gap-4">
    <div class="col-span-2">
      <Label for="street">Rua *</Label>
      <Input
        id="street"
        v-model="form.address.street"
        placeholder="Nome da rua"
        required
      />
    </div>
    <div>
      <Label for="number">Número</Label>
      <Input
        id="number"
        v-model="form.address.number"
        placeholder="123"
      />
    </div>
  </div>

  <!-- City and State -->
  <div class="grid grid-cols-2 gap-4">
    <div>
      <Label for="city">Cidade *</Label>
      <Input
        id="city"
        v-model="form.address.city"
        placeholder="Ex: São Paulo"
        required
      />
    </div>
    <div>
      <Label for="state">Estado *</Label>
      <Select v-model="form.address.state">
        <SelectTrigger>
          <SelectValue placeholder="UF" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem v-for="state in states" :key="state" :value="state">
            {{ state }}
          </SelectItem>
        </SelectContent>
      </Select>
    </div>
  </div>

  <!-- Geocoding Feedback -->
  <div v-if="isGeocodingLoading || geocodingSuccess || geocodingError">
    <!-- Loading -->
    <div v-if="isGeocodingLoading" class="flex items-center gap-2 text-sm text-blue-600">
      <Icon icon="lucide:loader-2" class="h-4 w-4 animate-spin" />
      <span>Localizando no mapa...</span>
    </div>

    <!-- Success -->
    <div v-else-if="geocodingSuccess" class="flex items-center gap-2 text-sm text-green-600">
      <Icon icon="lucide:map-pin" class="h-4 w-4" />
      <span>
        Sua loja aparecerá no mapa com localização
        {{ cepSuccess && form.address.cep && form.address.street ? 'precisa' : 'aproximada' }}!
      </span>
    </div>

    <!-- Error -->
    <div v-else-if="geocodingError" class="flex items-center gap-2 text-sm text-red-600">
      <Icon icon="lucide:alert-circle" class="h-4 w-4" />
      <span>{{ geocodingError }}</span>
    </div>
  </div>

  <!-- Hidden fields for lat/lng -->
  <input v-model="form.latitude" type="hidden">
  <input v-model="form.longitude" type="hidden">
</template>
```

### 7. Form Structure

```javascript
const form = useForm({
  address: {
    cep: '',
    street: '',
    number: '',
    complement: '',
    neighborhood: '',
    city: '',
    state: '',
  },
  latitude: null,
  longitude: null,
})
```

## Implementation: Filament Admin

### Field Configuration

```php
use Illuminate\Support\Facades\Http;

Forms\Components\Section::make('Endereço')
    ->description('Preencha o CEP para carregar automaticamente o endereço.')
    ->schema([
        Forms\Components\TextInput::make('zip_code')
            ->label('CEP')
            ->required()
            ->maxLength(9)
            ->placeholder('00000-000')
            ->mask('99999-999')
            ->live(onBlur: true)
            ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                if (!$state || strlen(str_replace('-', '', $state)) !== 8) {
                    return;
                }

                $cep = preg_replace('/\D/', '', $state);

                try {
                    $response = Http::timeout(5)->get("https://viacep.com.br/ws/{cep}/json/");

                    if ($response->successful() && $data = $response->json()) {
                        if (!isset($data['erro'])) {
                            if ($data['logradouro'] ?? false) {
                                $set('address', $data['logradouro']);
                            }
                            if ($data['localidade'] ?? false) {
                                $set('city', $data['localidade']);
                            }
                            if ($data['uf'] ?? false) {
                                $set('state', $data['uf']);
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('CEP encontrado!')
                                ->body('Endereço preenchido automaticamente.')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('CEP não encontrado')
                                ->body('Verifique o número digitado.')
                                ->warning()
                                ->send();
                        }
                    }
                } catch (\Exception $e) {
                    \Filament\Notifications\Notification::make()
                        ->title('Erro ao buscar CEP')
                        ->body('Por favor, preencha manualmente.')
                        ->danger()
                        ->send();
                }
            })
            ->helperText('Digite o CEP completo para buscar automaticamente'),

        Forms\Components\TextInput::make('address')
            ->label('Endereço (Rua, Nº)')
            ->required()
            ->maxLength(255)
            ->columnSpanFull(),

        Forms\Components\TextInput::make('city')
            ->label('Cidade')
            ->required()
            ->maxLength(100),

        Forms\Components\TextInput::make('state')
            ->label('Estado (UF)')
            ->required()
            ->maxLength(2)
            ->length(2),

        Forms\Components\TextInput::make('latitude')
            ->numeric()
            ->label('Latitude')
            ->helperText('Preenchido automaticamente ao salvar'),

        Forms\Components\TextInput::make('longitude')
            ->numeric()
            ->label('Longitude')
            ->helperText('Preenchido automaticamente ao salvar'),
    ])
    ->columns(2),
```

## Backend: Model Observer for Geocoding

Create `app/Observers/TeamObserver.php`:

```php
<?php

namespace App\Observers;

use App\Models\Team;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TeamObserver
{
    public function saving(Team $team): void
    {
        if ($this->shouldGeocode($team)) {
            $this->geocodeAddress($team);
        }
    }

    private function shouldGeocode(Team $team): bool
    {
        if (empty($team->city) || empty($team->state)) {
            return false;
        }

        if ($team->latitude && $team->longitude && !$team->isDirty(['address', 'city', 'state', 'zip_code'])) {
            return false;
        }

        return true;
    }

    private function geocodeAddress(Team $team): void
    {
        try {
            $query = $this->buildQuery($team);

            $response = Http::timeout(5)
                ->withHeaders(['User-Agent' => 'TanaVitrine/1.0'])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'br',
                    'addressdetails' => 1,
                ]);

            if ($response->successful() && $data = $response->json()) {
                if (isset($data[0]['lat']) && isset($data[0]['lon'])) {
                    $team->latitude = (float) $data[0]['lat'];
                    $team->longitude = (float) $data[0]['lon'];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Geocoding failed', ['team_id' => $team->id, 'error' => $e->getMessage()]);
        }
    }

    private function buildQuery(Team $team): string
    {
        $parts = [];
        if (!empty($team->address)) $parts[] = $team->address;
        if (!empty($team->zip_code)) $parts[] = $team->zip_code;
        if (!empty($team->city)) $parts[] = $team->city;
        if (!empty($team->state)) $parts[] = $team->state;
        $parts[] = 'Brazil';

        return implode(', ', $parts);
    }
}
```

Register in `app/Providers/AppServiceProvider.php`:

```php
use App\Models\Team;
use App\Observers\TeamObserver;

public function boot(): void
{
    // ... other configurations
    Team::observe(TeamObserver::class);
}
```

## Database Migration

Ensure you have these fields in your teams table:

```php
$table->string('zip_code', 9)->nullable();
$table->string('address')->nullable();
$table->string('city', 100)->nullable();
$table->string('state', 2)->nullable();
$table->decimal('latitude', 10, 8)->nullable();
$table->decimal('longitude', 11, 8)->nullable();
```

## Brazilian States

```javascript
const states = [
  'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
  'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
  'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'
]
```

## Key Features

✅ **CEP validation**: Only triggers when 8 digits complete
✅ **Auto-fill**: Street, neighborhood, city, state from ViaCEP
✅ **Geocoding**: Automatic GPS coordinates via Nominatim
✅ **State clearing**: Resets on CEP edit to allow retry
✅ **Visual feedback**: Loading, success, error states
✅ **Precision indication**: Shows "precisa" or "aproximada"
✅ **Error handling**: Graceful degradation if APIs fail
✅ **Backend geocoding**: Observer handles server-side updates

## Related Files

- [Step4Contact.vue](resources/js/Pages/Onboarding/Steps/Step4Contact.vue)
- [StoreEdit.vue](resources/js/Pages/Dashboard/StoreEdit.vue)
- [TeamResource.php](app/Filament/Resources/TeamResource.php)
- [TeamObserver.php](app/Observers/TeamObserver.php)
- [formatters.js](resources/js/utils/formatters.js)
