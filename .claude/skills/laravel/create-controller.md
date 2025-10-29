---
name: laravel-create-controller
description: "Create Laravel controllers with Inertia responses for TanaVitrine. Use when creating new controllers, API endpoints, or page handlers following project conventions."
---

# Create Laravel Controller

## Instructions

1. **Generate controller**:
```bash
./vendor/bin/sail artisan make:controller [ControllerName]
```

2. **Structure controller**:
   - Add `declare(strict_types=1);` at top
   - Use `final class` when appropriate
   - Return Inertia responses for pages
   - Use route model binding
   - Check team authorization
   - Add proper type hints

3. **Return Inertia page**:
```php
return Inertia::render('PageName', [
    'data' => $data,
]);
```

4. **Add route** in `routes/web.php`:
```php
Route::get('/path', [ControllerName::class, 'method'])->name('route.name');
```

## Project Conventions
- Use invokable controllers for single-action endpoints
- Check team ownership with policies
- Use resource controllers for CRUD operations
- Always use route model binding with slugs for teams
- Use form requests for validation

## Examples

### Dashboard Controller with Team Check
```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class StoreController extends Controller
{
    public function show(Team $team): Response
    {
        $this->authorize('view', $team);

        return Inertia::render('StoreDetail', [
            'store' => $team->load(['photos', 'category']),
            'similar' => Team::where('category_id', $team->category_id)
                ->where('id', '!=', $team->id)
                ->limit(4)
                ->get(),
        ]);
    }

    public function update(Request $request, Team $team)
    {
        $this->authorize('update', $team);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $team->update($validated);

        return redirect()->route('dashboard.stores.edit', $team->slug)
            ->with('success', 'Store updated');
    }
}
```

### Invokable Controller
```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Team;
use Inertia\Inertia;
use Inertia\Response;

final class WelcomeController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Welcome', [
            'featured' => Team::featured()->limit(6)->get(),
            'categories' => Category::all(),
        ]);
    }
}
```
