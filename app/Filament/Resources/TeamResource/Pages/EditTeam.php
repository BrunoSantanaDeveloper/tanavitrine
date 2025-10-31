<?php

declare(strict_types=1);

namespace App\Filament\Resources\TeamResource\Pages;

use App\Filament\Resources\TeamResource;
use App\Models\Team;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

final class EditTeam extends EditRecord
{
    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view')
                ->label('Ver Loja')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->url(fn (Team $record): string => route('store.show', $record->slug))
                ->openUrlInNewTab(),
            Actions\Action::make('toggle_featured')
                ->label(fn (Team $record): string => $record->featured ? 'Remover Destaque' : 'Destacar Loja')
                ->icon('heroicon-o-star')
                ->color(fn (Team $record): string => $record->featured ? 'warning' : 'gray')
                ->requiresConfirmation()
                ->action(function (Team $record) {
                    $record->update([
                        'featured' => !$record->featured,
                        'featured_until' => !$record->featured ? now()->addDays(30) : null,
                    ]);

                    Notification::make()
                        ->success()
                        ->title($record->featured ? 'Loja destacada!' : 'Destaque removido')
                        ->body($record->featured
                            ? 'A loja agora aparecerá em destaque por 30 dias.'
                            : 'A loja não aparecerá mais em destaque.')
                        ->send();
                })
                ->visible(fn (Team $record): bool => $record->status === 'ativo'),
            Actions\Action::make('toggle_status')
                ->label(fn (Team $record): string => $record->status === 'ativo' ? 'Desativar' : 'Ativar')
                ->icon(fn (Team $record): string => $record->status === 'ativo' ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->color(fn (Team $record): string => $record->status === 'ativo' ? 'danger' : 'success')
                ->requiresConfirmation()
                ->action(function (Team $record) {
                    $newStatus = $record->status === 'ativo' ? 'inativo' : 'ativo';
                    $record->update(['status' => $newStatus]);

                    Notification::make()
                        ->success()
                        ->title('Status atualizado!')
                        ->body("Loja {$record->name} está agora {$newStatus}.")
                        ->send();
                }),
            Actions\DeleteAction::make()
                ->label('Excluir')
                ->requiresConfirmation()
                ->modalHeading('Excluir Loja')
                ->modalDescription('Tem certeza que deseja excluir esta loja? Esta ação não pode ser desfeita.')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Loja excluída')
                        ->body('A loja foi removida permanentemente.')
                ),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Loja atualizada!')
            ->body('As alterações foram salvas com sucesso.')
            ->duration(3000);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Se a loja foi marcada como não-destaque, limpar a data de destaque
        if (isset($data['featured']) && !$data['featured']) {
            $data['featured_until'] = null;
        }

        return $data;
    }
}
