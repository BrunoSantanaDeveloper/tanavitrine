<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\StoreLead;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

final class LatestLeadsWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Leads Recentes')
            ->description('Últimos contatos de compradores interessados')
            ->query(
                StoreLead::query()
                    ->with(['team'])
                    ->latest()
                    ->limit(15)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('E-mail copiado!')
                    ->icon('heroicon-m-envelope'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefone')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Telefone copiado!')
                    ->icon('heroicon-m-phone')
                    ->placeholder('Não informado'),
                Tables\Columns\TextColumn::make('team.name')
                    ->label('Loja')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('message')
                    ->label('Mensagem')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    })
                    ->placeholder('Sem mensagem'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($state) => $state->format('d/m/Y H:i:s')),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
