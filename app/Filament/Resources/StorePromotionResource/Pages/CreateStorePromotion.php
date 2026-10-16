<?php

declare(strict_types=1);

namespace App\Filament\Resources\StorePromotionResource\Pages;

use App\Filament\Resources\StorePromotionResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

final class CreateStorePromotion extends CreateRecord
{
    protected static string $resource = StorePromotionResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        try {
            return parent::handleRecordCreation($data);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(collect($exception->errors())->mapWithKeys(fn ($messages, $field) => ["data.{$field}" => $messages])->all());
        }
    }
}
