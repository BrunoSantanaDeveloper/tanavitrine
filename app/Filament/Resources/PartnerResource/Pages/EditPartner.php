<?php

declare(strict_types=1);

namespace App\Filament\Resources\PartnerResource\Pages;

use App\Filament\Resources\PartnerResource;
use App\Models\Coupon;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditPartner extends EditRecord
{
    protected static string $resource = PartnerResource::class;

    /** @var array<int, int|string> */
    private array $couponIds = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['coupon_ids'] = $this->record->coupons()->pluck('coupons.id')->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->couponIds = array_values($data['coupon_ids'] ?? []);
        unset($data['coupon_ids']);

        $data['is_partner'] = true;
        $data['is_superadmin'] = false;
        $data['is_partner_active'] = (bool) ($data['is_partner_active'] ?? true);

        return $data;
    }

    protected function afterSave(): void
    {
        Coupon::query()
            ->where('partner_id', $this->record->id)
            ->whereNotIn('id', $this->couponIds)
            ->update(['partner_id' => null]);

        if (empty($this->couponIds)) {
            return;
        }

        Coupon::query()
            ->whereIn('id', $this->couponIds)
            ->update(['partner_id' => $this->record->id]);
    }
}
