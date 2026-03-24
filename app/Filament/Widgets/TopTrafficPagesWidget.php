<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\TrafficEvent;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

final class TopTrafficPagesWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Top Páginas por Tráfego')
            ->description('Últimos 7 dias: visualizações, visitantes únicos e IPs únicos')
            ->query(
                TrafficEvent::query()
                    ->selectRaw('MIN(id) as id, path, page_type, COUNT(*) as views, COUNT(DISTINCT visitor_id) as unique_visitors, COUNT(DISTINCT ip_hash) as unique_ips')
                    ->where('occurred_at', '>=', now()->subDays(7))
                    ->groupBy('path', 'page_type')
                    ->orderByDesc('views')
                    ->limit(15)
            )
            ->columns([
                Tables\Columns\TextColumn::make('path')
                    ->label('Página')
                    ->searchable()
                    ->limit(60)
                    ->tooltip(fn (TrafficEvent $record): string => (string) $record->path)
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('page_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'home' => 'Home',
                        'atacado' => 'Atacado',
                        'varejo' => 'Varejo',
                        'prices' => 'Preços',
                        'about' => 'Sobre',
                        'store' => 'Loja',
                        default => 'Outro',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'home' => 'info',
                        'store' => 'success',
                        'atacado', 'varejo' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('views')
                    ->label('Views')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('unique_visitors')
                    ->label('Únicos')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('unique_ips')
                    ->label('IPs Únicos')
                    ->numeric()
                    ->sortable(),
            ])
            ->paginated(false)
            ->defaultSort('views', 'desc');
    }
}
