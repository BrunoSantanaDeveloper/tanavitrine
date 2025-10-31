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

final class PhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'photos';

    protected static ?string $title = 'Fotos da Loja';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('path')
                    ->label('Foto')
                    ->image()
                    ->directory('stores/photos')
                    ->maxSize(5120)
                    ->imageEditor()
                    ->imageEditorAspectRatios([
                        '16:9',
                        '4:3',
                        '1:1',
                    ])
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label('Descrição')
                    ->maxLength(500)
                    ->rows(3),
                Forms\Components\TextInput::make('order')
                    ->label('Ordem')
                    ->numeric()
                    ->default(0)
                    ->helperText('Ordem de exibição da foto'),
                Forms\Components\Toggle::make('is_primary')
                    ->label('Foto Principal')
                    ->helperText('Marcar como foto de capa da loja'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\ImageColumn::make('path')
                    ->label('Foto')
                    ->disk('public')
                    ->height(80),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pivot.order')
                    ->label('Ordem')
                    ->sortable(),
                Tables\Columns\IconColumn::make('pivot.is_primary')
                    ->label('Principal')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('size')
                    ->label('Tamanho')
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . ' MB')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criada em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('pivot.order', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('pivot.is_primary')
                    ->label('Foto Principal')
                    ->placeholder('Todas')
                    ->trueLabel('Apenas principal')
                    ->falseLabel('Não principal'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Forms\Components\TextInput::make('order')
                            ->label('Ordem')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Toggle::make('is_primary')
                            ->label('Foto Principal'),
                    ]),
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['type'] = 'image';
                        $data['team_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    })
                    ->after(function ($record, $data) {
                        $this->getOwnerRecord()->photos()->attach($record->id, [
                            'order' => $data['order'] ?? 0,
                            'is_primary' => $data['is_primary'] ?? false,
                        ]);
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('set_primary')
                    ->label('Definir como Principal')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->visible(fn ($record) => !$record->pivot->is_primary)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        // Remove primary flag from all photos
                        $this->getOwnerRecord()->photos()->updateExistingPivot(
                            $this->getOwnerRecord()->photos()->pluck('media.id'),
                            ['is_primary' => false]
                        );
                        // Set this photo as primary
                        $this->getOwnerRecord()->photos()->updateExistingPivot(
                            $record->id,
                            ['is_primary' => true]
                        );
                    }),
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data, $record): array {
                        $data['order'] = $record->pivot->order ?? 0;
                        $data['is_primary'] = $record->pivot->is_primary ?? false;
                        return $data;
                    })
                    ->using(function ($record, array $data) {
                        $this->getOwnerRecord()->photos()->updateExistingPivot(
                            $record->id,
                            [
                                'order' => $data['order'] ?? 0,
                                'is_primary' => $data['is_primary'] ?? false,
                            ]
                        );
                        $record->update([
                            'name' => $data['name'],
                            'description' => $data['description'] ?? null,
                        ]);
                        return $record;
                    }),
                Tables\Actions\DetachAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('pivot.order')
            ->emptyStateHeading('Nenhuma foto cadastrada')
            ->emptyStateDescription('Adicione fotos para exibir na vitrine da loja')
            ->emptyStateIcon('heroicon-o-photo');
    }
}
