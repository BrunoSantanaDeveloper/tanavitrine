---
name: build-assets
description: "Build and optimize frontend assets for TanaVitrine. Use when deploying, compiling Vue/Vite assets, or preparing for production."
---

# Build Frontend Assets

## Instructions

### Development Mode

Start dev server with HMR (Hot Module Replacement):

```bash
./vendor/bin/sail npm run dev
```

### Production Build

Build optimized assets for production:

```bash
./vendor/bin/sail npm run build
```

This compiles:
- Main Vue 3 + Inertia.js app
- All TailwindCSS v4 styles

### Linting

Check and fix code style:

```bash
./vendor/bin/sail npm run lint        # Check only
./vendor/bin/sail npm run lint:fix    # Auto-fix
```

## Configuration

- **Vite config**: `vite.config.js`
- **Dev server port**: 5174 (not default 5173)
- **Output**: `public/build/`
- **Entry point**: `resources/js/app.js`

## Build Outputs

After running `npm run build`, check for:

```
public/build/
├── manifest.json
├── assets/
│   ├── app-[hash].js        # Main Vue app
│   └── app-[hash].css       # Main app styles
```

## Filament Admin

**Filament uses its own asset compilation.** It does NOT use the Vite build process.

Filament styling is handled internally and configured via:
- `app/Providers/Filament/AdminPanelProvider.php`
- No custom CSS files needed

## Common Issues

### Port already in use

```bash
./vendor/bin/sail npm run dev -- --port 5175
```

### Clear cache and rebuild

```bash
rm -rf node_modules public/build
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

### Filament admin styles not updating

Filament compiles its own assets. If admin styles seem cached:

```bash
# Clear Laravel caches
php artisan view:clear
php artisan config:clear

# Hard refresh browser (Ctrl+Shift+R or Cmd+Shift+R)
```

## Related Skills

- Filament Theme Customization: `.claude/skills/filament/customize-theme.md`
- Filament Widgets: `.claude/skills/filament/create-widget.md`
