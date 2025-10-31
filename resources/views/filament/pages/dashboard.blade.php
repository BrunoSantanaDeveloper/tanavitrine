<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Welcome Message --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="p-6">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10">
                        <x-filament::icon
                            icon="heroicon-o-building-storefront"
                            class="h-6 w-6 text-primary"
                        />
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-950 dark:text-white">
                            Bem-vindo ao TanaVitrine Admin
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Gerencie lojas, usuários e monitore o desempenho da plataforma
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Widgets --}}
        <x-filament-widgets::widgets
            :widgets="$this->getWidgets()"
            :columns="$this->getColumns()"
        />
    </div>
</x-filament-panels::page>
