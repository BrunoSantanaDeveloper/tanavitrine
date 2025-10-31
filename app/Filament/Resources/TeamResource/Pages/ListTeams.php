<?php

declare(strict_types=1);

namespace App\Filament\Resources\TeamResource\Pages;

use App\Filament\Resources\TeamResource;
use App\Models\Team;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

final class ListTeams extends ListRecords
{
    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova Loja')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todas')
                ->badge(Team::count()),
            'ativo' => Tab::make('Ativas')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'ativo'))
                ->badge(Team::where('status', 'ativo')->count())
                ->badgeColor('success'),
            'inativo' => Tab::make('Inativas')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'inativo'))
                ->badge(Team::where('status', 'inativo')->count())
                ->badgeColor('danger'),
            'featured' => Tab::make('Em Destaque')
                ->modifyQueryUsing(fn (Builder $query) => $query->featured())
                ->badge(Team::featured()->count())
                ->badgeColor('warning'),
            'atacado' => Tab::make('Atacado')
                ->modifyQueryUsing(fn (Builder $query) => $query->atacado())
                ->badge(Team::atacado()->count())
                ->badgeColor('info'),
            'varejo' => Tab::make('Varejo')
                ->modifyQueryUsing(fn (Builder $query) => $query->varejo())
                ->badge(Team::varejo()->count())
                ->badgeColor('success'),
        ];
    }
}
