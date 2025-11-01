<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Models\User;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\SubscriptionResource\Pages;

final class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Financeiro';

    protected static ?string $navigationLabel = 'Assinaturas';

    protected static ?string $modelLabel = 'Assinatura';

    protected static ?string $pluralModelLabel = 'Assinaturas';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): string
    {
        return (string) static::getModel()::query()->where('stripe_status', 'active')->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações da Assinatura')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Usuário')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('type')
                            ->label('Tipo/Nome')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('stripe_id')
                            ->label('Stripe ID')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('stripe_status')
                            ->label('Status')
                            ->required()
                            ->options([
                                'active' => 'Ativo',
                                'canceled' => 'Cancelado',
                                'incomplete' => 'Incompleto',
                                'incomplete_expired' => 'Incompleto Expirado',
                                'past_due' => 'Vencido',
                                'trialing' => 'Em Trial',
                                'unpaid' => 'Não Pago',
                            ]),
                        Forms\Components\TextInput::make('stripe_price')
                            ->label('Stripe Price ID')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantidade')
                            ->numeric()
                            ->default(1),
                    ])->columns(2),

                Forms\Components\Section::make('Desconto/Cupom')
                    ->description('Gerencie cupons e descontos aplicados à assinatura')
                    ->schema([
                        Forms\Components\Select::make('coupon_id')
                            ->label('Cupom')
                            ->relationship('coupon', 'code', function ($query) {
                                return $query->where('is_active', true);
                            })
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state) {
                                    $coupon = \App\Models\Coupon::find($state);
                                    if ($coupon && $coupon->isValid()) {
                                        // Calculate expiration date
                                        $expirationDate = $coupon->calculateExpirationDate();
                                        if ($expirationDate) {
                                            $set('discount_ends_at', $expirationDate);
                                        }
                                    }
                                }
                            })
                            ->helperText('Selecione um cupom ativo para aplicar desconto'),
                        Forms\Components\Placeholder::make('coupon_info')
                            ->label('Informações do Cupom')
                            ->content(function ($get) {
                                $couponId = $get('coupon_id');
                                if (!$couponId) {
                                    return 'Nenhum cupom selecionado';
                                }

                                $coupon = \App\Models\Coupon::find($couponId);
                                if (!$coupon) {
                                    return 'Cupom não encontrado';
                                }

                                $info = [];
                                $info[] = "Tipo: " . ($coupon->type === 'percentage' ? 'Percentual' : 'Valor Fixo');
                                $info[] = "Valor: " . ($coupon->type === 'percentage' ? $coupon->value . '%' : 'R$ ' . number_format((float) $coupon->value, 2, ',', '.'));

                                if ($coupon->duration_value && $coupon->duration_unit) {
                                    $info[] = "Duração: " . $coupon->getDurationText();
                                }

                                if (!$coupon->isValid()) {
                                    $info[] = "⚠️ CUPOM INVÁLIDO/EXPIRADO";
                                }

                                return implode(' | ', $info);
                            })
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('original_price')
                            ->label('Preço Original')
                            ->numeric()
                            ->prefix('R$')
                            ->step(0.01)
                            ->nullable()
                            ->helperText('Preço antes do desconto'),
                        Forms\Components\TextInput::make('discount_amount')
                            ->label('Valor do Desconto')
                            ->numeric()
                            ->prefix('R$')
                            ->step(0.01)
                            ->nullable()
                            ->helperText('Valor descontado'),
                        Forms\Components\TextInput::make('final_price')
                            ->label('Preço Final')
                            ->numeric()
                            ->prefix('R$')
                            ->step(0.01)
                            ->nullable()
                            ->helperText('Preço após desconto'),
                        Forms\Components\DateTimePicker::make('discount_ends_at')
                            ->label('Desconto Válido Até')
                            ->nullable()
                            ->helperText('Data de expiração do desconto (calculada automaticamente)'),
                    ])->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Datas')
                    ->schema([
                        Forms\Components\DateTimePicker::make('trial_ends_at')
                            ->label('Trial Termina Em')
                            ->nullable(),
                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('Termina Em')
                            ->nullable()
                            ->helperText('Deixe vazio para assinatura ativa'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('coupon'))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuário')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Plano')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stripe_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Ativo',
                        'canceled' => 'Cancelado',
                        'incomplete' => 'Incompleto',
                        'past_due' => 'Vencido',
                        'trialing' => 'Em Trial',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'canceled' => 'danger',
                        'trialing' => 'info',
                        'past_due' => 'warning',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('coupon.code')
                    ->label('Cupom')
                    ->badge()
                    ->color('purple')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('discount_status')
                    ->label('Status Desconto')
                    ->badge()
                    ->formatStateUsing(function ($record) {
                        if (!$record->coupon_id) {
                            return 'Sem desconto';
                        }

                        if (!$record->discount_ends_at) {
                            return 'Desconto';
                        }

                        if (now()->isAfter($record->discount_ends_at)) {
                            return 'Expirado';
                        }

                        $daysRemaining = now()->diffInDays($record->discount_ends_at);
                        return "Ativo ({$daysRemaining}d)";
                    })
                    ->color(function ($record) {
                        if (!$record->coupon_id) {
                            return 'gray';
                        }

                        if (!$record->discount_ends_at) {
                            return 'info';
                        }

                        if (now()->isAfter($record->discount_ends_at)) {
                            return 'danger';
                        }

                        $daysRemaining = now()->diffInDays($record->discount_ends_at);
                        if ($daysRemaining <= 7) {
                            return 'warning';
                        }

                        return 'success';
                    })
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderBy('discount_ends_at', $direction);
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('final_price')
                    ->label('Preço')
                    ->formatStateUsing(fn ($record) =>
                        $record->final_price
                            ? 'R$ ' . number_format((float) $record->final_price, 2, ',', '.')
                            : ($record->original_price ? 'R$ ' . number_format((float) $record->original_price, 2, ',', '.') : 'N/A')
                    )
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('discount_ends_at')
                    ->label('Desconto Até')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('Sem desconto'),
                Tables\Columns\TextColumn::make('trial_ends_at')
                    ->label('Trial Até')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Termina Em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado Em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->label('Apenas Ativos')
                    ->query(fn (Builder $query): Builder => $query->whereNull('ends_at')),
                Tables\Filters\Filter::make('ended')
                    ->label('Apenas Encerrados')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('ends_at')),
                Tables\Filters\Filter::make('with_discount')
                    ->label('Com Desconto Ativo')
                    ->query(fn (Builder $query): Builder =>
                        $query->whereNotNull('coupon_id')
                            ->where('discount_ends_at', '>', now())
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('remove_coupon')
                    ->label('Remover Cupom')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->coupon_id !== null)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'coupon_id' => null,
                            'original_price' => null,
                            'discount_amount' => null,
                            'final_price' => null,
                            'discount_ends_at' => null,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Cupom removido')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}
