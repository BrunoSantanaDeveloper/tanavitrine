<?php

namespace App\Filament\Resources\IntervalResource\Pages;

use App\Filament\Resources\IntervalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIntervals extends ListRecords
{
    protected static string $resource = IntervalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
