---
name: filament-create-widget
description: "Create Filament dashboard widgets for TanaVitrine admin. Use when adding stats cards, charts, or data tables to the admin dashboard. Includes patterns for stats overview, line/bar/pie charts, and table widgets."
---

# Create Filament Dashboard Widget

## Instructions

1. **Choose Widget Type**:
   - `StatsOverviewWidget` - Multiple stat cards with trends
   - `ChartWidget` - Line, bar, pie, doughnut charts
   - `TableWidget` - Data tables with filters/actions

2. **Generate Widget**:

   ```bash
   ./vendor/bin/sail artisan make:filament-widget [WidgetName]
   ```

   Options:
   - `--stats-overview` - Create stats card widget
   - `--chart` - Create chart widget
   - `--table` - Create table widget
   - `--resource=[Resource]` - Attach to specific resource

3. **Configure Widget**:
   - Set `$sort` for widget ordering
   - Set `$columnSpan` for responsive layout
   - Add to Dashboard in `AdminPanelProvider`

4. **Important Type Casting**:
   - Always cast database counts to `(int)` before using in widgets
   - Use `CarbonInterface` instead of `Carbon` for date type hints
   - This prevents type errors with `Number::format()` and Carbon instances

## Widget Types

### Stats Overview Widget

Display multiple stat cards with trends and comparisons:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Team;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

final class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        // IMPORTANT: Always cast to (int) to prevent type errors
        $total = (int) Team::count();
        $thisMonth = (int) Team::whereMonth('created_at', now()->month)->count();
        $lastMonth = (int) Team::whereMonth('created_at', now()->subMonth()->month)->count();

        $growth = $lastMonth > 0
            ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1)
            : 100;

        return [
            Stat::make('Total de Lojas', Number::format($total))
                ->description($growth >= 0 ? "+{$growth}%" : "{$growth}%")
                ->descriptionIcon($growth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart([$lastMonth, $thisMonth])
                ->color($growth >= 0 ? 'success' : 'danger'),
        ];
    }
}
```

### Chart Widget (Line Chart with Filters)

Create interactive charts with data visualization:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Team;
use Filament\Widgets\ChartWidget;
use Carbon\CarbonInterface; // IMPORTANT: Use CarbonInterface, not Carbon

final class GrowthChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Crescimento nos Últimos 6 Meses';

    protected static ?int $sort = 2;

    protected static ?string $maxHeight = '300px';

    // Responsive column span
    protected int | string | array $columnSpan = [
        'default' => 'full',
        'sm' => 'full',
        'md' => 'full',
        'lg' => 1,      // Half width on desktop
        'xl' => 1,
        '2xl' => 1,
    ];

    public ?string $filter = 'stores';

    protected function getData(): array
    {
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $months->push(now()->subMonths($i));
        }

        // IMPORTANT: Use CarbonInterface for type hints
        $monthLabels = $months->map(function (CarbonInterface $date) {
            return $date->format('M/Y');
        })->toArray();

        $data = $months->map(function (CarbonInterface $date) {
            // IMPORTANT: Cast to (int)
            return (int) Team::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
        })->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Novas Lojas',
                    'data' => $data,
                    'borderColor' => 'rgb(251, 191, 36)',
                    'backgroundColor' => 'rgba(251, 191, 36, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $monthLabels,
        ];
    }

    protected function getType(): string
    {
        return 'line'; // Options: line, bar, pie, doughnut, radar, polarArea
    }

    protected function getFilters(): ?array
    {
        return [
            'stores' => 'Lojas',
            'users' => 'Usuários',
        ];
    }
}
```

### Doughnut Chart Widget

