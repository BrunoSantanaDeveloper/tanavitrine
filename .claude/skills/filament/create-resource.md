---
name: filament-create-resource
description: "Create Filament admin resources for TanaVitrine. Use when creating admin panels, CRUD interfaces, or managing models via Filament admin. Includes patterns for navigation, forms, tables, filters, and complex relationships."
---

# Create Filament Admin Resource

## Instructions

1. **Generate Filament Resource**:

   ```bash
   ./vendor/bin/sail artisan make:filament-resource [ModelName] --generate
   ```

   Options:
   - `--generate`: Auto-generate form/table from model
   - `--simple`: Create simple resource without separate pages
   - `--view`: Add view page

2. **Configure Resource Navigation**:
   - Set navigation icon (Heroicons)
   - Set navigation group for organization
   - Add navigation badge for counts
   - Set navigation sort order

3. **Define Form Schema**:
   - Use Sections to organize fields
   - Add validation rules
   - Configure columns layout
   - Use Repeaters for complex relationships
   - Add helper text for guidance

4. **Define Table Columns**:
   - Make important fields searchable
   - Add sortable columns
   - Use toggleable for optional columns
   - Add custom state columns when needed
   - Use badges and icons for visual feedback

5. **Add Filters and Actions**:
   - Date range filters
   - Boolean filters
   - Custom query filters
   - Soft delete filters (TrashedFilter)

## Project Conventions

### Code Style

- Use `declare(strict_types=1);` at the top
- Use `final class` when appropriate (not always enforced)
- Set `protected static ?string $navigationGroup` to organize menu
- Add `getNavigationBadge()` for count badges

### Navigation Groups

- **User Management**: Users, teams
- **Subscription Management**: Plans, intervals
- **Billing**: Subscriptions, payments
- **Gerenciamento**: Teams/stores, content management

### Authentication

Access control via `User::canAccessPanel()`:

