<?php

declare(strict_types=1);

namespace App\Filament\Resources\PartnerResource\Pages;

use App\Filament\Resources\PartnerResource;
use App\Models\Coupon;
use Filament\Resources\Pages\CreateRecord;

final class CreatePartner extends CreateRecord
{
    protected static string $resource = PartnerResource::class;

    /** @var array<int, int|string> */
    private array $couponIds = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->couponIds = array_values($data['coupon_ids'] ?? []);
        unset($data['coupon_ids']);

        $data['is_partner'] = true;
        $data['is_superadmin'] = false;
        $data['is_partner_active'] = (bool) ($data['is_partner_active'] ?? true);

        return $data;
    }

    protected function afterCreate(): void
    {
        if (empty($this->couponIds)) {
            return;
        }

        Coupon::query()
            ->whereIn('id', $this->couponIds)
            ->update(['partner_id' => $this->record->id]);
    }
}
