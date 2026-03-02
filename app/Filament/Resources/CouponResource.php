<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Filament\Resources\CouponResource\RelationManagers;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Financeiro';

    protected static ?string $navigationLabel = 'Cupons';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Cupom';

    protected static ?string $pluralModelLabel = 'Cupons';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações do Cupom')
                    ->schema([
                        Forms\Components\Select::make('partner_id')
                            ->label('Parceiro')
                            ->relationship(
                                name: 'partner',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->where('is_partner', true)
                                    ->where('is_partner_active', true)
                                    ->orderBy('name'),
                            )
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Opcional: vincule este cupom a um parceiro/influencer.'),
                        Forms\Components\TextInput::make('code')
                            ->label('Código')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Código único do cupom (ex: BLACKVIP)'),
                        Forms\Components\TextInput::make('name')
                            ->label('Nome')
                            ->maxLength(255)
                            ->helperText('Nome descritivo do cupom'),
                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Desconto')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Tipo de Desconto')
                            ->required()
                            ->options([
                                'percentage' => 'Percentual',
                                'fixed' => 'Valor Fixo',
                            ])
                            ->default('percentage')
                            ->reactive(),
                        Forms\Components\TextInput::make('value')
                            ->label('Valor')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(fn ($get) => $get('type') === 'percentage' ? 100 : null)
                            ->suffix(fn ($get) => $get('type') === 'percentage' ? '%' : 'R$')
                            ->helperText(fn ($get) => $get('type') === 'percentage'
                                ? 'Percentual de desconto (0-100)'
                                : 'Valor fixo em reais'),
                    ])->columns(2),

                Forms\Components\Section::make('Limites e Validade')
                    ->schema([
                        Forms\Components\TextInput::make('max_uses')
                            ->label('Máximo de Usos')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Deixe em branco para usos ilimitados'),
                        Forms\Components\TextInput::make('uses_count')
                            ->label('Usos Realizados')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2),

                Forms\Components\Section::make('Duração do Desconto')
                    ->description('Configure a duração do desconto de forma numérica ou com datas específicas')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('duration_value')
                                    ->label('Duração')
                                    ->numeric()
                                    ->minValue(1)
                                    ->helperText('Valor numérico da duração'),
                                Forms\Components\Select::make('duration_unit')
                                    ->label('Unidade')
                                    ->options([
                                        'days' => 'Dias',
                                        'months' => 'Meses',
                                        'years' => 'Anos',
                                    ])
                                    ->helperText('Unidade de tempo'),
                            ]),
                        Forms\Components\Placeholder::make('duration_helper')
                            ->label('')
                            ->content('OU defina datas específicas abaixo:')
                            ->columnSpanFull()
                            ->extraAttributes(['class' => 'text-center text-sm text-gray-500 font-medium']),
                        Forms\Components\DateTimePicker::make('valid_from')
                            ->label('Válido de')
                            ->helperText('Data de início da validade (opcional)'),
                        Forms\Components\DateTimePicker::make('valid_until')
                            ->label('Válido até')
                            ->helperText('Data de término (será ignorada se duração estiver definida)'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Ativo')
                            ->default(true),
                        Forms\Components\Toggle::make('is_exit_intent')
                            ->label('Cupom de Exit Intent')
                            ->helperText('Mostrar este cupom quando o usuário tentar sair da página de planos')
                            ->default(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Código copiado!')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('partner.name')
                    ->label('Parceiro')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Sem parceiro')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'percentage' => 'Percentual',
                        'fixed' => 'Valor Fixo',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'percentage' => 'info',
                        'fixed' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('value')
                    ->label('Valor')
                    ->formatStateUsing(fn ($record) =>
                        $record->type === 'percentage'
                            ? $record->value . '%'
                            : 'R$ ' . number_format($record->value, 2, ',', '.')
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('uses_count')
                    ->label('Usos')
                    ->formatStateUsing(fn ($record) =>
                        $record->max_uses
                            ? $record->uses_count . ' / ' . $record->max_uses
                            : $record->uses_count . ' (ilimitado)'
                    )
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_exit_intent')
                    ->label('Exit Intent')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('duration')
                    ->label('Duração')
                    ->formatStateUsing(fn ($record) =>
                        $record->duration_value && $record->duration_unit
                            ? $record->getDurationText()
                            : '-'
                    )
                    ->badge()
                    ->color('info')
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderBy('duration_value', $direction);
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('valid_from')
                    ->label('Válido de')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('valid_until')
                    ->label('Válido até')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'percentage' => 'Percentual',
                        'fixed' => 'Valor Fixo',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Ativo')
                    ->trueLabel('Apenas ativos')
                    ->falseLabel('Apenas inativos')
                    ->native(false),
                Tables\Filters\TernaryFilter::make('is_exit_intent')
                    ->label('Exit Intent')
                    ->trueLabel('Apenas Exit Intent')
                    ->falseLabel('Apenas Normais')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
