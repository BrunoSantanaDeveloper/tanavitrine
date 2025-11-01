<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Models\Plan;
use App\Models\Interval;
use App\Models\Module;
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
                Forms\Components\Section::make('Plan Details')
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
                            ->label('Trial Period (days)')
                            ->helperText('Number of days for trial period'),
                        Forms\Components\Textarea::make('features')
                            ->label('Features')
                            ->helperText('Enter one feature per line (analytics metrics are configured below)')
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

                Forms\Components\Section::make('Plan Intervals')
                    ->schema([
                        Forms\Components\Repeater::make('intervals')
                            ->default(fn ($record) => $record?->intervals_for_form ?? [])
                            ->schema([
                                Forms\Components\Select::make('interval_id')
                                    ->label('Interval')
                                    ->options(Interval::where('is_active', true)->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\TextInput::make('price')
                                    ->required()
                                    ->numeric()
                                    ->prefix('R$')
                                    ->helperText('Price in BRL (Brazilian Real)'),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Resource Limits')
                    ->schema([
                        Forms\Components\Repeater::make('limits')
                            ->default(fn ($record) => $record?->limits_for_form ?? [])
                            ->relationship('limits')
                            ->schema([
                                Forms\Components\Select::make('module')
                                    ->required()
                                    ->options(Module::where('is_active', true)->pluck('name', 'code'))
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $module = Module::where('code', $state)->first();
                                            $set('limit_type', null);
                                            $set('limit_types', $module->getAvailableLimitTypes());
                                        }
                                    }),
                                Forms\Components\TextInput::make('resource')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('Resource identifier (e.g., playlist_media, displays, own_media_uploads)'),
                                Forms\Components\Select::make('limit_type')
                                    ->required()
                                    ->options(function (callable $get) {
                                        $module = Module::where('code', $get('module'))->first();
                                        return $module ? $module->getAvailableLimitTypes() : ['count' => 'Count'];
                                    })
                                    ->default('count'),
                                Forms\Components\TextInput::make('limit_value')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Use -1 for unlimited'),
                                Forms\Components\Select::make('period')
                                    ->required()
                                    ->options([
                                        'day' => 'Daily',
                                        'week' => 'Weekly',
                                        'month' => 'Monthly',
                                        'year' => 'Yearly',
                                    ])
                                    ->default('month'),
                                Forms\Components\TextInput::make('grace_period_days')
                                    ->numeric()
                                    ->label('Grace Period (days)')
                                    ->helperText('Number of days allowed for exceeding the limit'),
                                Forms\Components\TextInput::make('notification_threshold')
                                    ->numeric()
                                    ->label('Notification Threshold (%)')
                                    ->helperText('Percentage of limit to trigger notification')
                                    ->default(80)
                                    ->minValue(1)
                                    ->maxValue(100),
                                Forms\Components\Toggle::make('is_hard_limit')
                                    ->label('Hard Limit')
                                    ->helperText('If enabled, the limit cannot be exceeded')
                                    ->default(true),

                                Forms\Components\Toggle::make('notify_on_limit')
                                    ->label('Notify on Limit')
                                    ->helperText('Send notification when limit is reached')
                                    ->default(true),

                                Forms\Components\KeyValue::make('metadata')
                                    ->columnSpanFull(),
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('New User Discount')
                    ->description('Configure automatic discounts for new users subscribing to this plan')
                    ->schema([
                        Forms\Components\Select::make('new_user_discount_type')
                            ->label('Discount Type')
                            ->options([
                                'none' => 'No Discount',
                                'percentage' => 'Percentage Discount',
                                'fixed' => 'Fixed Amount Discount',
                                'trial' => 'Free Trial',
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
                            ->label('Discount Value')
                            ->numeric()
                            ->suffix(fn (callable $get) => $get('new_user_discount_type') === 'percentage' ? '%' : 'R$')
                            ->helperText('For percentage: enter number (e.g., 50 for 50%). For fixed: enter amount in BRL.')
                            ->visible(fn (callable $get) => in_array($get('new_user_discount_type'), ['percentage', 'fixed']))
                            ->required(fn (callable $get) => in_array($get('new_user_discount_type'), ['percentage', 'fixed'])),
                        Forms\Components\TextInput::make('new_user_discount_duration_value')
                            ->label('Duration Value')
                            ->numeric()
                            ->helperText('Number of days/months/years for the discount')
                            ->visible(fn (callable $get) => $get('new_user_discount_type') !== 'none')
                            ->required(fn (callable $get) => $get('new_user_discount_type') !== 'none'),
                        Forms\Components\Select::make('new_user_discount_duration_unit')
                            ->label('Duration Unit')
                            ->options([
                                'days' => 'Days',
                                'months' => 'Months',
                                'years' => 'Years',
                            ])
                            ->default('months')
                            ->visible(fn (callable $get) => $get('new_user_discount_type') !== 'none')
                            ->required(fn (callable $get) => $get('new_user_discount_type') !== 'none'),
                    ])->columns(2)
                    ->collapsible()
                    ->collapsed(fn ($record) => !$record?->hasNewUserDiscount()),

                Forms\Components\Section::make('Analytics Settings')
                    ->description('Configure which analytics metrics are available for this plan')
                    ->schema([
                        Forms\Components\CheckboxList::make('analytics_metrics')
                            ->label('Available Analytics Metrics')
                            ->options([
                                'views' => 'Page Views (Visualizações)',
                                'whatsapp_clicks' => 'WhatsApp Clicks',
                                'website_clicks' => 'Website Clicks',
                                'phone_clicks' => 'Phone Clicks',
                                'map_clicks' => 'Map/Location Clicks',
                                'shares' => 'Shares (Compartilhamentos)',
                                'leads' => 'Lead Captures (Leads Capturados)',
                                'instagram_clicks' => 'Instagram Clicks',
                                'facebook_clicks' => 'Facebook Clicks',
                                'tiktok_clicks' => 'TikTok Clicks',
                            ])
                            ->columns(2)
                            ->helperText('Select which analytics metrics this plan can track. These will be saved separately from text features.')
                            ->dehydrated(false),
                    ])
                    ->columns(1)
                    ->collapsible(),

                Forms\Components\Section::make('Plan Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured Plan')
                            ->default(false),
                        Forms\Components\Toggle::make('show_on_map')
                            ->label('Show on Map')
                            ->helperText('If enabled, stores with this plan will appear on the map')
                            ->default(false),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        Forms\Components\Toggle::make('is_default')
                            ->label('Default Plan')
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
