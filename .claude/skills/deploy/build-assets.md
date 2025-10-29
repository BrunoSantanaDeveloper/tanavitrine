---
name: build-frontend
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
