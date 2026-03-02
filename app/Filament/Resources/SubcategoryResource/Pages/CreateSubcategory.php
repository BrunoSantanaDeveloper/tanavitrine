<?php

declare(strict_types=1);

namespace App\Filament\Resources\SubcategoryResource\Pages;

use App\Filament\Resources\SubcategoryResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateSubcategory extends CreateRecord
{
    protected static string $resource = SubcategoryResource::class;
}

