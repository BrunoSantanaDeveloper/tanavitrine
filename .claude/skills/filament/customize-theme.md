---
name: filament-customize-theme
description: "Customize Filament admin panel appearance for TanaVitrine. Use when updating colors, fonts, or branding. WARNING: Do NOT use custom CSS themes - they break layouts."
---

# Customize Filament Admin Theme

## ⚠️ CRITICAL: Do NOT Use Custom CSS Theme

**TanaVitrine uses TailwindCSS v4** which **CONFLICTS** with Filament's TailwindCSS v3 preset.

Creating a `theme.css` file that processes through Tailwind **BREAKS** Filament layouts (grid, forms, spacing).

## ✅ Correct Approach: Built-in Customization Only

Location: `app/Providers/Filament/AdminPanelProvider.php`

```php
use Filament\Support\Colors\Color;

public function panel(Panel $panel): Panel
{
    return $panel
        ->colors([
            'primary' => Color::Amber,  // Matches main app
        ])
        ->font('Outfit')                // Matches main app
        ->brandName('TanaVitrine Admin')
        ->favicon(asset('favicon.ico'))
        ->darkMode(true)
        ->sidebarCollapsibleOnDesktop()
        // DO NOT add ->viteTheme() here!
}
```

## Available Customizations

### Colors
```php
->colors([
    'primary' => Color::Amber,
    'danger' => Color::Red,
    'success' => Color::Green,
])
```

**Available:** Slate, Gray, Zinc, Red, Orange, Amber, Yellow, Green, Emerald, Teal, Cyan, Blue, Indigo, Violet, Purple, Pink, Rose

### Font
```php
->font('Outfit')  // Google Fonts loaded automatically
```

### Branding
```php
->brandName('TanaVitrine Admin')
->brandLogo(asset('images/logo.svg'))
->favicon(asset('favicon.ico'))
```

### Features
```php
->darkMode(true)
->sidebarCollapsibleOnDesktop()
->maxContentWidth('7xl')
```

## Why Custom CSS Breaks Everything

1. **TailwindCSS v3 vs v4 conflict**
2. **Grid classes get overridden**
3. **Universal selectors break spacing**
4. **Form layouts collapse**

## What You Get Without Custom Theme

✅ Professional Filament design
✅ Custom colors (Amber)
✅ Custom font (Outfit)
✅ Dark mode
✅ All layouts working perfectly

## Related Files

- [AdminPanelProvider.php](app/Providers/Filament/AdminPanelProvider.php)
