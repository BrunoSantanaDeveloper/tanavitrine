<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use DateTimeInterface;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\UserResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;

final class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Usuários';

    protected static ?string $navigationGroup = 'Clientes';

    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): string
    {
        /** @var int $count */
        $count = self::getModel()::count();

        return (string) $count;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Digite o nome completo'),
                        Forms\Components\TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('email@exemplo.com'),
                        Forms\Components\TextInput::make('password')
                            ->label('Senha')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->minLength(8)
                            ->placeholder('Mínimo de 8 caracteres'),
                        Forms\Components\Select::make('user_type')
                            ->label('Tipo de usuário')
                            ->required()
                            ->live()
                            ->options([
                                'fornecedor' => 'Fornecedor',
                                'admin' => 'Admin',
                                'parceiro' => 'Parceiro',
                            ])
                            ->default('fornecedor')
                            ->dehydrated(false),
                        Forms\Components\Toggle::make('is_partner_active')
                            ->label('Parceiro ativo')
                            ->default(true)
                            ->visible(fn (Forms\Get $get): bool => $get('user_type') === 'parceiro')
                            ->dehydrated(false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tipo_usuario')
                    ->label('Tipo')
                    ->badge()
                    ->state(function (User $record): string {
                        if ($record->is_superadmin) {
                            return 'Admin';
                        }
                        if ($record->is_partner) {
                            return 'Parceiro';
                        }

                        return 'Fornecedor';
                    })
                    ->color(function (string $state): string {
                        return match ($state) {
                            'Admin' => 'danger',
                            'Parceiro' => 'warning',
                            default => 'success',
                        };
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->orderBy('is_superadmin', $direction)
                            ->orderBy('is_partner', $direction);
                    }),

                Tables\Columns\IconColumn::make('ativo')
                    ->label('Ativo')
                    ->boolean()
                    ->state(fn (User $record): bool => !$record->trashed())
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('deleted_at', $direction)),

                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo_usuario')
                    ->label('Tipo')
                    ->options([
                        'fornecedor' => 'Fornecedor',
                        'admin' => 'Admin',
                        'parceiro' => 'Parceiro',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        return match ($value) {
                            'admin' => $query->where('is_superadmin', true),
                            'parceiro' => $query->where('is_partner', true)->where('is_superadmin', false),
                            'fornecedor' => $query->where('is_superadmin', false)->where('is_partner', false),
                            default => $query,
                        };
                    }),
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['created_from'] ?? null,
                            fn (Builder $query, mixed $date): Builder => $query->whereDate(
                                'created_at',
                                '>=',
                                type($date)->as(DateTimeInterface::class)
                            ),
                        )
                        ->when(
                            $data['created_until'] ?? null,
                            fn (Builder $query, mixed $date): Builder => $query->whereDate(
                                'created_at',
                                '<=',
                                type($date)->as(DateTimeInterface::class)
                            ),
                        )),
            ])
            ->actions([
                Tables\Actions\Action::make('edit_user')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->fillForm(function (User $record): array {
                        return [
                            'name' => $record->name,
                            'email' => $record->email,
                            'user_type' => static::resolveUserType($record),
                            'is_partner_active' => (bool) $record->is_partner_active,
                        ];
                    })
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(table: User::class, ignorable: fn (User $record): User => $record),
                        Forms\Components\Select::make('user_type')
                            ->label('Tipo de usuário')
                            ->required()
                            ->live()
                            ->options([
                                'fornecedor' => 'Fornecedor',
                                'admin' => 'Admin',
                                'parceiro' => 'Parceiro',
                            ]),
                        Forms\Components\Toggle::make('is_partner_active')
                            ->label('Parceiro ativo')
                            ->default(true)
                            ->visible(fn (Forms\Get $get): bool => $get('user_type') === 'parceiro'),
                    ])
                    ->action(function (User $record, array $data): void {
                        $newType = (string) ($data['user_type'] ?? 'fornecedor');
                        $wasPartner = (bool) $record->is_partner;

                        $flags = static::flagsByUserType($newType, (bool) ($data['is_partner_active'] ?? true));

                        $record->update([
                            'name' => $data['name'],
                            'email' => $data['email'],
                            ...$flags,
                        ]);

                        if ($wasPartner && $newType !== 'parceiro') {
                            $record->coupons()->update(['partner_id' => null]);
                        }

                        Notification::make()
                            ->title('Usuário atualizado com sucesso.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('toggle_status')
                    ->label(fn (User $record): string => $record->trashed() ? 'Ativar' : 'Desativar')
                    ->icon(fn (User $record): string => $record->trashed() ? 'heroicon-o-play-circle' : 'heroicon-o-pause-circle')
                    ->color(fn (User $record): string => $record->trashed() ? 'success' : 'warning')
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        if ($record->trashed()) {
                            $record->restore();

                            Notification::make()
                                ->title('Usuário ativado.')
                                ->success()
                                ->send();

                            return;
                        }

                        $record->delete();

                        Notification::make()
                            ->title('Usuário desativado.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (User $record): bool => Auth::id() !== $record->id),
                Tables\Actions\DeleteAction::make()
                    ->label('Excluir')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->before(function (User $record): void {
                        if ($record->is_partner) {
                            $record->coupons()->update(['partner_id' => null]);
                        }
                    })
                    ->after(function (): void {
                        Notification::make()
                            ->title('Usuário excluído.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (User $record): bool => !$record->trashed() && Auth::id() !== $record->id),
                Tables\Actions\Action::make('reset_password')
                    ->label('Resetar senha')
                    ->icon('heroicon-o-key')
                    ->color('gray')
                    ->form([
                        Forms\Components\TextInput::make('password')
                            ->label('Nova senha')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(8)
                            ->same('password_confirmation'),
                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Confirmar nova senha')
                            ->password()
                            ->revealable()
                            ->required()
                            ->dehydrated(false),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->update([
                            'password' => Hash::make((string) $data['password']),
                        ]);

                        Notification::make()
                            ->title('Senha redefinida com sucesso.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (User $record): bool => !$record->trashed()),
                Tables\Actions\ForceDeleteAction::make()
                    ->label('Excluir permanente')
                    ->requiresConfirmation()
                    ->before(function (User $record): void {
                        if ($record->is_partner) {
                            $record->coupons()->update(['partner_id' => null]);
                        }
                    })
                    ->visible(fn (User $record): bool => $record->trashed() && Auth::id() !== $record->id),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Desativar selecionados'),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->label('Excluir permanentemente'),
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
            'index' => Pages\ListUsers::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    private static function resolveUserType(User $user): string
    {
        if ($user->is_superadmin) {
            return 'admin';
        }

        if ($user->is_partner) {
            return 'parceiro';
        }

        return 'fornecedor';
    }

    /**
     * @return array{is_superadmin: bool, is_partner: bool, is_partner_active: bool}
     */
    private static function flagsByUserType(string $type, bool $partnerActive): array
    {
        return match ($type) {
            'admin' => [
                'is_superadmin' => true,
                'is_partner' => false,
                'is_partner_active' => false,
            ],
            'parceiro' => [
                'is_superadmin' => false,
                'is_partner' => true,
                'is_partner_active' => $partnerActive,
            ],
            default => [
                'is_superadmin' => false,
                'is_partner' => false,
                'is_partner_active' => false,
            ],
        };
    }
}
