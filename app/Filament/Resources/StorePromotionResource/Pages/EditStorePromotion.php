<?php

declare(strict_types=1);

namespace App\Filament\Resources\StorePromotionResource\Pages;

use App\Filament\Resources\StorePromotionResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

final class EditStorePromotion extends EditRecord
{
    protected static string $resource = StorePromotionResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return parent::handleRecordUpdate($record, $data);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(collect($exception->errors())->mapWithKeys(fn ($messages, $field) => ["data.{$field}" => $messages])->all());
        }
    }
}
