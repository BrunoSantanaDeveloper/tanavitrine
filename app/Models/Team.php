<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\TeamFactory;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamUpdated;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Jetstream\Team as JetstreamTeam;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\QueryException;
use App\Models\Plan;
use App\Models\Category;
use App\Models\Media;
use App\Models\TeamView;
use App\Traits\HasPlanLimits;
use App\Services\SubscriptionAccessRuleService;
use Illuminate\Support\Str;
/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property bool $personal_team
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User|null $owner
 * @property-read Collection<int, TeamInvitation> $teamInvitations
 * @property-read int|null $team_invitations_count
 * @property-read Membership|null $membership
 * @property-read Collection<int, User> $users
 * @property-read int|null $users_count
 *
 * @method static \Database\Factories\TeamFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team wherePersonalTeam($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereUserId($value)
 *
 * @mixin \Eloquent
 */
final class Team extends JetstreamTeam
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory, HasPlanLimits;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'personal_team',
        'plan_id',
        'description',
        'video_url',
        'sale_type',
        'store_type',
        'category_id',
        'subcategory',
        'gender',
        'is_manufacturer',
        'min_order',
        'whatsapp',
        'phone',
        'email',
        'website',
        'instagram',
        'facebook',
        'tiktok',
        'address',
        'address_number',
        'address_complement',
        'google_maps_url',
        'city',
        'state',
        'zip_code',
        'latitude',
        'longitude',
        'is_verified',
        'featured',
        'featured_until',
        'status',
    ];

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => TeamCreated::class,
        'updated' => TeamUpdated::class,
        'deleted' => TeamDeleted::class,
    ];

    /**
     * {@inheritdoc}
     *
     * @return HasMany<TeamInvitation, covariant $this>
     */
    public function teamInvitations(): HasMany
    {
        return parent::teamInvitations();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'personal_team' => 'boolean',
            'is_verified' => 'boolean',
            'is_manufacturer' => 'boolean',
            'featured' => 'boolean',
            'featured_until' => 'datetime',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'views_count' => 'integer',
            'whatsapp_clicks' => 'integer',
            'website_clicks' => 'integer',
            'phone_clicks' => 'integer',
            'map_clicks' => 'integer',
            'shares_count' => 'integer',
            'instagram_clicks' => 'integer',
            'facebook_clicks' => 'integer',
            'tiktok_clicks' => 'integer',
            'subcategory' => 'array',
        ];
    }

    public function playlists(): HasMany
    {
        return $this->hasMany(Playlist::class);
    }

    public function displays(): HasMany
    {
        return $this->hasMany(Display::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    public function collections(): HasMany
    {
        return $this->hasMany(TeamCollection::class)->orderBy('sort_order');
    }

    /**
     * Get the leads for the store.
     */
    public function leads(): HasMany
    {
        return $this->hasMany(StoreLead::class);
    }

    /**
     * Get the view events for the store.
     */
    public function viewEvents(): HasMany
    {
        return $this->hasMany(TeamView::class);
    }

    /**
     * Get the plan that the team belongs to.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the current active plan for this team.
     *
     * Priority:
     * 1. If admin manually assigned a plan (plan_id), use it
     * 2. Otherwise, check if owner has active subscription
     * 3. Fallback to default free plan
     */
    public function currentPlan(): ?Plan
    {
        // Se admin definiu um plano manualmente, use ele (prioridade)
        if ($this->plan_id) {
            return $this->plan ?? Plan::getDefaultPlan();
        }

        // Caso contrário, verifica se o owner tem subscription ativa
        $subscription = $this->owner?->subscription('default');

        if (!$subscription || !$subscription->active()) {
            return Plan::getDefaultPlan();
        }

        // Busca o plano pela subscription do Stripe
        return Plan::where('stripe_price_id', $subscription->stripe_price)->first()
            ?? Plan::getDefaultPlan();
    }

    /**
     * Get the category that the team belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the photos/media for the store through pivot table.
     */
    public function photos(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'team_media')
            ->withPivot('order', 'is_primary')
            ->withTimestamps()
            ->orderBy('team_media.order');
    }

    /**
     * Get users who favorited this store.
     */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')
            ->withTimestamps();
    }

    /**
     * Scope a query to only include featured stores.
     */
    public function scopeFeatured($query)
    {
        return $query->where('featured', true)
            ->where(function($q) {
                $q->whereNull('featured_until')
                  ->orWhere('featured_until', '>', now());
            });
    }

    /**
     * Scope a query to only include active stores.
     */
    public function scopeActive($query)
    {
        $query->where('status', 'ativo');

        $hideStoreWhenExpired = app(SubscriptionAccessRuleService::class)->shouldHideStoreWhenExpired();
        if (!$hideStoreWhenExpired) {
            return $query;
        }

        return $query->whereHas('owner.subscriptions', function ($subscriptionQuery) {
            $subscriptionQuery
                ->where('name', 'default')
                // "trialing" também representa assinatura válida para acesso público.
                ->whereIn('stripe_status', ['active', 'trialing'])
                ->where(function ($trialQuery) {
                    $trialQuery->whereNull('trial_ends_at')
                        ->orWhere('trial_ends_at', '>=', now())
                        ->orWhere(function ($extensionQuery) {
                            $extensionQuery->whereNotNull('discount_ends_at')
                                ->where('discount_ends_at', '>=', now());
                        });
                })
                ->where(function ($endsAtQuery) {
                    $endsAtQuery->whereNull('ends_at')
                        ->orWhere('ends_at', '>=', now());
                });
        });
    }

    /**
     * Scope a query to only include atacado stores.
     */
    public function scopeAtacado($query)
    {
        return $query->whereIn('sale_type', ['atacado', 'ambos']);
    }

    /**
     * Scope a query to only include varejo stores.
     */
    public function scopeVarejo($query)
    {
        return $query->whereIn('sale_type', ['varejo', 'ambos']);
    }

    /**
     * Filter for stores with plans that allow showing on map.
     * Only show stores that have an active subscription with show_on_map enabled.
     */
    public function scopeShowOnMap($query)
    {
        return $query->whereHas('plan', function ($planQuery) {
            $planQuery->where('show_on_map', true);
        });
    }

    /**
     * Scope a query to filter by location.
     */
    public function scopeByLocation($query, $state = null, $city = null)
    {
        if ($state) {
            $query->where('state', $state);
        }
        if ($city) {
            $query->where('city', $city);
        }
        return $query;
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Increment views count.
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');

        try {
            $this->viewEvents()->create([
                'viewed_at' => now(),
            ]);
        } catch (QueryException $exception) {
            report($exception);
        }
    }

    /**
     * Increment WhatsApp clicks count.
     */
    public function incrementWhatsappClicks(): void
    {
        $this->increment('whatsapp_clicks');
    }

    /**
     * Increment website clicks count.
     */
    public function incrementWebsiteClicks(): void
    {
        $this->increment('website_clicks');
    }

    /**
     * Increment phone clicks count.
     */
    public function incrementPhoneClicks(): void
    {
        $this->increment('phone_clicks');
    }

    /**
     * Increment map clicks count.
     */
    public function incrementMapClicks(): void
    {
        $this->increment('map_clicks');
    }

    /**
     * Increment shares count.
     */
    public function incrementShares(): void
    {
        $this->increment('shares_count');
    }

    /**
     * Increment Instagram clicks count.
     */
    public function incrementInstagramClicks(): void
    {
        $this->increment('instagram_clicks');
    }

    /**
     * Increment Facebook clicks count.
     */
    public function incrementFacebookClicks(): void
    {
        $this->increment('facebook_clicks');
    }

    /**
     * Increment TikTok clicks count.
     */
    public function incrementTikTokClicks(): void
    {
        $this->increment('tiktok_clicks');
    }

    /**
     * Check if store is currently featured.
     */
    public function isFeatured(): bool
    {
        if (!$this->featured) {
            return false;
        }

        if ($this->featured_until === null) {
            return true;
        }

        return $this->featured_until->isFuture();
    }

    /**
     * Check if store is verified without assuming the attribute is always hydrated.
     */
    public function isVerified(): bool
    {
        return (bool) ($this->getAttributes()['is_verified'] ?? false);
    }

    /**
     * Check if store can upload more photos based on plan limits.
     */
    public function canUploadPhotos(int $count = 1): bool
    {
        $currentCount = $this->photos()->count();
        $limit = $this->plan?->getModuleLimit('store', 'photos_per_vitrine') ?? 3;

        if ($limit < 0) {
            return true;
        }

        return ($currentCount + $count) <= $limit;
    }

    /**
     * Generate slug from name.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($team) {
            if (empty($team->slug)) {
                $team->slug = Str::slug($team->name);

                // Ensure unique slug
                $count = 1;
                while (static::where('slug', $team->slug)->exists()) {
                    $team->slug = Str::slug($team->name) . '-' . $count;
                    $count++;
                }
            }
        });
    }
}
