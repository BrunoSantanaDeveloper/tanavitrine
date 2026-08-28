<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Forms;
use App\Models\Team;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use App\Models\StoreInteraction;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\Paginator;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class Reports extends Page implements HasTable
{
    use InteractsWithTable;

    /** @var array<string, string> */
    private const METRICS = [
        'views' => 'Visualizações',
        'whatsapp' => 'Cliques no WhatsApp',
        'website' => 'Cliques no site',
        'instagram' => 'Cliques no Instagram',
        'facebook' => 'Cliques no Facebook',
        'tiktok' => 'Cliques no TikTok',
        'map' => 'Cliques em Ver localização',
    ];

    /** @var array<string, string> */
    private const CUMULATIVE_COLUMNS = [
        'views' => 'views_count',
        'whatsapp' => 'whatsapp_clicks',
        'website' => 'website_clicks',
        'instagram' => 'instagram_clicks',
        'facebook' => 'facebook_clicks',
        'tiktok' => 'tiktok_clicks',
        'map' => 'map_clicks',
    ];

    /** @var array<string, string> */
    private const INTERACTION_TYPES = [
        'whatsapp' => StoreInteraction::TYPE_WHATSAPP,
        'website' => StoreInteraction::TYPE_WEBSITE,
        'instagram' => StoreInteraction::TYPE_INSTAGRAM,
        'facebook' => StoreInteraction::TYPE_FACEBOOK,
        'tiktok' => StoreInteraction::TYPE_TIKTOK,
        'map' => StoreInteraction::TYPE_MAP,
    ];

    /** @var list<string> */
    public array $selectedMetrics = [
        'views',
        'whatsapp',
        'website',
        'instagram',
        'facebook',
        'tiktok',
        'map',
    ];

    public ?string $startDate = null;

    public ?string $endDate = null;

    public string $sortMetric = 'views';

    public string $sortDirection = 'desc';

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Relatórios';

    protected static ?string $title = 'Relatórios de lojas';

    protected static ?string $slug = 'relatorios';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.reports';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->reportQuery())
            ->columns($this->reportColumns())
            ->defaultSort('report_'.$this->sortMetric, $this->sortDirection)
            ->searchPlaceholder('Buscar loja, cidade ou UF')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->striped()
            ->emptyStateHeading('Nenhuma loja encontrada');
    }

    public function exportCsv(): StreamedResponse
    {
        $rows = $this->reportQuery()
            ->orderBy('report_'.$this->sortMetric, $this->sortDirection)
            ->orderBy('name')
            ->get();
        $metrics = $this->selectedMetrics;
        $filename = 'relatorio-lojas-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($rows, $metrics): void {
            $output = fopen('php://output', 'wb');

            if ($output === false) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, array_merge(['Código', 'Loja', 'Status', 'Cidade', 'UF'], array_map(
                fn (string $metric): string => self::METRICS[$metric],
                $metrics,
            )), ';');

            foreach ($rows as $store) {
                $row = [
                    'TV'.mb_str_pad((string) $store->id, 4, '0', STR_PAD_LEFT),
                    $this->safeCsvValue((string) $store->name),
                    (string) $store->status,
                    $this->safeCsvValue((string) ($store->city ?? '')),
                    (string) ($store->state ?? ''),
                ];

                foreach ($metrics as $metric) {
                    $row[] = (int) $store->getAttribute('report_'.$metric);
                }

                fputcsv($output, $row, ';');
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function hasPeriodFilter(): bool
    {
        return $this->startDate !== null || $this->endDate !== null;
    }

    public function periodLabel(): string
    {
        if (! $this->hasPeriodFilter()) {
            return 'Todo o histórico disponível';
        }

        $start = $this->startDate ? CarbonImmutable::parse($this->startDate)->format('d/m/Y') : 'início';
        $end = $this->endDate ? CarbonImmutable::parse($this->endDate)->format('d/m/Y') : 'hoje';

        return "{$start} até {$end}";
    }

    /** @return array<Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('configureReport')
                ->label('Configurar relatório')
                ->icon('heroicon-o-adjustments-horizontal')
                ->form([
                    Forms\Components\CheckboxList::make('metrics')
                        ->label('Métricas do relatório')
                        ->options(self::METRICS)
                        ->columns(2)
                        ->bulkToggleable()
                        ->required(),
                    Forms\Components\Section::make('Período')
                        ->description('Sem datas, o relatório usa os totais históricos acumulados.')
                        ->schema([
                            Forms\Components\DatePicker::make('start_date')
                                ->label('Data inicial')
                                ->native(false)
                                ->maxDate(fn (Forms\Get $get): ?string => $get('end_date') ?: now()->toDateString()),
                            Forms\Components\DatePicker::make('end_date')
                                ->label('Data final')
                                ->native(false)
                                ->minDate(fn (Forms\Get $get): ?string => $get('start_date'))
                                ->maxDate(now()),
                        ])
                        ->columns(2),
                    Forms\Components\Select::make('sort_metric')
                        ->label('Ordenar por')
                        ->options(self::METRICS)
                        ->required(),
                    Forms\Components\Select::make('sort_direction')
                        ->label('Ordem')
                        ->options([
                            'desc' => 'Maior para menor',
                            'asc' => 'Menor para maior',
                        ])
                        ->required(),
                ])
                ->mountUsing(function (Form $form): void {
                    $form->fill([
                        'metrics' => $this->selectedMetrics,
                        'start_date' => $this->startDate,
                        'end_date' => $this->endDate,
                        'sort_metric' => $this->sortMetric,
                        'sort_direction' => $this->sortDirection,
                    ]);
                })
                ->action(function (array $data): void {
                    $this->selectedMetrics = array_values($data['metrics']);
                    $this->startDate = $data['start_date'] ?: null;
                    $this->endDate = $data['end_date'] ?: null;
                    $this->sortMetric = $data['sort_metric'];
                    $this->sortDirection = $data['sort_direction'];
                    $this->resetTable();
                }),
            Action::make('exportCsv')
                ->label('Baixar CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn (): StreamedResponse => $this->exportCsv()),
        ];
    }

    /**
     * Fetch only the selected page from the database. A simple paginator also
     * avoids an extra COUNT(*) query and does not require PHP's intl extension.
     */
    protected function paginateTableQuery(Builder $query): Paginator|CursorPaginator
    {
        return $query->simplePaginate(
            perPage: (int) $this->getTableRecordsPerPage(),
            columns: ['*'],
            pageName: $this->getTablePaginationPageName(),
        );
    }

    private function reportQuery(): Builder
    {
        $query = Team::query()
            ->select('teams.*')
            ->where('personal_team', false)
            ->with('category');

        if (! $this->hasPeriodFilter()) {
            foreach (self::CUMULATIVE_COLUMNS as $metric => $column) {
                $query->selectRaw("{$column} as report_{$metric}");
            }

            return $query;
        }

        $query->withCount([
            'viewEvents as report_views' => fn (Builder $events): Builder => $this->applyPeriod($events, 'viewed_at'),
        ]);

        foreach (self::INTERACTION_TYPES as $metric => $type) {
            $query->withCount([
                "interactionEvents as report_{$metric}" => fn (Builder $events): Builder => $this->applyPeriod(
                    $events->where('type', $type),
                    'occurred_at',
                ),
            ]);
        }

        return $query;
    }

    /** @return array<Tables\Columns\Column> */
    private function reportColumns(): array
    {
        $columns = [
            Tables\Columns\TextColumn::make('id')
                ->label('Código')
                ->formatStateUsing(fn (int $state): string => 'TV'.mb_str_pad((string) $state, 4, '0', STR_PAD_LEFT))
                ->sortable(),
            Tables\Columns\TextColumn::make('name')
                ->label('Loja')
                ->description(fn (Team $record): string => implode(' • ', array_filter([
                    $record->category?->name,
                    trim(implode(' - ', array_filter([$record->city, $record->state]))),
                ])))
                ->searchable(['name', 'city', 'state'])
                ->weight('bold'),
            Tables\Columns\TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'ativo' => 'success',
                    'pendente' => 'warning',
                    'inativo' => 'danger',
                    default => 'gray',
                }),
        ];

        foreach ($this->selectedMetrics as $metric) {
            $columns[] = Tables\Columns\TextColumn::make('report_'.$metric)
                ->label(self::METRICS[$metric])
                ->formatStateUsing(fn (mixed $state): string => number_format((int) $state, 0, ',', '.'))
                ->sortable()
                ->alignEnd();
        }

        return $columns;
    }

    private function applyPeriod(Builder $query, string $column): Builder
    {
        if ($this->startDate !== null) {
            $query->where($column, '>=', CarbonImmutable::parse($this->startDate)->startOfDay());
        }

        if ($this->endDate !== null) {
            $query->where($column, '<=', CarbonImmutable::parse($this->endDate)->endOfDay());
        }

        return $query;
    }

    private function safeCsvValue(string $value): string
    {
        return preg_match('/^[=+\-@]/', $value) === 1 ? "'{$value}" : $value;
    }
}
