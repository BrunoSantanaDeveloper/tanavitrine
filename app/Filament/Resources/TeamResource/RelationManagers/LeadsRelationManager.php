<?php

declare(strict_types=1);

namespace App\Filament\Resources\TeamResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

final class LeadsRelationManager extends RelationManager
{
    protected static string $relationship = 'leads';

    protected static ?string $title = 'Leads/Interessados';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('whatsapp')
                    ->label('WhatsApp')
                    ->tel()
                    ->required()
                    ->maxLength(20),
                Forms\Components\Select::make('action')
                    ->label('Ação')
                    ->options([
                        'whatsapp' => 'WhatsApp',
                        'website' => 'Website',
                        'phone' => 'Telefone',
                        'map' => 'Localização',
                        'other' => 'Outro',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('ip_address')
                    ->label('Endereço IP')
                    ->maxLength(45)
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\Textarea::make('user_agent')
                    ->label('User Agent')
                    ->rows(2)
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('whatsapp')
                    ->label('WhatsApp')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('WhatsApp copiado!')
                    ->copyMessageDuration(1500),
                Tables\Columns\TextColumn::make('action')
                    ->label('Ação')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'whatsapp' => 'success',
                        'website' => 'info',
                        'phone' => 'warning',
                        'map' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'whatsapp' => 'WhatsApp',
                        'website' => 'Website',
                        'phone' => 'Telefone',
                        'map' => 'Localização',
                        default => ucfirst($state),
                    }),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->since()
                    ->description(fn ($record): string => $record->created_at->format('d/m/Y H:i')),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->label('Ação')
                    ->options([
                        'whatsapp' => 'WhatsApp',
                        'website' => 'Website',
                        'phone' => 'Telefone',
                        'map' => 'Localização',
                        'other' => 'Outro',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Data inicial'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Data final'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('contact_whatsapp')
                    ->label('Contatar')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(fn ($record): string => "https://wa.me/{$record->whatsapp}")
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Nenhum lead cadastrado')
            ->emptyStateDescription('Leads são gerados quando visitantes demonstram interesse na loja')
            ->emptyStateIcon('heroicon-o-user-group');
    }
}
