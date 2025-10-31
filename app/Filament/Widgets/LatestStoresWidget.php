<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\TeamResource;
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
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Proprietário')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoria')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sale_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'atacado' => 'Atacado',
                        'varejo' => 'Varejo',
                        'ambos' => 'Ambos',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'atacado' => 'info',
                        'varejo' => 'success',
                        'ambos' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('city')
                    ->label('Cidade')
                    ->searchable(),
                Tables\Columns\TextColumn::make('state')
                    ->label('UF')
                    ->searchable(),
                Tables\Columns\IconColumn::make('featured')
                    ->label('Destaque')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ativo' => 'Ativo',
                        'inativo' => 'Inativo',
                        'pendente' => 'Pendente',
                        'suspenso' => 'Suspenso',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'ativo' => 'success',
                        'inativo' => 'danger',
                        'pendente' => 'warning',
                        'suspenso' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criada em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Team $record): string => TeamResource::getUrl('edit', ['record' => $record]))
                    ->color('primary'),
            ]);
    }
}
