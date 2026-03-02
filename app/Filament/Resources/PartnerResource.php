<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\PartnerResource\Pages;
use App\Models\Coupon;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

final class PartnerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Parceiros';

    protected static ?string $navigationGroup = 'Clientes';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Parceiro';

    protected static ?string $pluralModelLabel = 'Parceiros';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados do Parceiro')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('password')
                            ->label('Senha')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->minLength(8)
                            ->maxLength(255),
                        Forms\Components\Toggle::make('is_partner')
                            ->label('Conta de parceiro')
                            ->default(true)
                            ->required()
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\Toggle::make('is_partner_active')
                            ->label('Parceiro ativo')
                            ->default(true)
                            ->helperText('Desative para bloquear o acesso ao portal /parceiros.'),
                        Forms\Components\Toggle::make('is_superadmin')
                            ->label('Super Admin')
                            ->default(false)
                            ->helperText('Mantenha desligado para parceiros.')
                            ->disabled(),
                        Forms\Components\Select::make('coupon_ids')
                            ->label('Cupons vinculados')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(fn (): array => Coupon::query()
                                ->orderBy('code')
                                ->pluck('code', 'id')
                                ->toArray())
                            ->helperText('Selecione os cupons que este parceiro poderá usar/acompanhar.'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_partner_active')
                    ->label('Ativo')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('coupons_count')
                    ->label('Cupons')
                    ->counts('coupons')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn (User $record): string => $record->is_partner_active ? 'Desativar' : 'Ativar')
                    ->icon(fn (User $record): string => $record->is_partner_active ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
                    ->color(fn (User $record): string => $record->is_partner_active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        $record->update([
                            'is_partner_active' => !$record->is_partner_active,
                        ]);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->before(function (User $record): void {
                        $record->coupons()->update(['partner_id' => null]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->recordUrl(fn (User $record): string => static::getUrl('view', ['record' => $record]));
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('is_partner', true)
            ->where('is_superadmin', false);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPartners::route('/'),
            'create' => Pages\CreatePartner::route('/create'),
            'view' => Pages\ViewPartner::route('/{record}'),
            'edit' => Pages\EditPartner::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->is_superadmin === true;
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->is_superadmin === true;
    }

    public static function canEdit(mixed $record): bool
    {
        return Auth::user()?->is_superadmin === true;
    }

    public static function canView(mixed $record): bool
    {
        return Auth::user()?->is_superadmin === true;
    }

    public static function canDelete(mixed $record): bool
    {
        return Auth::user()?->is_superadmin === true;
    }
}
