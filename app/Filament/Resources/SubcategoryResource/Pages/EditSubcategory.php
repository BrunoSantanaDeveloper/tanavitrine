<?php

declare(strict_types=1);

namespace App\Filament\Resources\SubcategoryResource\Pages;

use App\Filament\Resources\SubcategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditSubcategory extends EditRecord
{
    protected static string $resource = SubcategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

