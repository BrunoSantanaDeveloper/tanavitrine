<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TeamResource\Pages;
use App\Filament\Resources\TeamResource\RelationManagers;
use App\Models\Team;
use App\Models\Category;
use App\Models\Plan;
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

final class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Lojas/Vitrines';

    protected static ?string $modelLabel = 'Loja';

    protected static ?string $pluralModelLabel = 'Lojas';

    protected static ?string $navigationGroup = 'Gerenciamento';

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
                            ->options([
                                'fabricante' => 'Fabricante',
                                'distribuidor' => 'Distribuidor',
                                'lojista' => 'Lojista',
                                'representante' => 'Representante',
                            ]),
                        Forms\Components\Select::make('category_id')
                            ->label('Categoria')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        Forms\Components\TagsInput::make('subcategory')
                            ->label('Subcategorias')
                            ->placeholder('Clique para selecionar ou digite para criar nova')
                            ->suggestions([
                                'Camisetas',
                                'Camisas',
                                'Calças',
                                'Shorts',
                                'Saias',
                                'Vestidos',
                                'Blusas',
                                'Jaquetas',
                                'Casacos',
                                'Moletons',
                                'Calçados',
                                'Tênis',
                                'Sapatos',
                                'Sandálias',
                                'Chinelos',
                                'Botas',
                                'Acessórios',
                                'Bolsas',
                                'Mochilas',
                                'Carteiras',
                                'Cintos',
                                'Bonés',
                                'Chapéus',
                                'Óculos',
                                'Joias',
                                'Relógios',
                                'Bijuterias',
                                'Lingerie',
                                'Moda Praia',
                                'Moda Fitness',
                                'Pijamas',
                                'Roupas Íntimas',
                            ])
                            ->splitKeys(['Enter', 'Tab'])
                            ->helperText('Clique nas sugestões ou digite novas subcategorias e pressione Enter'),
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

                Forms\Components\Section::make('Contatos')
                    ->schema([
                        Forms\Components\TextInput::make('whatsapp')
                            ->label('WhatsApp')
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('(00) 00000-0000'),
                        Forms\Components\TextInput::make('phone')
                            ->label('Telefone')
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('(00) 0000-0000'),
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
                    ->schema([
                        Forms\Components\TextInput::make('zip_code')
                            ->label('CEP')
                            ->maxLength(9)
                            ->placeholder('00000-000'),
                        Forms\Components\TextInput::make('address')
                            ->label('Endereço')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('city')
                            ->label('Cidade')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('state')
                            ->label('Estado')
                            ->maxLength(2)
                            ->placeholder('SP')
                            ->length(2),
                        Forms\Components\TextInput::make('latitude')
                            ->numeric()
                            ->label('Latitude'),
                        Forms\Components\TextInput::make('longitude')
                            ->numeric()
                            ->label('Longitude'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Configurações')
                    ->schema([
                        Forms\Components\Select::make('plan_id')
                            ->label('Plano')
                            ->relationship('plan', 'name')
                            ->searchable()
                            ->preload(),
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
                    ])
                    ->columns(3)
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoria')
                    ->searchable()
                    ->sortable()
                    ->badge(),
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
                    ->label('Status')
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
            RelationManagers\LeadsRelationManager::class,
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
