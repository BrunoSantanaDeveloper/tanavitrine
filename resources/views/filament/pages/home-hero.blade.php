<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex flex-wrap items-center gap-4">
            <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="save">
                Salvar hero
            </x-filament::button>
            <x-filament::link :href="url('/')" target="_blank" rel="noopener noreferrer">
                Abrir página inicial
            </x-filament::link>
        </div>
        <p class="text-sm text-gray-500">As alterações aparecem na página inicial após salvar e atualizar a página.</p>
    </form>
</x-filament-panels::page>
