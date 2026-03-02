<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use Filament\Actions;
use Illuminate\Support\Arr;
use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\ListRecords;

final class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Novo usuário')
                ->mutateFormDataUsing(function (array $data): array {
                    $userType = (string) ($data['user_type'] ?? 'fornecedor');
                    $partnerActive = (bool) ($data['is_partner_active'] ?? true);

                    [$isSuperadmin, $isPartner, $isPartnerActive] = $this->resolveTypeFlags($userType, $partnerActive);

                    return array_merge(
                        Arr::except($data, ['user_type', 'is_partner_active']),
                        [
                            'is_superadmin' => $isSuperadmin,
                            'is_partner' => $isPartner,
                            'is_partner_active' => $isPartnerActive,
                        ],
                    );
                }),
        ];
    }

    /**
     * @return array{0: bool, 1: bool, 2: bool}
     */
    private function resolveTypeFlags(string $type, bool $partnerActive): array
    {
        return match ($type) {
            'admin' => [true, false, false],
            'parceiro' => [false, true, $partnerActive],
            default => [false, false, false],
        };
    }
}
