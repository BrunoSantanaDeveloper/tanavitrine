<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TeamResource\Pages;
use App\Filament\Resources\TeamResource\RelationManagers;
use App\Models\Team;
use App\Models\Category;
use App\Models\Plan;
use App\Models\Coupon;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

final class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Lojas/Vitrines';

    protected static ?string $modelLabel = 'Loja';

    protected static ?string $pluralModelLabel = 'Lojas';

    protected static ?string $navigationGroup = 'Clientes';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações Básicas')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Proprietário')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn () => auth()->id())
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('password')
                                    ->password()
                                    ->required()
                                    ->minLength(8),
                            ]),
                        Forms\Components\TextInput::make('name')
                            ->label('Nome da Loja')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) =>
                                $operation === 'create' ? $set('slug', Str::slug($state)) : null
                            ),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug (URL)')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('URL amigável para a loja'),
                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Tipo de Negócio')
                    ->schema([
                        Forms\Components\Select::make('sale_type')
                            ->label('Tipo de Venda')
                            ->options([
                                'atacado' => 'Atacado',
                                'varejo' => 'Varejo',
                                'ambos' => 'Ambos',
                            ])
                            ->required()
                            ->default('varejo'),
                        Forms\Components\Select::make('store_type')
                            ->label('Tipo de Loja')
                            ->formatStateUsing(fn ($state) => $state === 'fisica' ? 'ambos' : $state)
                            ->options([
                                'virtual' => 'Loja Virtual',
                                'ambos' => 'Virtual / Física',
                            ])
                            ->live()
                            ->required()
                            ->default('virtual')
                            ->helperText('Loja Virtual: atendimento por redes sociais, WhatsApp ou site. Virtual / Física: atendimento online e também em loja física.'),
                        Forms\Components\Select::make('category_id')
                            ->label('Categoria')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('subcategory', []);
                            })
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        Forms\Components\Select::make('subcategory')
                            ->label('Subcategorias')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->placeholder('Selecione a categoria primeiro')
                            ->options(function (Forms\Get $get) {
                                $categoryId = $get('category_id');
                                if (!$categoryId) {
                                    return [];
                                }

                                $category = Category::with('children')->find($categoryId);
                                if (!$category || !$category->children) {
                                    return [];
                                }

                                return $category->children->pluck('name', 'name')->toArray();
                            })
                            ->helperText('Selecione uma ou mais subcategorias que representam seus produtos')
                            ->disabled(fn (Forms\Get $get): bool => !$get('category_id'))
                            ->dehydrateStateUsing(fn ($state) => is_array($state) ? $state : []),
                        Forms\Components\Select::make('gender')
                            ->label('Gênero')
                            ->options([
                                'masculino' => 'Masculino',
                                'feminino' => 'Feminino',
                                'unissex' => 'Unissex',
                                'infantil' => 'Infantil',
                            ]),
                        Forms\Components\TextInput::make('min_order')
                            ->label('Pedido Mínimo')
                            ->numeric()
                            ->prefix('R$'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Mídia')
                    ->schema([
                        Forms\Components\FileUpload::make('logo_path')
                            ->label('Logo da Loja')
                            ->image()
                            ->disk('public')
                            ->directory('stores/logos')
                            ->maxSize(2048)
                            ->imageEditor()
                            ->helperText('Imagem quadrada recomendada (máx. 2MB)'),
                        Forms\Components\TextInput::make('video_url')
                            ->label('Vídeo')
                            ->maxLength(500)
                            ->placeholder('https://www.youtube.com/watch?v=... ou https://vimeo.com/...')
                            ->helperText('Cole o link do vídeo do YouTube ou Vimeo')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Contatos')
                    ->schema([
                        Forms\Components\TextInput::make('whatsapp')
                            ->label('WhatsApp')
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('(00) 00000-0000'),
                        Forms\Components\TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('website')
                            ->label('Website')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('https://'),
                        Forms\Components\TextInput::make('instagram')
                            ->label('Instagram')
                            ->maxLength(255)
                            ->placeholder('@usuario'),
                        Forms\Components\TextInput::make('facebook')
                            ->label('Facebook')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('tiktok')
                            ->label('TikTok')
                            ->maxLength(255)
                            ->placeholder('@usuario'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Endereço')
                    ->description('Preencha o CEP para carregar automaticamente o endereço. As coordenadas GPS são preenchidas automaticamente ao salvar.')
                    ->disabled(fn (Forms\Get $get) => $get('store_type') === 'virtual')
                    ->schema([
                        Forms\Components\TextInput::make('zip_code')
                            ->label('CEP')
                            ->required()
                            ->maxLength(9)
                            ->placeholder('00000-000')
                            ->mask('99999-999')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state, Forms\Get $get) {
                                if (!$state || strlen(str_replace('-', '', $state)) !== 8) {
                                    return;
                                }

                                // Remove formatting
                                $cep = preg_replace('/\D/', '', $state);

                                try {
                                    // Call ViaCEP API
                                    $response = Http::timeout(5)->get("https://viacep.com.br/ws/{$cep}/json/");

                                    if ($response->successful() && $data = $response->json()) {
                                        if (!isset($data['erro'])) {
                                            // Fill address fields automatically
                                            $street = $data['logradouro'] ?? '';
                                            $city = $data['localidade'] ?? '';
                                            $state_uf = $data['uf'] ?? '';

                                            if ($street) {
                                                $set('address', $street);
                                            }
                                            if ($city) {
                                                $set('city', $city);
                                            }
                                            if ($state_uf) {
                                                $set('state', $state_uf);
                                            }

                                            // Show success notification
                                            \Filament\Notifications\Notification::make()
                                                ->title('CEP encontrado!')
                                                ->body('Endereço preenchido automaticamente.')
                                                ->success()
                                                ->send();
                                        } else {
                                            // CEP not found
                                            \Filament\Notifications\Notification::make()
                                                ->title('CEP não encontrado')
                                                ->body('Verifique o número digitado.')
                                                ->warning()
                                                ->send();
                                        }
                                    }
                                } catch (\Exception $e) {
                                    // Silent fail - user can fill manually
                                    \Filament\Notifications\Notification::make()
                                        ->title('Erro ao buscar CEP')
                                        ->body('Por favor, preencha manualmente.')
                                        ->danger()
                                        ->send();
                                }
                            })
                            ->helperText('Digite o CEP completo para buscar automaticamente'),
                        Forms\Components\TextInput::make('address')
                            ->label('Endereço (Rua, Nº)')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ex: Rua Augusta, 123')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('city')
                            ->label('Cidade')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('state')
                            ->label('Estado (UF)')
                            ->required()
                            ->maxLength(2)
                            ->placeholder('SP')
                            ->length(2),
                        Forms\Components\TextInput::make('latitude')
                            ->numeric()
                            ->label('Latitude')
                            ->helperText('Preenchido automaticamente após salvar'),
                        Forms\Components\TextInput::make('longitude')
                            ->numeric()
                            ->label('Longitude')
                            ->helperText('Preenchido automaticamente após salvar'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Plano e Assinatura')
                    ->description('Ao alterar o plano, a assinatura do proprietário será automaticamente sincronizada')
                    ->schema([
                        Forms\Components\Select::make('plan_id')
                            ->label('Plano Atribuído')
                            ->relationship('plan', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('⚡ Ao salvar, a subscription do proprietário será criada/atualizada automaticamente')
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $plan = \App\Models\Plan::find($state);
                                    if ($plan) {
                                        $set('plan_info', $plan->description);
                                    }
                                }
                            }),
                        Forms\Components\Placeholder::make('plan_info')
                            ->label('Descrição do Plano')
                            ->content(function ($record, Forms\Get $get) {
                                $planId = $get('plan_id') ?? $record?->plan_id;
                                if (!$planId) {
                                    return 'Nenhum plano selecionado - usará o plano gratuito padrão';
                                }
                                $plan = \App\Models\Plan::find($planId);
                                return $plan?->description ?? 'Plano não encontrado';
                            }),
                        Forms\Components\Placeholder::make('subscription_status')
                            ->label('Status da Assinatura')
                            ->content(function ($record) {
                                if (!$record || !$record->owner) {
                                    return 'N/A';
                                }

                                $subscription = $record->owner->subscription('default');
                                if (!$subscription) {
                                    return '❌ Sem assinatura ativa';
                                }

                                $status = match($subscription->stripe_status) {
                                    'active' => '✅ Ativa',
                                    'past_due' => '⚠️ Vencida',
                                    'canceled' => '❌ Cancelada',
                                    default => '⏸️ ' . $subscription->stripe_status,
                                };

                                return "{$status} | Plano: {$subscription->type}";
                            })
                            ->visibleOn('edit'),
                        Forms\Components\Placeholder::make('current_subscription_coupon')
                            ->label('Cupom Atual')
                            ->content(function ($record) {
                                $couponCode = $record?->owner?->subscription('default')?->coupon?->code;

                                return $couponCode ? "Cupom aplicado: {$couponCode}" : 'Sem cupom aplicado';
                            })
                            ->visibleOn('edit'),
                        Forms\Components\Select::make('subscription_coupon_id')
                            ->label('Trocar Cupom da Assinatura')
                            ->visibleOn('edit')
                            ->formatStateUsing(fn ($state, $record) => $record?->owner?->subscription('default')?->coupon_id)
                            ->options(function (): array {
                                return Coupon::query()
                                    ->where('is_active', true)
                                    ->where(function ($q) {
                                        $q->whereNull('valid_until')
                                            ->orWhere('valid_until', '>', now());
                                    })
                                    ->orderBy('code')
                                    ->pluck('code', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->dehydrated()
                            ->helperText('Selecione um cupom para trocar o cupom atual da assinatura.'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Configurações da Loja')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'ativo' => 'Ativo',
                                'inativo' => 'Inativo',
                                'pendente' => 'Pendente',
                                'suspenso' => 'Suspenso',
                            ])
                            ->required()
                            ->default('ativo'),
                        Forms\Components\Toggle::make('featured')
                            ->label('Destaque')
                            ->helperText('Marcar como loja em destaque')
                            ->default(false),
                        Forms\Components\DateTimePicker::make('featured_until')
                            ->label('Destaque até')
                            ->helperText('Data até quando a loja ficará em destaque'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Analytics')
                    ->description('Métricas de desempenho da loja (somente leitura)')
                    ->schema([
                        Forms\Components\TextInput::make('views_count')
                            ->label('Visualizações')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('whatsapp_clicks')
                            ->label('Cliques no WhatsApp')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('website_clicks')
                            ->label('Cliques no Website')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('phone_clicks')
                            ->label('Cliques no Telefone')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('map_clicks')
                            ->label('Cliques no Mapa')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('shares_count')
                            ->label('Compartilhamentos')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('instagram_clicks')
                            ->label('Cliques no Instagram')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('facebook_clicks')
                            ->label('Cliques no Facebook')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('tiktok_clicks')
                            ->label('Cliques no TikTok')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(3)
                    ->visibleOn('edit')
                    ->collapsible()
                    ->collapsed(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['owner.subscriptions', 'plan', 'category']))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Proprietário')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plano')
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => $record->plan_id ? 'success' : 'gray')
                    ->default('Gratuito'),
                Tables\Columns\TextColumn::make('subscription_status')
                    ->label('Status Assinatura')
                    ->state(function ($record) {
                        $subscription = $record->owner?->subscription('default');
                        return $subscription?->stripe_status ?? 'none';
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'active' => 'success',
                        'past_due' => 'warning',
                        'canceled' => 'danger',
                        'none' => 'gray',
                        default => 'info',
                    })
                    ->formatStateUsing(fn ($state) => match($state) {
                        'active' => 'Ativa',
                        'past_due' => 'Vencida',
                        'canceled' => 'Cancelada',
                        'none' => 'Sem assinatura',
                        default => ucfirst($state),
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoria')
                    ->searchable()
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('subcategory')
                    ->label('Subcategorias')
                    ->badge()
                    ->separator(',')
                    ->limit(2)
                    ->tooltip(fn ($record) => is_array($record->subcategory) ? implode(', ', $record->subcategory) : $record->subcategory)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('sale_type')
                    ->label('Tipo de Venda')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'atacado' => 'info',
                        'varejo' => 'success',
                        'ambos' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('city')
                    ->label('Cidade')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('state')
                    ->label('UF')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('featured')
                    ->label('Destaque')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ativo' => 'success',
                        'inativo' => 'danger',
                        'pendente' => 'warning',
                        'suspenso' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('views_count')
                    ->label('Visualizações')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('photos_count')
                    ->label('Fotos')
                    ->counts('photos')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plan')
                    ->label('Plano')
                    ->relationship('plan', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('without_plan')
                    ->label('Sem Plano Atribuído')
                    ->query(fn ($query) => $query->whereNull('plan_id')),
                Tables\Filters\Filter::make('with_active_subscription')
                    ->label('Com Assinatura Ativa')
                    ->query(function ($query) {
                        $query->whereHas('owner.subscriptions', function ($q) {
                            $q->where('stripe_status', 'active')
                                ->whereNull('ends_at');
                        });
                    }),
                Tables\Filters\SelectFilter::make('category')
                    ->label('Categoria')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('sale_type')
                    ->label('Tipo de Venda')
                    ->options([
                        'atacado' => 'Atacado',
                        'varejo' => 'Varejo',
                        'ambos' => 'Ambos',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status da Loja')
                    ->options([
                        'ativo' => 'Ativo',
                        'inativo' => 'Inativo',
                        'pendente' => 'Pendente',
                        'suspenso' => 'Suspenso',
                    ]),
                Tables\Filters\TernaryFilter::make('featured')
                    ->label('Em Destaque')
                    ->placeholder('Todos')
                    ->trueLabel('Apenas em destaque')
                    ->falseLabel('Não destacadas'),
                Tables\Filters\SelectFilter::make('state')
                    ->label('Estado')
                    ->options(fn () => Team::query()
                        ->whereNotNull('state')
                        ->distinct()
                        ->pluck('state', 'state')
                        ->toArray()
                    )
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view')
                        ->label('Ver Loja')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->url(fn (Team $record): string => route('store.show', $record->slug))
                        ->openUrlInNewTab(),
                    Tables\Actions\EditAction::make()
                        ->label('Editar')
                        ->icon('heroicon-o-pencil')
                        ->color('primary'),
                    Tables\Actions\Action::make('change_plan')
                        ->label('Alterar Plano')
                        ->icon('heroicon-o-credit-card')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('plan_id')
                                ->label('Novo Plano')
                                ->options(\App\Models\Plan::query()->where('is_active', true)->pluck('name', 'id'))
                                ->searchable()
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    if ($state) {
                                        $plan = \App\Models\Plan::with('intervals')->find($state);
                                        if ($plan && $plan->intervals->isNotEmpty()) {
                                            $interval = $plan->intervals->first();
                                            $set('price', $interval->pivot->price ?? 0);
                                        }
                                    }
                                })
                                ->helperText('A assinatura do proprietário será automaticamente atualizada'),
                            Forms\Components\TextInput::make('price')
                                ->label('Preço do Plano')
                                ->prefix('R$')
                                ->numeric()
                                ->disabled()
                                ->dehydrated(false),
                            Forms\Components\Select::make('coupon_id')
                                ->label('Aplicar Cupom (Opcional)')
                                ->options(function () {
                                    return \App\Models\Coupon::query()
                                        ->where('is_active', true)
                                        ->where(function ($q) {
                                            $q->whereNull('valid_until')
                                              ->orWhere('valid_until', '>', now());
                                        })
                                        ->pluck('code', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->reactive()
                                ->helperText('Selecione um cupom para aplicar desconto'),
                        ])
                        ->action(function (Team $record, array $data) {
                            $record->update(['plan_id' => $data['plan_id']]);

                            // Se houver cupom, atualizar a subscription com desconto
                            if (!empty($data['coupon_id'])) {
                                $subscription = $record->owner->subscription('default');
                                if ($subscription) {
                                    $coupon = \App\Models\Coupon::find($data['coupon_id']);
                                    $plan = \App\Models\Plan::with('intervals')->find($data['plan_id']);
                                    $interval = $plan->intervals->first();
                                    $price = (float) ($interval->pivot->price ?? 0);

                                    if ($coupon && $coupon->isValid()) {
                                        $discountAmount = $coupon->calculateDiscount($price);
                                        $discountEndsAt = $coupon->calculateExpirationDate();

                                        $subscription->update([
                                            'coupon_id' => $coupon->id,
                                            'original_price' => $price,
                                            'discount_amount' => $discountAmount,
                                            'final_price' => $price - $discountAmount,
                                            'discount_ends_at' => $discountEndsAt,
                                        ]);

                                        $coupon->incrementUses();
                                    }
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Plano atualizado com sucesso!')
                                ->success()
                                ->send();
                        })
                        ->successNotificationTitle('Plano atualizado!'),
                    Tables\Actions\Action::make('toggle_status')
                        ->label(fn (Team $record): string => $record->status === 'ativo' ? 'Desativar' : 'Ativar')
                        ->icon(fn (Team $record): string => $record->status === 'ativo' ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn (Team $record): string => $record->status === 'ativo' ? 'danger' : 'success')
                        ->requiresConfirmation()
                        ->modalHeading(fn (Team $record): string => $record->status === 'ativo' ? 'Desativar Loja?' : 'Ativar Loja?')
                        ->modalDescription(fn (Team $record): string =>
                            $record->status === 'ativo'
                                ? 'A loja não será mais exibida no marketplace.'
                                : 'A loja voltará a ser exibida no marketplace.'
                        )
                        ->action(function (Team $record) {
                            $newStatus = $record->status === 'ativo' ? 'inativo' : 'ativo';
                            $record->update(['status' => $newStatus]);
                        })
                        ->successNotificationTitle(fn (Team $record): string =>
                            $record->status === 'ativo' ? 'Loja ativada!' : 'Loja desativada!'
                        ),
                    Tables\Actions\Action::make('toggle_featured')
                        ->label(fn (Team $record): string => $record->featured ? 'Remover Destaque' : 'Destacar')
                        ->icon('heroicon-o-star')
                        ->color(fn (Team $record): string => $record->featured ? 'warning' : 'gray')
                        ->requiresConfirmation()
                        ->modalHeading(fn (Team $record): string => $record->featured ? 'Remover Destaque?' : 'Destacar Loja?')
                        ->modalDescription(fn (Team $record): string =>
                            $record->featured
                                ? 'A loja não aparecerá mais em destaque.'
                                : 'A loja aparecerá em destaque por 30 dias.'
                        )
                        ->action(fn (Team $record) => $record->update([
                            'featured' => !$record->featured,
                            'featured_until' => !$record->featured ? now()->addDays(30) : null,
                        ]))
                        ->successNotificationTitle(fn (Team $record): string =>
                            $record->featured ? 'Loja destacada!' : 'Destaque removido!'
                        )
                        ->visible(fn (Team $record): bool => $record->status === 'ativo'),
                    Tables\Actions\DeleteAction::make()
                        ->label('Excluir')
                        ->icon('heroicon-o-trash')
                        ->modalHeading('Excluir Loja')
                        ->modalDescription('Tem certeza? Esta ação não pode ser desfeita e todos os dados relacionados serão perdidos.')
                        ->successNotificationTitle('Loja excluída com sucesso'),
                ])
                    ->label('Ações')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Ativar')
                        ->icon('heroicon-o-check-circle')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['status' => 'ativo']))
                        ->color('success'),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Desativar')
                        ->icon('heroicon-o-x-circle')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['status' => 'inativo']))
                        ->color('danger'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PhotosRelationManager::class,
            RelationManagers\CollectionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeams::route('/'),
            'create' => Pages\CreateTeam::route('/create'),
            'edit' => Pages\EditTeam::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }
}
