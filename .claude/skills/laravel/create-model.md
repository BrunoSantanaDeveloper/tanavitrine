---
name: laravel-create-model
description: "Create Laravel models with migrations and factories for TanaVitrine. Use when creating new database models, tables, or entities following project conventions with strict types and team relationships."
---

# Create Laravel Model with Migration

## Instructions

1. Run artisan command to create model with migration and factory:

   ```bash
   ./vendor/bin/sail artisan make:model [ModelName] -mf
   ```

2. Edit the migration file in `database/migrations/` to add:
   - Table columns with appropriate types
   - Foreign keys if needed
   - Indexes for performance
   - Soft deletes if applicable

3. Edit the model file in `app/Models/` to add:
   - Fillable/guarded properties
   - Relationships (belongsTo, hasMany, etc.)
   - Casts for dates, JSON, etc.
   - Scopes if needed
   - Model events (boot method) if needed

4. Update the factory in `database/factories/` with realistic fake data

5. Run migration:

   ```bash
   ./vendor/bin/sail artisan migrate
   ```

## Project Conventions

- Always use `declare(strict_types=1);` at the top
- Use `final class` when possible
- Add `HasFactory` trait
- For team-scoped models, add `team_id` foreign key
- Add proper type hints for relationships
- Add query scopes for common filters

## Examples

### Model with team relationship

```php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'name',
        'description',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
```
