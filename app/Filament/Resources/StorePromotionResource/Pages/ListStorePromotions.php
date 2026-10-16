<?php

declare(strict_types=1);

namespace App\Filament\Resources\StorePromotionResource\Pages;

use App\Filament\Resources\StorePromotionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

final class ListStorePromotions extends ListRecords
{
    protected static string $resource = StorePromotionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Novo destaque semanal')];
    }

    protected function paginateTableQuery(Builder $query): Paginator|CursorPaginator
    {
        return $query->simplePaginate(
            perPage: (int) $this->getTableRecordsPerPage(),
            columns: ['*'],
            pageName: $this->getTablePaginationPageName(),
        );
    }
}
