<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Models\Plan;
use App\Models\Interval;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Webbingbrasil\FilamentCopyActions\Tables\CopyableTextColumn;
use Webbingbrasil\FilamentCopyActions\Tables\Actions\CopyAction;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Configurações';

    protected static ?string $navigationLabel = 'Planos';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalhes do Plano')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('description')
                            ->maxLength(255),
                        Forms\Components\Select::make('currency')
                            ->required()
                            ->options([
                                'brl' => 'BRL',
                                'usd' => 'USD',
                                'eur' => 'EUR',
                            ])
                            ->default('brl'),
                        Forms\Components\TextInput::make('trial_days')
                            ->numeric()
                            ->label('Período de teste (dias)')
                            ->helperText('Quantidade de dias de teste'),
                        Forms\Components\Textarea::make('features')
                            ->label('Recursos')
                            ->helperText('Informe um recurso por linha (as métricas de analytics são configuradas abaixo)')
                            ->rows(8)
                            ->columnSpanFull()
                            ->formatStateUsing(function ($state) {
                                if (is_array($state)) {
                                    // Filter out analytics array and keep only string features
                                    $stringFeatures = array_filter($state, function ($value) {
                                        return is_string($value);
                                    });
                                    return implode("\n", $stringFeatures);
                                }
                                return $state;
                            })
                            ->dehydrateStateUsing(function ($state) {
                                if (is_string($state)) {
                                    return array_filter(array_map('trim', explode("\n", $state)));
                                }
                                return $state;
                            }),

                    ])->columns(2),

                Forms\Components\Section::make('Intervalos do Plano')
                    ->schema([
                        Forms\Components\Repeater::make('intervals')
                            ->default(fn ($record) => $record?->intervals_for_form ?? [])
                            ->schema([
                                Forms\Components\Select::make('interval_id')
                                    ->label('Intervalo')
                                    ->options(Interval::where('is_active', true)->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\TextInput::make('price')
                                    ->required()
                                    ->numeric()
                                    ->prefix('R$')
                                    ->helperText('Preço em BRL (Real Brasileiro)'),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Limites da Loja')
                    ->description('Configure os limites da vitrine para este plano')
                    ->schema([
                        Forms\Components\TextInput::make('store_limit_photos_per_vitrine')
                            ->label('Fotos de destaque por vitrine')
                            ->numeric()
                            ->required()
                            ->default(3)
                            ->minValue(-1)
                            ->helperText('Use -1 para ilimitado'),
                        Forms\Components\TextInput::make('store_limit_collections_per_vitrine')
                            ->label('Coleções por vitrine')
                            ->numeric()
                            ->required()
                            ->default(-1)
                            ->minValue(-1)
                            ->helperText('Use -1 para ilimitado'),
                        Forms\Components\TextInput::make('store_limit_photos_per_collection')
                            ->label('Fotos por coleção')
                            ->numeric()
                            ->required()
                            ->default(-1)
                            ->minValue(-1)
                            ->helperText('Use -1 para ilimitado'),
                        Forms\Components\TextInput::make('store_limit_videos_per_collection')
                            ->label('Vídeos por coleção')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->minValue(-1)
                            ->helperText('Use -1 para ilimitado'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Desconto para Novo Usuário')
                    ->description('Configure descontos automáticos para novos usuários ao assinar este plano')
                    ->schema([
                        Forms\Components\Select::make('new_user_discount_type')
                            ->label('Tipo de desconto')
                            ->options([
                                'none' => 'Sem desconto',
                                'percentage' => 'Desconto percentual',
                                'fixed' => 'Desconto em valor fixo',
                                'trial' => 'Período grátis',
                            ])
                            ->default('none')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state === 'none') {
                                    $set('new_user_discount_value', null);
                                    $set('new_user_discount_duration_value', null);
                                    $set('new_user_discount_duration_unit', null);
                                }
                                if ($state === 'trial') {
                                    $set('new_user_discount_value', null);
                                }
                            }),
                        Forms\Components\TextInput::make('new_user_discount_value')
                            ->label('Valor do desconto')
                            ->numeric()
                            ->suffix(fn (callable $get) => $get('new_user_discount_type') === 'percentage' ? '%' : 'R$')
                            ->helperText('Percentual: informe um número (ex.: 50 para 50%). Fixo: informe o valor em BRL.')
                            ->visible(fn (callable $get) => in_array($get('new_user_discount_type'), ['percentage', 'fixed']))
                            ->required(fn (callable $get) => in_array($get('new_user_discount_type'), ['percentage', 'fixed'])),
                        Forms\Components\TextInput::make('new_user_discount_duration_value')
                            ->label('Duração')
                            ->numeric()
                            ->helperText('Quantidade de dias/meses/anos para o desconto')
                            ->visible(fn (callable $get) => $get('new_user_discount_type') !== 'none')
                            ->required(fn (callable $get) => $get('new_user_discount_type') !== 'none'),
                        Forms\Components\Select::make('new_user_discount_duration_unit')
                            ->label('Unidade da duração')
                            ->options([
                                'days' => 'Dias',
                                'months' => 'Meses',
                                'years' => 'Anos',
                            ])
                            ->default('months')
                            ->visible(fn (callable $get) => $get('new_user_discount_type') !== 'none')
                            ->required(fn (callable $get) => $get('new_user_discount_type') !== 'none'),
                    ])->columns(2)
                    ->collapsible()
                    ->collapsed(fn ($record) => !$record?->hasNewUserDiscount()),

                Forms\Components\Section::make('Configurações de Analytics')
                    ->description('Configure quais métricas de analytics estarão disponíveis para este plano')
                    ->schema([
                        Forms\Components\CheckboxList::make('analytics_metrics')
                            ->label('Métricas de analytics disponíveis')
                            ->options([
                                'views' => 'Visualizações da página',
                                'whatsapp_clicks' => 'Cliques no WhatsApp',
                                'website_clicks' => 'Cliques no site',
                                'map_clicks' => 'Cliques no mapa/localização',
                                'shares' => 'Compartilhamentos',
                                'instagram_clicks' => 'Cliques no Instagram',
                                'facebook_clicks' => 'Cliques no Facebook',
                                'tiktok_clicks' => 'Cliques no TikTok',
                            ])
                            ->columns(2)
                            ->helperText('Selecione quais métricas este plano pode acompanhar. Elas são salvas separadamente dos recursos em texto.'),
                    ])
                    ->columns(1)
                    ->collapsible(),

                Forms\Components\Section::make('Configurações do Plano')
                    ->schema([
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Plano em destaque')
                            ->default(false),
                        Forms\Components\Toggle::make('show_on_map')
                            ->label('Exibir no mapa')
                            ->helperText('Se ativado, lojas com este plano aparecerão no mapa')
                            ->default(false),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Ativo')
                            ->default(true),
                        Forms\Components\Toggle::make('is_default')
                            ->label('Plano padrão')
                            ->default(false),
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('intervals'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descrição')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('currency')
                    ->label('Moeda')
                    ->searchable()
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('prices')
                    ->label('Preços')
                    ->state(function ($record) {
                        $intervals = $record->intervals;
                        if ($intervals->isEmpty()) {
                            return 'Sem preços';
                        }
                        return $intervals->map(function ($interval) {
                            $price = number_format($interval->pivot->price, 2);
                            return "{$interval->name}: R$ {$price}";
                        })->implode(' | ');
                    })
                    ->wrap(),
                Tables\Columns\TextColumn::make('new_user_discount')
                    ->label('Desconto Novo Usuário')
                    ->state(function ($record) {
                        return $record->getNewUserDiscountText() ?? 'Nenhum';
                    })
                    ->badge()
                    ->color(fn ($record) => $record->hasNewUserDiscount() ? 'success' : 'gray'),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Destaque')
                    ->boolean(),
                Tables\Columns\IconColumn::make('show_on_map')
                    ->label('Mapa')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_default')
                    ->label('Padrão')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Ordem')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Excluído em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('interval_links')
                    ->label('Links de Registro')
                    ->state(function ($record) {
                        $intervals = $record->intervals()->where('is_active', true)->get();
                        if ($intervals->isEmpty()) {
                            return ['Nenhum intervalo ativo'];
                        }
                        return $intervals->map(function ($interval) use ($record) {
                            $pivotId = $interval->pivot->id;

                            $url = route('register', [
                                'plan' => $pivotId,
                            ]);
                            return [
                                'display' => $interval->name,
                                'url' => $url,
                            ];
                        })->toArray();
                    })
                    ->listWithLineBreaks()
                    ->badge()
                    ->icon('heroicon-o-clipboard')
                    ->copyable()
                    ->copyableState(function ($state) {
                        if (is_array($state) && isset($state['url'])) {
                            return $state['url'];
                        }
                        return $state;
                    })
                    ->formatStateUsing(function ($state) {
                        if (is_array($state) && isset($state['display'])) {
                            return $state['display'];
                        }
                        return $state;
                    })
                    ->copyMessage('Link copiado!')
                    ->copyMessageDuration(2000),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                ])
                ->label('Ações')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('primary'),
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
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
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
