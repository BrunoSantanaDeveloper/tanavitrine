<?php

declare(strict_types=1);

namespace App\Filament\Resources\TeamResource\Pages;

use App\Filament\Resources\TeamResource;
use App\Models\Coupon;
use App\Models\Team;
use App\Services\AdminStoreAccessService;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

final class EditTeam extends EditRecord
{
    protected static string $resource = TeamResource::class;

    private ?int $couponIdToApply = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view')
                ->label('Ver Loja')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->url(fn (Team $record): ?string => filled($record->slug)
                    ? route('store.show', $record->slug)
                    : null)
                ->visible(fn (Team $record): bool => filled($record->slug))
                ->openUrlInNewTab(),
            Actions\Action::make('toggle_featured')
                ->label(fn (Team $record): string => $record->featured ? 'Remover Destaque' : 'Destacar Loja')
                ->icon('heroicon-o-star')
                ->color(fn (Team $record): string => $record->featured ? 'warning' : 'gray')
                ->requiresConfirmation()
                ->action(function (Team $record) {
                    $record->update([
                        'featured' => !$record->featured,
                        'featured_until' => null,
                    ]);

                    Notification::make()
                        ->success()
                        ->title($record->featured ? 'Loja destacada!' : 'Destaque removido')
                        ->body($record->featured
                            ? 'A loja agora aparecerá em destaque sem prazo definido.'
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
            Actions\Action::make('grant_access_days')
                ->label('Conceder Dias')
                ->icon('heroicon-o-calendar-days')
                ->color('success')
                ->form([
                    Forms\Components\TextInput::make('days')
                        ->label('Dias de acesso')
                        ->numeric()
                        ->required()
                        ->default(30)
                        ->minValue(1)
                        ->maxValue(3650)
                        ->helperText('Ex.: 30 para 1 mês ou 90 para 3 meses.'),
                ])
                ->modalHeading('Conceder dias de acesso')
                ->modalDescription('Esta ação estende o acesso da loja e reativa a vitrine automaticamente quando estiver inativa.')
                ->modalSubmitActionLabel('Conceder dias')
                ->action(function (Team $record, array $data): void {
                    try {
                        $result = app(AdminStoreAccessService::class)->grantDays(
                            $record,
                            (int) ($data['days'] ?? 0)
                        );

                        $endsAt = $result['trial_ends_at'] ?? $result['discount_ends_at'];
                        $details = $endsAt
                            ? "Acesso válido até {$endsAt}."
                            : 'Acesso atualizado com sucesso.';

                        if ($result['store_reactivated']) {
                            $details .= ' Vitrine reativada automaticamente.';
                        }

                        Notification::make()
                            ->success()
                            ->title('Dias concedidos com sucesso!')
                            ->body($details)
                            ->send();
                    } catch (\Throwable $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Não foi possível conceder dias')
                            ->body($exception->getMessage())
                            ->send();
                    }
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
        if (isset($data['subscription_coupon_id']) && $data['subscription_coupon_id'] !== '') {
            $this->couponIdToApply = (int) $data['subscription_coupon_id'];
        }

        unset($data['subscription_coupon_id']);

        // Se a loja foi marcada como não-destaque, limpar a data de destaque
        if (isset($data['featured']) && !$data['featured']) {
            $data['featured_until'] = null;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->couponIdToApply === null) {
            return;
        }

        /** @var Team $store */
        $store = $this->record->fresh(['owner.subscriptions.coupon', 'plan.intervals']);
        $subscription = $store->owner?->subscription('default');

        if (!$subscription) {
            Notification::make()
                ->warning()
                ->title('Assinatura não encontrada')
                ->body('Esta loja não possui assinatura para trocar cupom.')
                ->send();

            return;
        }

        if ((int) $subscription->coupon_id === $this->couponIdToApply) {
            return;
        }

        $coupon = Coupon::find($this->couponIdToApply);

        if (!$coupon || !$coupon->isValid()) {
            Notification::make()
                ->danger()
                ->title('Cupom inválido')
                ->body('Selecione um cupom ativo e válido.')
                ->send();

            return;
        }

        $plan = $store->plan;
        $price = 0.0;

        if ($plan && $plan->intervals->isNotEmpty()) {
            $price = (float) ($plan->intervals->first()->pivot->price ?? 0);
        }

        if ($price <= 0) {
            $price = (float) ($subscription->original_price ?? 0);
        }

        if ($price <= 0) {
            Notification::make()
                ->danger()
                ->title('Preço base não encontrado')
                ->body('Não foi possível calcular o desconto para este cupom.')
                ->send();

            return;
        }

        $subscription->applyCustomCoupon($coupon, $price);

        Notification::make()
            ->success()
            ->title('Cupom atualizado')
            ->body("Assinatura atualizada com o cupom {$coupon->code}.")
            ->send();
    }
}