- User must have a `currentTeam`
- User must have 'admin' role in current team
- Implemented in [User.php:171](app/Models/User.php#L171)

### Key Packages

- `filament/filament: ^3.2`
- `maartenpaauw/filament-cashier-billing-provider: ^2.2`
- Billing provider configured in [AdminPanelProvider:67](app/Providers/Filament/AdminPanelProvider.php#L67)

## Examples

### Simple Resource with Navigation Badge

```php
<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\UserResource\Pages;

final class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 1;

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
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter full name'),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('email@example.com'),
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
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
        ];
    }
}
```

### Complex Resource with Repeaters and Soft Deletes

```php
<?php

namespace App\Filament\Resources;

use App\Models\Plan;
use App\Models\Interval;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PlanResource\Pages;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Subscription Management';

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
                    ])->columns(2),

                Forms\Components\Section::make('Plan Intervals')
                    ->schema([
                        Forms\Components\Repeater::make('intervals')
                            ->schema([
                                Forms\Components\Select::make('interval_id')
                                    ->label('Interval')
                                    ->options(Interval::pluck('name', 'id'))
                                    ->required()
                                    ->searchable(),
                                Forms\Components\TextInput::make('price')
                                    ->required()
                                    ->numeric()
                                    ->prefix('R$'),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}
```

### Custom Table Column with Copyable State

```php
Tables\Columns\TextColumn::make('registration_link')
    ->label('Registration Link')
    ->state(function ($record) {
        return route('register', ['plan' => $record->id]);
    })
    ->copyable()
    ->copyMessage('Link copied!')
    ->copyMessageDuration(2000)
    ->icon('heroicon-o-clipboard'),
```

### Date Range Filter

```php
Tables\Filters\Filter::make('created_at')
    ->form([
        Forms\Components\DatePicker::make('created_from'),
        Forms\Components\DatePicker::make('created_until'),
    ])
    ->query(fn (Builder $query, array $data): Builder =>
        $query
            ->when(
                $data['created_from'] ?? null,
                fn (Builder $query, $date) =>
                    $query->whereDate('created_at', '>=', $date)
            )
            ->when(
                $data['created_until'] ?? null,
                fn (Builder $query, $date) =>
                    $query->whereDate('created_at', '<=', $date)
            )
    ),
```

## Advanced Patterns

### ActionGroup - Dropdown Menu for Table Actions

Use `ActionGroup` to organize multiple actions in a compact dropdown menu:

```php
->actions([
    Tables\Actions\ActionGroup::make([
        Tables\Actions\Action::make('view')
            ->label('Ver')
            ->icon('heroicon-o-eye')
            ->color('info')
            ->url(fn ($record) => route('item.show', $record))
            ->openUrlInNewTab(),
        Tables\Actions\EditAction::make()
            ->label('Editar')
            ->icon('heroicon-o-pencil'),
        Tables\Actions\Action::make('toggle_status')
            ->label(fn ($record) => $record->active ? 'Desativar' : 'Ativar')
            ->icon(fn ($record) => $record->active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
            ->color(fn ($record) => $record->active ? 'danger' : 'success')
            ->requiresConfirmation()
            ->action(fn ($record) => $record->update(['active' => !$record->active])),
        Tables\Actions\DeleteAction::make(),
    ])
        ->label('Ações')
        ->icon('heroicon-m-ellipsis-vertical')
        ->size('sm')
        ->color('gray')
        ->button(),
])
```

### Tabs for List Pages

Add tabs to filter records in list pages:

```php
// In ListResource page
public function getTabs(): array
{
    return [
        'all' => Tab::make('Todas')
            ->badge(Team::count()),
        'active' => Tab::make('Ativas')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'ativo'))
            ->badge(Team::where('status', 'ativo')->count())
            ->badgeColor('success'),
        'inactive' => Tab::make('Inativas')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'inativo'))
            ->badge(Team::where('status', 'inativo')->count())
            ->badgeColor('danger'),
    ];
}
```

### RelationManagers

Create relation managers to manage related records:

```bash
php artisan make:filament-relation-manager TeamResource photos name
```

Example RelationManager:

```php
final class PhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'photos';
    protected static ?string $title = 'Fotos';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('path')
                    ->label('Foto')
                    ->height(80),
                Tables\Columns\TextColumn::make('name'),
            ])
            ->reorderable('order') // Enable drag-and-drop ordering
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }
}
```

Register in Resource:

```php
public static function getRelations(): array
{
    return [
        RelationManagers\PhotosRelationManager::class,
    ];
}
```

### Custom Edit Page Actions

Add custom actions to edit page header:

```php
// In EditResource page
protected function getHeaderActions(): array
{
    return [
        Actions\Action::make('view')
            ->label('Ver')
            ->icon('heroicon-o-eye')
            ->url(fn ($record) => route('item.show', $record))
            ->openUrlInNewTab(),
        Actions\Action::make('toggle_status')
            ->label(fn ($record) => $record->active ? 'Desativar' : 'Ativar')
            ->action(fn ($record) => $record->update(['active' => !$record->active]))
            ->requiresConfirmation(),
        Actions\DeleteAction::make(),
    ];
}
```

### Custom Notifications

Customize success notifications:

```php
// In CreateRecord page
protected function getCreatedNotification(): ?Notification
{
    return Notification::make()
        ->success()
        ->title('Item criado com sucesso!')
        ->body('Agora você pode adicionar mais detalhes.')
        ->duration(5000);
}

// In EditRecord page
protected function getSavedNotification(): ?Notification
{
    return Notification::make()
        ->success()
        ->title('Alterações salvas!')
        ->duration(3000);
}
```

### Mutate Data Before Save

Transform data before creating or updating:

```php
// In CreateRecord page
protected function mutateFormDataBeforeCreate(array $data): array
{
    $data['user_id'] = auth()->id();
    $data['status'] = $data['status'] ?? 'active';
    return $data;
}

// In EditRecord page
protected function mutateFormDataBeforeSave(array $data): array
{
    if (isset($data['featured']) && !$data['featured']) {
        $data['featured_until'] = null;
    }
    return $data;
}
```

### Toggle Columns (Hidden by Default)

Hide columns by default but allow users to show them:

```php
Tables\Columns\TextColumn::make('city')
    ->searchable()
    ->toggleable(isToggledHiddenByDefault: true),
```

### Conditional Actions

Show actions based on record state:

```php
Tables\Actions\Action::make('feature')
    ->visible(fn ($record) => $record->status === 'active'),
```

### Badge Colors with Match Expression

Use match for clean badge colors:

```php
Tables\Columns\TextColumn::make('status')
    ->badge()
    ->color(fn (string $state): string => match ($state) {
        'ativo' => 'success',
        'inativo' => 'danger',
        'pendente' => 'warning',
        default => 'gray',
    }),
```

## Common Tasks

### Access Admin Panel

Navigate to: `/admin`

**Requires**:

- Authenticated user
- User has current team
- User has 'admin' role in current team

### Create New Resource

```bash
php artisan make:filament-resource Product
# or with auto-generation (requires database connection)
php artisan make:filament-resource Product --generate
```

### Create RelationManager

```bash
php artisan make:filament-relation-manager ProductResource reviews rating
```

### Publish Assets

```bash
php artisan filament:assets
```

### Clear Cache

```bash
php artisan filament:cache-components
php artisan view:clear
```

## Related Files

- Panel Provider: [AdminPanelProvider.php](app/Providers/Filament/AdminPanelProvider.php)
- User Access Control: [User.php:171](app/Models/User.php#L171)
- Config: [filament.php](config/filament.php)
- Example Resources:
  - Simple: [UserResource.php](app/Filament/Resources/UserResource.php)
  - Complex: [PlanResource.php](app/Filament/Resources/PlanResource.php)
  - Advanced: [TeamResource.php](app/Filament/Resources/TeamResource.php) - with ActionGroup, tabs, RelationManagers
