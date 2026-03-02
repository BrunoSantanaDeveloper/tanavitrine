<?php

declare(strict_types=1);

namespace App\Filament\Resources\PartnerResource\Pages;

use Filament\Actions;
use App\Filament\Resources\PartnerResource;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\PartnerResource\Widgets\PartnerLinkedStoresTable;

final class ViewPartner extends ViewRecord
{
    protected static string $resource = PartnerResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['coupon_ids'] = $this->record->coupons()->pluck('coupons.id')->all();

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PartnerLinkedStoresTable::make([
                'record' => $this->getRecord(),
            ]),
        ];
    }
}
