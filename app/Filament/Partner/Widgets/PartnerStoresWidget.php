<?php

declare(strict_types=1);

namespace App\Filament\Partner\Widgets;

use App\Models\Team;
use App\Models\Subscription;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

final class PartnerStoresWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $partnerId = Auth::id();

        return $table
            ->heading('Lojas que usaram seu cupom')
            ->query(
                Team::query()
                    ->with([
                        'plan',
                        'owner.subscriptions' => function ($query) use ($partnerId): void {
                            $query
                                ->with('coupon')
                                ->whereHas('coupon', function ($couponQuery) use ($partnerId): void {
                                    $couponQuery->where('partner_id', $partnerId);
                                })
                                ->latest('created_at');
                        },
                    ])
                    ->whereHas('owner.subscriptions', function ($query) use ($partnerId): void {
                        $query->whereHas('coupon', function ($couponQuery) use ($partnerId): void {
                            $couponQuery->where('partner_id', $partnerId);
                        });
                    })
                    ->latest('teams.created_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Loja')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
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
                Tables\Columns\TextColumn::make('coupon')
                    ->label('Cupom usado')
                    ->state(fn (Team $record): string => $this->partnerSubscription($record)?->coupon?->code ?? 'N/A')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('plan')
                    ->label('Plano')
                    ->state(function (Team $record): string {
                        $subscription = $this->partnerSubscription($record);
                        return $subscription?->type
                            ?? ($record->plan?->name ?? 'N/A');
                    })
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
                        'none' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Cadastro da Loja')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ]);
    }

    private function partnerSubscription(Team $team): ?Subscription
    {
        return $team->owner?->subscriptions?->first();
    }
}

