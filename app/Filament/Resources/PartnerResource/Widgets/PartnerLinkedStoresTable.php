<?php

declare(strict_types=1);

namespace App\Filament\Resources\PartnerResource\Widgets;

use App\Models\Team;
use App\Models\User;
use App\Models\Subscription;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

final class PartnerLinkedStoresTable extends TableWidget
{
    public ?User $record = null;

    protected static ?string $heading = 'Lojas vinculadas por cupom';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $partnerId = $this->record?->id ?? 0;

        return $table
            ->query(
                Team::query()
                    ->with([
                        'owner.subscriptions' => function ($query) use ($partnerId): void {
                            $query
                                ->with('coupon')
                                ->whereHas('coupon', function ($couponQuery) use ($partnerId): void {
                                    $couponQuery->where('partner_id', $partnerId);
                                })
                                ->latest('created_at');
                        },
                        'plan',
                    ])
                    ->whereHas('owner.subscriptions', function ($query) use ($partnerId): void {
                        $query->whereHas('coupon', function ($couponQuery) use ($partnerId): void {
                            $couponQuery->where('partner_id', $partnerId);
                        });
                    })
            )
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Loja')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('coupon')
                    ->label('Cupom')
                    ->state(fn (Team $record): string => $this->partnerSubscription($record)?->coupon?->code ?? 'N/A')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('plan')
                    ->label('Plano')
                    ->state(fn (Team $record): string => $this->partnerSubscription($record)?->type ?? ($record->plan?->name ?? 'N/A'))
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('subscription_status')
                    ->label('Status Assinatura')
                    ->state(fn (Team $record): string => $this->partnerSubscription($record)?->stripe_status ?? 'none')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Ativa',
                        'canceled' => 'Cancelada',
                        'past_due' => 'Vencida',
                        'trialing' => 'Trial',
                        'incomplete' => 'Incompleta',
                        'none' => 'N/A',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trialing' => 'info',
                        'past_due' => 'warning',
                        'canceled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status Loja')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ativo' => 'Ativa',
                        'inativo' => 'Inativa',
                        'pendente' => 'Pendente',
                        'suspenso' => 'Suspensa',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'ativo' => 'success',
                        'inativo' => 'danger',
                        'pendente' => 'warning',
                        'suspenso' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Cadastro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ]);
    }

    private function partnerSubscription(Team $team): ?Subscription
    {
        return $team->owner?->subscriptions?->first();
    }
}

