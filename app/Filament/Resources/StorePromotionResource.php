<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\StorePromotionResource\Pages;
use App\Models\StorePromotion;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class StorePromotionResource extends Resource
{
    protected static ?string $model = StorePromotion::class;

    protected static ?string $slug = 'destaques-semanais';

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'Destaques semanais';

    protected static ?string $navigationGroup = 'Clientes';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Destaque semanal';

    protected static ?string $pluralModelLabel = 'Destaques semanais';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Vitrine da semana')
                ->description('Reserve uma das três posições na página inicial. Horários de Brasília. A exibição começa e termina automaticamente nas datas escolhidas.')
                ->schema([
                    Forms\Components\Select::make('team_id')
                        ->label('Loja / Vitrine')
                        ->relationship('team', 'name', fn (Builder $query) => $query->active()->where('personal_team', false))
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('position')
                        ->label('Posição na home')
                        ->options([1 => '1 — Primeira', 2 => '2 — Segunda', 3 => '3 — Terceira'])
                        ->required()
                        ->default(1),
                    Forms\Components\DateTimePicker::make('starts_at')
                        ->label('Início')
                        ->timezone('America/Sao_Paulo')
                        ->seconds(false)
                        ->default(now()->startOfMinute())
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (?string $state, string $operation, Forms\Set $set): void {
                            if ($state && $operation === 'create') {
                                $set('ends_at', Carbon::parse($state, 'America/Sao_Paulo')->addDays(7)->format('Y-m-d H:i:s'));
                            }
                        }),
                    Forms\Components\DateTimePicker::make('ends_at')
                        ->label('Término')
                        ->timezone('America/Sao_Paulo')
                        ->seconds(false)
                        ->default(now()->startOfMinute()->addDays(7))
                        ->helperText('Preenchido com sete dias após o início; pode ser ajustado.')
                        ->after('starts_at')
                        ->required(),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Reserva ativa')
                        ->helperText('Desative para cancelar a reserva e liberar a posição.')
                        ->default(true),
                    Forms\Components\Textarea::make('notes')
                        ->label('Observações internas')
                        ->helperText('Ex.: valor combinado e confirmação do pagamento. Não aparece na home.')
                        ->maxLength(3000)
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('starts_at', 'desc')
            ->paginated([10, 25, 50])
            ->columns([
                Tables\Columns\TextColumn::make('team.name')->label('Loja')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('position')->label('Posição')->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Em exibição' => 'success',
                        'Agendado' => 'info',
                        'Cancelado' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('starts_at')->label('Início (Brasília)')->dateTime('d/m/Y H:i', 'America/Sao_Paulo')->sortable(),
                Tables\Columns\TextColumn::make('ends_at')->label('Término (Brasília)')->dateTime('d/m/Y H:i', 'America/Sao_Paulo')->sortable(),
                Tables\Columns\TextColumn::make('notes')->label('Observações')->limit(50)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Status')
                    ->options(['current' => 'Em exibição', 'scheduled' => 'Agendado', 'ended' => 'Encerrado', 'cancelled' => 'Cancelado'])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'current' => $query->current(),
                            'scheduled' => $query->where('is_active', true)->where('starts_at', '>', now()),
                            'ended' => $query->where('is_active', true)->where('ends_at', '<=', now()),
                            'cancelled' => $query->where('is_active', false),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('cancel')
                    ->label('Cancelar')->icon('heroicon-o-x-circle')->color('danger')
                    ->visible(fn (StorePromotion $record): bool => $record->is_active && $record->ends_at->isFuture())
                    ->requiresConfirmation()
                    ->modalHeading('Cancelar destaque semanal?')
                    ->modalDescription('A vitrine sai desta seção e a posição fica disponível. O histórico será preservado.')
                    ->action(fn (StorePromotion $record) => $record->update(['is_active' => false])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStorePromotions::route('/'),
            'create' => Pages\CreateStorePromotion::route('/create'),
            'edit' => Pages\EditStorePromotion::route('/{record}/edit'),
        ];
    }
}
