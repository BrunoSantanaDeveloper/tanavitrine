<?php

declare(strict_types=1);

namespace App\Filament\Resources\TeamResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

final class PhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'photos';

    protected static ?string $title = 'Fotos em Destaque';

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
                Forms\Components\Toggle::make('is_primary')
                    ->label('Foto Principal')
                    ->helperText('Marcar como foto de capa da loja'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Ativa')
                    ->default(true)
                    ->helperText('Fotos inativas não aparecem no site'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->whereNull('media.team_collection_id')
                    ->where(function (Builder $subQuery) {
                        $subQuery->whereNull('media.category')
                            ->orWhere('media.category', '!=', 'logo');
                    });
            })
            ->recordClasses(fn ($record): string => ($record->pivot?->is_primary ?? false) ? 'tv-primary-photo-locked' : '')
            ->defaultSort('team_media.order')
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
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('team_media.order', $direction)),
                Tables\Columns\IconColumn::make('pivot.is_primary')
                    ->label('Principal')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Ativa'),
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
            ->filters([
                Tables\Filters\TernaryFilter::make('pivot.is_primary')
                    ->label('Foto Principal')
                    ->placeholder('Todas')
                    ->trueLabel('Apenas principal')
                    ->falseLabel('Não principal'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $sizeInMb = 0.0;
                        if (!empty($data['path']) && Storage::disk('public')->exists($data['path'])) {
                            $sizeInMb = Storage::disk('public')->size($data['path']) / 1024 / 1024;
                        }

                        $data['type'] = 'image';
                        $data['team_id'] = $this->getOwnerRecord()->id;
                        $data['team_collection_id'] = null;
                        $data['category'] = 'highlight';
                        $data['is_generic'] = false;
                        $data['size'] = $sizeInMb;
                        $data['is_active'] = (bool) ($data['is_active'] ?? true);
                        return $data;
                    })
                    ->after(function ($record, $data) {
                        $nextOrder = ((int) $this->getOwnerRecord()->photos()->max('team_media.order')) + 1;

                        $this->getOwnerRecord()->photos()->updateExistingPivot(
                            $record->id,
                            [
                                'order' => $nextOrder,
                                'is_primary' => $data['is_primary'] ?? false,
                            ]
                        );
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
                        $owner = $this->getOwnerRecord();

                        $orderedIds = $owner->photos()
                            ->where('media.id', '!=', $record->id)
                            ->pluck('media.id')
                            ->values();

                        // Move selected photo to position 1 and mark as primary.
                        $owner->photos()->updateExistingPivot($record->id, [
                            'is_primary' => true,
                            'order' => 1,
                        ]);

                        // Reindex remaining photos after the primary one.
                        foreach ($orderedIds as $index => $photoId) {
                            $owner->photos()->updateExistingPivot((int) $photoId, [
                                'is_primary' => false,
                                'order' => $index + 2,
                            ]);
                        }
                    }),
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(fn (array $data, $record): array => [
                        ...$data,
                        'is_primary' => $record->pivot->is_primary ?? false,
                    ])
                    ->using(function ($record, array $data) {
                        $this->getOwnerRecord()->photos()->updateExistingPivot(
                            $record->id,
                            [
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
            ->reorderable('order')
            ->emptyStateHeading('Nenhuma foto de destaque cadastrada')
            ->emptyStateDescription('Adicione fotos para exibir no topo da vitrine')
            ->emptyStateIcon('heroicon-o-photo');
    }

    /**
     * Keep the primary photo locked in first position during drag-and-drop reorder.
     *
     * @param array<int|string> $order
     */
    public function reorderTable(array $order): void
    {
        if (! $this->getTable()->isReorderable()) {
            return;
        }

        $owner = $this->getOwnerRecord();

        $primaryPhotoId = $owner->photos()
            ->wherePivot('is_primary', true)
            ->value('media.id');

        $orderedIds = collect($order)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->values();

        if ($primaryPhotoId) {
            $primaryPhotoId = (int) $primaryPhotoId;

            $orderedIds = $orderedIds
                ->reject(fn (int $id) => $id === $primaryPhotoId)
                ->prepend($primaryPhotoId)
                ->values();
        }

        foreach ($orderedIds as $index => $photoId) {
            $owner->photos()->updateExistingPivot($photoId, [
                'order' => $index + 1,
            ]);
        }
    }
}
