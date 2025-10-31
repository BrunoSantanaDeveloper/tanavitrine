---
name: filament-customize-theme
description: "Customize Filament admin panel theme to match TanaVitrine branding. Use when updating colors, fonts, or styling of the admin panel. Includes TailwindCSS v4 configuration and Vite setup."
---

# Customize Filament Admin Theme

## Current Theme Configuration

TanaVitrine admin uses a custom theme that matches the main application design:

- **Fonts**: Outfit (sans), Fira Code (mono)
- **Colors**: OKLCH color space for better color accuracy
- **Primary Color**: Yellow/Amber (matching main app)
- **TailwindCSS**: v4 with custom configuration
- **Build Tool**: Vite

## Theme Files

### 1. CSS Theme File

Location: `resources/css/filament/admin/theme.css`

```css
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&family=Fira+Code:wght@300..700&display=swap');

@import 'tailwindcss';

@config '../../../../tailwind.config.filament.js';

/* Custom color variables using OKLCH */
@layer base {
  :root {
    --background: oklch(0.9816 0.0017 247.8390);
    --foreground: oklch(0.386 0.063 188.416);
    --primary: oklch(0.8790 0.1690 91.6050);
    /* ... more colors ... */
  }

  .dark {
    --background: oklch(0.1649 0.0352 281.8285);
    /* ... dark mode colors ... */
  }
}

@layer base {
  body {
    @apply bg-background text-foreground;
    font-family: Outfit, sans-serif !important;
  }

  .fi-body,
  .fi-sidebar,
  .fi-main {
    font-family: Outfit, sans-serif !important;
  }
}

/* Custom component styling */
@layer components {
  .fi-section,
  .fi-card {
    border-radius: var(--radius);
  }
}
```

### 2. TailwindCSS Config

Location: `tailwind.config.filament.js`

```javascript
import preset from './vendor/filament/support/tailwind.config.preset'
import typography from '@tailwindcss/typography'
import animate from 'tailwindcss-animate'

export default {
  presets: [preset],
  darkMode: ['class'],
  content: [
    './app/Filament/**/*.php',
    './resources/views/filament/**/*.blade.php',
    './resources/css/filament/**/*.css',
    './vendor/filament/**/*.blade.php',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Outfit', 'sans-serif'],
        mono: ['Fira Code', 'monospace'],
      },
      colors: {
        border: 'var(--border)',
        input: 'var(--input)',
        ring: 'var(--ring)',
        background: 'var(--background)',
        foreground: 'var(--foreground)',
        primary: {
          DEFAULT: 'var(--primary)',
          foreground: 'var(--primary-foreground)',
        },
        // ... more color mappings
      },
    },
  },
  plugins: [animate, typography],
}
```

### 3. Vite Configuration

Update `vite.config.js` to include Filament CSS:

```javascript
export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/js/app.js',
        'resources/css/filament/admin/theme.css',  // Add this
      ],
      refresh: [
        'resources/views/**',
        'app/Filament/**',                         // Add this
        'app/Livewire/**',                        // Add this
      ],
    }),
    // ... other plugins
  ],
})
```

### 4. Panel Provider Configuration

Update `app/Providers/Filament/AdminPanelProvider.php`:

```php
return $panel
    ->default()
    ->id('admin')
    ->path('admin')
    ->colors([
        'primary' => Color::Amber,
    ])
    ->font('Outfit')                                           // Custom font
    ->brandName('TanaVitrine Admin')                          // Brand name
    ->favicon(asset('favicon.ico'))
    ->darkMode(true)                                          // Enable dark mode
    ->sidebarCollapsibleOnDesktop()                          // Collapsible sidebar
    ->viteTheme('resources/css/filament/admin/theme.css')    // Custom theme
    // ... rest of configuration
```

## Making Changes

### Update Colors

1. Edit OKLCH color values in `resources/css/filament/admin/theme.css`
2. Find color values at: https://oklch.com
3. Update both light and dark mode colors
4. Rebuild assets: `npm run build`

### Change Fonts

1. Update Google Fonts import in theme.css
2. Update font-family in both CSS and tailwind.config.filament.js
3. Update AdminPanelProvider font setting
4. Rebuild assets

### Add Custom Component Styles

Add to `@layer components` in theme.css:

```css
@layer components {
  .fi-btn {
    border-radius: var(--radius);
    font-weight: 600;
  }

  .fi-input {
    border-radius: calc(var(--radius) - 2px);
  }
}
```

## Build Process

### Development

```bash
./vendor/bin/sail npm run dev
```

### Production

```bash
./vendor/bin/sail npm run build
```

### Clear Caches

```bash
php artisan view:clear
php artisan config:clear
```

## Color Palette (OKLCH)

Current TanaVitrine colors in OKLCH format:

### Light Mode
- Background: `oklch(0.9816 0.0017 247.8390)` - Very light blue-gray
- Foreground: `oklch(0.386 0.063 188.416)` - Dark blue-gray
- Primary: `oklch(0.8790 0.1690 91.6050)` - Yellow/Amber
- Secondary: `oklch(0.3860 0.0630 188.4160)` - Dark teal
- Border: `oklch(0.8940 0.0570 293.2830)` - Light purple-gray

### Dark Mode
- Background: `oklch(0.1649 0.0352 281.8285)` - Very dark purple
- Foreground: `oklch(0.9513 0.0074 260.7315)` - Very light gray
- Primary: `oklch(0.6726 0.2904 341.4084)` - Pink/Magenta
- Accent: `oklch(0.8903 0.1739 171.2690)` - Cyan

## Benefits of OKLCH

- **Perceptually uniform**: Equal steps look equal to human eye
- **Wider gamut**: Access to more vivid colors
- **Better interpolation**: Smoother gradients and transitions
- **Consistent lightness**: Colors with same L value have same perceived brightness

## Troubleshooting

### Theme not loading

1. Check Vite built the theme CSS: `ls -la public/build/assets/theme-*.css`
2. Clear browser cache (Ctrl+Shift+R)
3. Check console for CSS errors
4. Verify viteTheme path in AdminPanelProvider

### Colors not applying

1. Check CSS variable names match between theme.css and tailwind.config
2. Ensure `@config` path is correct in theme.css
3. Rebuild with `npm run build`
4. Clear view cache: `php artisan view:clear`

### Font not loading

1. Check Google Fonts URL in theme.css
2. Verify font name matches in CSS and config files
3. Check browser console for font loading errors
4. Ensure font fallbacks are defined

## Related Files

- Theme CSS: [resources/css/filament/admin/theme.css](resources/css/filament/admin/theme.css)
- Tailwind Config: [tailwind.config.filament.js](tailwind.config.filament.js)
- Vite Config: [vite.config.js](vite.config.js)
- Panel Provider: [app/Providers/Filament/AdminPanelProvider.php](app/Providers/Filament/AdminPanelProvider.php)
- Main App CSS (for reference): [resources/css/app.css](resources/css/app.css)

## Design Consistency

The Filament theme uses the exact same design tokens as the main application:
- Same fonts (Outfit)
- Same color palette (OKLCH values)
- Same border radius (0.5rem)
- Same shadows
- Same spacing

This ensures visual consistency between the public marketplace and admin panel.
