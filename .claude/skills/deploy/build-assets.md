---
name: build-assets
description: "Build and optimize frontend assets for TanaVitrine. Use when deploying, compiling Vue/Vite assets, Filament theme, or preparing for production."
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
- Filament admin theme CSS
- All TailwindCSS styles

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
- **Entry points**:
  - `resources/js/app.js` (main app)
  - `resources/css/filament/admin/theme.css` (Filament admin theme)

## Build Outputs

After running `npm run build`, check for:

```
public/build/
├── manifest.json
├── assets/
│   ├── app-[hash].js        # Main Vue app
│   ├── app-[hash].css       # Main app styles
│   └── theme-[hash].css     # Filament admin theme
```

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

### Filament theme not updating

1. Clear Laravel caches:
```bash
php artisan view:clear
php artisan config:clear
```

2. Force rebuild without cache:
```bash
rm -rf public/build
./vendor/bin/sail npm run build
```

3. Hard refresh browser (Ctrl+Shift+R or Cmd+Shift+R)

## Related Skills

- Filament Theme Customization: `.claude/skills/filament/customize-theme.md`
- Filament Widgets: `.claude/skills/filament/create-widget.md`
