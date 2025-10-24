# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

TanaVitrine is a Laravel 11 + Vue 3 + Inertia.js marketplace platform for fashion retailers to showcase products to wholesale and retail buyers. The application uses Laravel Jetstream for authentication, Stripe/Cashier for subscriptions, and FilamentPHP for admin.

## Development Commands

### With Docker (Laravel Sail)
```bash
# Start development servers
./vendor/bin/sail up -d
./vendor/bin/sail npm run dev

# Run migrations
./vendor/bin/sail artisan migrate

# Run seeders
./vendor/bin/sail artisan migrate --seed

# Install dependencies
./vendor/bin/sail composer install
./vendor/bin/sail npm install
```

### Without Docker
```bash
# Start all development services (server, queue, logs, vite)
composer dev

# Individual services
php artisan serve
php artisan queue:listen --tries=1
php artisan pail --timeout=0
npm run dev
```

### Build & Deploy
```bash
npm run build          # Build frontend assets
vite build            # Alternative build command
```

### Code Quality
```bash
composer analyse      # Run PHPStan analysis
composer test         # Run Pest tests in parallel
composer format       # Run Pint (code formatter) and Rector

npm run lint          # Run ESLint
npm run lint:fix      # Fix ESLint issues
```

### Artisan Commands
```bash
php artisan storage:link      # Link storage for media uploads
php artisan key:generate      # Generate application key
```

## Architecture

### Core Business Model

The `Team` model represents a store/vitrine (not just a Laravel Jetstream team). Key characteristics:
- Each user's "personal team" is their store/vitrine
- Uses `HasPlanLimits` trait to enforce subscription plan limits
- Slug-based routing (`Team::getRouteKeyName()` returns 'slug')
- Tracks analytics: views_count, whatsapp_clicks, website_clicks
- Supports featured/premium stores with `featured` and `featured_until` fields
- Photos relationship via pivot table with ordering and primary image support

### Plan System

Plans are managed via Stripe subscriptions (Laravel Cashier):
- `Plan` model defines available subscription tiers
- `HasPlanLimits` trait provides `getPlanLimit()` to check feature limits
- Default plan limits (e.g., `photos_per_vitrine`) applied when no active subscription
- Plans control: photo uploads, analytics access, featured placement

### Key Models & Relationships

- **Team** (Store/Vitrine): Core entity with media, leads, categories, plans
- **Media**: Photos for stores, linked via `team_media` pivot with ordering
- **StoreLead**: Tracks buyer interest/contact captures
- **Category**: Store categorization (clothing type, etc.)
- **Plan**: Subscription tiers with Stripe integration
- **User**: Jetstream authentication with favorites relationship

### Frontend Architecture

**Stack**: Vue 3 + Inertia.js + TailwindCSS + shadcn/ui components

**Key Pages**:
- `Welcome.vue`: Public marketplace listing with filters
- `Dashboard.vue`: Store owner dashboard
- `StoreDetail.vue`: Public store page with photos, info, contact
- `OnBoarding.vue`: Guided store creation wizard
- Pages under `Dashboard/`: Store management (edit, photos, analytics)

**Key Components**:
- `StoreCard.vue`: Store preview cards used throughout the app
- `AppSidebarContent.vue`: Main navigation sidebar
- `FileUpload.vue`: Handles media uploads
- `shadcn/`: shadcn/ui component library (do not modify these)

**Important**: ESLint config ignores `resources/js/Components/shadcn/**/*` - do not modify shadcn components

### Controllers

- **WelcomeController**: Marketplace homepage with filtering (atacado/varejo, category, location)
- **DashboardController**: Store owner dashboard with analytics
- **DashboardStoreController**: CRUD operations for store management
- **StoreController**: Public store pages, analytics tracking (views/clicks), lead capture
- **OnboardingController**: Guided store creation wizard
- **SubscriptionController**: Stripe subscription management
- **FavoriteController**: Buyer favorites system

### Routes Structure

Routes defined in `routes/web.php`:
- Public: `/`, `/lojas/{team:slug}`, `/atacado`, `/varejo`
- Auth: Laravel Jetstream routes (login, register, password reset)
- Dashboard: `/dashboard/*` (requires authentication)
- Store management: `/dashboard/loja/*`
- Subscriptions: `/assinatura/*`

### Database

Primary tables:
- `teams`: Stores/vitrines (extended Jetstream teams)
- `team_media`: Pivot for store photos with ordering
- `media`: Photo storage with paths
- `plans`: Subscription plan definitions
- `categories`: Store categorization
- `store_leads`: Buyer interest tracking
- `favorites`: Buyer saved stores

## Special Considerations

### Vite Configuration
- Dev server runs on port `5174` (not standard 5173)
- HMR configured for localhost
- Laravel plugin input: `resources/js/app.js`

### Slug Generation
Teams automatically generate unique slugs from name on creation (see `Team::boot()`). Always use slug for public routes.

### Plan Limits
Before allowing feature usage (e.g., photo upload), check plan limits:
```php
$team->canUploadPhotos($count)
$team->getPlanLimit('photos_per_vitrine', $defaultValue)
```

### Analytics Tracking
Store views/clicks tracked via:
- `Team::incrementViews()`
- `Team::incrementWhatsappClicks()`
- `Team::incrementWebsiteClicks()`

### Featured Stores
Use `Team::scopeFeatured()` query scope to filter featured stores. Check `Team::isFeatured()` for individual store status.

### Query Scopes
Common Team scopes: `featured()`, `active()`, `atacado()`, `varejo()`, `byLocation()`, `byCategory()`
