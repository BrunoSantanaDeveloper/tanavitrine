<x-filament-panels::page>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <p class="text-sm font-medium text-gray-950 dark:text-white">Período do relatório</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $this->periodLabel() }}</p>
            </div>

            @if ($this->hasPeriodFilter())
                <p class="max-w-2xl text-xs text-gray-500 dark:text-gray-400">
                    Os dados por período consideram visualizações detalhadas desde 23/03/2026 e cliques detalhados a partir da implantação deste relatório.
                </p>
            @endif
        </div>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
