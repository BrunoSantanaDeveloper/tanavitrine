<?php

namespace App\Filament\Resources\IntervalResource\Pages;

use App\Filament\Resources\IntervalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInterval extends EditRecord
{
    protected static string $resource = IntervalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
