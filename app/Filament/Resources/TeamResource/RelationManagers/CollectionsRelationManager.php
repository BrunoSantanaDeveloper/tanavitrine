<?php

declare(strict_types=1);

namespace App\Filament\Resources\TeamResource\RelationManagers;

use App\Models\Media;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

final class CollectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'collections';

    protected static ?string $title = 'Coleções';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nome da Coleção')
                    ->required()
                    ->maxLength(120),
                Forms\Components\Textarea::make('description')
                    ->label('Descrição')
                    ->maxLength(500)
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Placeholder::make('collection_photos_preview')
                    ->label('Fotos da Coleção')
                    ->content(function ($record) {
                        if (!$record) {
                            return 'As fotos poderão ser visualizadas após criar a coleção.';
                        }

                        $photos = $record->media()
                            ->orderByDesc('id')
                            ->get();

                        if ($photos->isEmpty()) {
                            return 'Nenhuma foto cadastrada nesta coleção.';
                        }

                        $items = $photos->map(function ($photo) {
                            $url = Storage::disk('public')->url($photo->path);
                            $name = e($photo->name ?: ('Foto #' . $photo->id));

                            return <<<HTML
<div style="position:relative;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;background:#f9fafb;">
  <img src="{$url}" alt="{$name}" style="display:block;width:100%;height:120px;object-fit:cover;">
  <div style="padding:6px 8px;font-size:11px;line-height:1.3;color:#374151;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{$name}</div>
</div>
HTML;
                        })->implode('');

                        return new HtmlString('<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;">' . $items . '</div>');
                    })
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_featured')
                    ->label('Coleção em Destaque')
                    ->helperText('Apenas uma coleção pode ficar em destaque'),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Ordem')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\ImageColumn::make('cover_photo')
                    ->label('Capa')
                    ->disk('public')
                    ->circular()
                    ->height(56)
                    ->getStateUsing(function ($record) {
                        $cover = $record->media()->latest('id')->first();
                        return $cover?->path;
                    })
                    ->defaultImageUrl(url('/images/og.webp')),
                Tables\Columns\TextColumn::make('name')
                    ->label('Coleção')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Destaque')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('media_count')
                    ->label('Fotos')
                    ->counts('media')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Ordem')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criada em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        if (!isset($data['sort_order'])) {
                            $data['sort_order'] = (int) $this->getOwnerRecord()->collections()->max('sort_order') + 1;
                        }
                        return $data;
                    })
                    ->after(function ($record): void {
                        if ($record->is_featured) {
                            $this->getOwnerRecord()->collections()
                                ->where('id', '!=', $record->id)
                                ->update(['is_featured' => false]);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('set_featured')
                    ->label('Definir destaque')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->visible(fn ($record): bool => !$record->is_featured)
                    ->requiresConfirmation()
                    ->action(function ($record): void {
                        $this->getOwnerRecord()->collections()->update(['is_featured' => false]);
                        $record->update(['is_featured' => true]);
                    }),
                Tables\Actions\EditAction::make()
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome da Coleção')
                            ->required()
                            ->maxLength(120),
                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->maxLength(500)
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Repeater::make('media')
                            ->label('Fotos da Coleção')
                            ->relationship('media')
                            ->columnSpanFull()
                            ->grid(4)
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->schema([
                                Forms\Components\FileUpload::make('path')
                                    ->label('Foto')
                                    ->image()
                                    ->disk('public')
                                    ->directory(fn () => 'stores/store_' . $this->getOwnerRecord()->id . '/collections')
                                    ->required(),
                                Forms\Components\TextInput::make('name')
                                    ->label('Nome da Foto')
                                    ->maxLength(255),
                            ])
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                $sizeInKb = 0.0;
                                if (!empty($data['path']) && Storage::disk('public')->exists($data['path'])) {
                                    $sizeInKb = Storage::disk('public')->size($data['path']) / 1024;
                                }

                                $data['team_id'] = $this->getOwnerRecord()->id;
                                $data['type'] = 'image';
                                $data['size'] = $sizeInKb;
                                $data['is_generic'] = false;
                                $data['category'] = 'collection';
                                $data['name'] = $data['name'] ?? basename((string) $data['path']);

                                return $data;
                            })
                            ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                                $sizeInKb = 0.0;
                                if (!empty($data['path']) && Storage::disk('public')->exists($data['path'])) {
                                    $sizeInKb = Storage::disk('public')->size($data['path']) / 1024;
                                }

                                $data['team_id'] = $this->getOwnerRecord()->id;
                                $data['type'] = 'image';
                                $data['size'] = $sizeInKb;
                                $data['is_generic'] = false;
                                $data['category'] = 'collection';
                                $data['name'] = $data['name'] ?? basename((string) $data['path']);

                                return $data;
                            }),
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Coleção em Destaque')
                            ->helperText('Apenas uma coleção pode ficar em destaque'),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Ordem')
                            ->numeric()
                            ->default(0),
                    ])
                    ->successNotificationTitle('Coleção atualizada com sucesso')
                    ->after(function ($record): void {
                        if ($record->is_featured) {
                            $this->getOwnerRecord()->collections()
                                ->where('id', '!=', $record->id)
                                ->update(['is_featured' => false]);
                        }
                    }),
                Tables\Actions\DeleteAction::make()
                    ->before(function ($record): void {
                        $record->media()->each(function (Media $media): void {
                            if (Storage::disk('public')->exists($media->path)) {
                                Storage::disk('public')->delete($media->path);
                            }
                            $media->delete();
                        });
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('sort_order')
            ->emptyStateHeading('Nenhuma coleção cadastrada')
            ->emptyStateDescription('Crie coleções para organizar as fotos da loja')
            ->emptyStateIcon('heroicon-o-rectangle-stack');
    }
}