```php
<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Team;
use Filament\Widgets\ChartWidget;

final class StoreTypeDistributionWidget extends ChartWidget
{
    protected static ?string $heading = 'Distribuição por Tipo de Venda';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = [
        'default' => 'full',
        'lg' => 1,  // Half width on desktop
    ];

    protected function getData(): array
    {
        // IMPORTANT: Cast to (int)
        $atacado = (int) Team::where('sale_type', 'atacado')->count();
        $varejo = (int) Team::where('sale_type', 'varejo')->count();
        $ambos = (int) Team::where('sale_type', 'ambos')->count();

        return [
            'datasets' => [
                [
                    'label' => 'Lojas',
                    'data' => [$atacado, $varejo, $ambos],
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(251, 191, 36, 0.8)',
                    ],
                ],
            ],
            'labels' => ['Atacado', 'Varejo', 'Ambos'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
```

### Table Widget

Display data tables with actions:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Team;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

final class LatestStoresWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Lojas Recentes')
            ->query(
                Team::query()
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criada em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ]);
    }
}
```

## Dashboard Configuration

### Custom Dashboard Page

Create custom dashboard in `app/Filament/Pages/Dashboard.php`:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

final class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Painel Administrativo';

    protected static string $view = 'filament.pages.dashboard';

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\StatsOverviewWidget::class,
            \App\Filament\Widgets\GrowthChartWidget::class,
            \App\Filament\Widgets\StoreTypeDistributionWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return [
            'default' => 1,
            'sm' => 1,
            'md' => 1,
            'lg' => 2,      // 2 columns on desktop
            'xl' => 2,
            '2xl' => 2,
        ];
    }
}
```

### Register in AdminPanelProvider

Add widgets to `app/Providers/Filament/AdminPanelProvider.php`:

```php
->pages([
    \App\Filament\Pages\Dashboard::class,
])
->widgets([
    \App\Filament\Widgets\StatsOverviewWidget::class,
    \App\Filament\Widgets\GrowthChartWidget::class,
    \App\Filament\Widgets\StoreTypeDistributionWidget::class,
])
```

## Layout & Responsive Design

### Column Span Options

```php
protected int | string | array $columnSpan = 'full';  // Full width
protected int | string | array $columnSpan = 1;       // 1 column
protected int | string | array $columnSpan = 2;       // 2 columns

// Responsive
protected int | string | array $columnSpan = [
    'default' => 'full',   // Mobile
    'sm' => 'full',        // Small tablets
    'md' => 'full',        // Tablets
    'lg' => 1,             // Desktop (half)
    'xl' => 1,             // Large desktop
    '2xl' => 1,            // Extra large
];
```

### Widget Ordering

Control order with `$sort` property:

```php
protected static ?int $sort = 1;  // Higher = lower position
```

## Common Patterns

### Percentage Growth Calculation

```php
$growth = $lastMonth > 0
    ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1)
    : 100;
```

### Color by Condition

```php
->color($growth >= 0 ? 'success' : 'danger')
```

### Chart Tension for Smooth Lines

```php
'tension' => 0.4,  // 0 = straight lines, 1 = very curved
```

## Important Notes

⚠️ **Type Casting**: Always cast database results to `(int)` before passing to `Number::format()` or chart data

⚠️ **Carbon Type**: Use `Carbon\CarbonInterface` instead of `Illuminate\Support\Carbon` for type hints to support both Carbon and CarbonImmutable

⚠️ **Column Span**: Set columnSpan responsively to ensure proper layout on mobile and desktop

## Related Files

- Dashboard Page: [app/Filament/Pages/Dashboard.php](app/Filament/Pages/Dashboard.php)
- Dashboard View: [resources/views/filament/pages/dashboard.blade.php](resources/views/filament/pages/dashboard.blade.php)
- Panel Provider: [app/Providers/Filament/AdminPanelProvider.php](app/Providers/Filament/AdminPanelProvider.php)
- Example Widgets:
  - Stats: [app/Filament/Widgets/StatsOverviewWidget.php](app/Filament/Widgets/StatsOverviewWidget.php)
  - Chart: [app/Filament/Widgets/GrowthChartWidget.php](app/Filament/Widgets/GrowthChartWidget.php)
