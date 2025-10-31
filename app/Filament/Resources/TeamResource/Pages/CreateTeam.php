<?php

declare(strict_types=1);

namespace App\Filament\Resources\TeamResource\Pages;

use App\Filament\Resources\TeamResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

final class CreateTeam extends CreateRecord
{
    protected static string $resource = TeamResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Loja criada com sucesso!')
            ->body('A loja foi cadastrada. Agora você pode adicionar fotos e gerenciar os dados.')
            ->duration(5000);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Garantir que o campo personal_team seja false para lojas criadas pelo admin
        $data['personal_team'] = false;

        // Se user_id não foi fornecido, usar o usuário logado
        if (empty($data['user_id'])) {
            $data['user_id'] = auth()->id();
        }

        // Garantir que featured seja false se não foi definido
        if (!isset($data['featured'])) {
            $data['featured'] = false;
        }

        // Garantir que status tenha um valor default
        if (empty($data['status'])) {
            $data['status'] = 'ativo';
        }

        return $data;
    }
}
