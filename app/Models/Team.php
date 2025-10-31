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
use App\Models\Plan;
use App\Models\Category;
use App\Models\Media;
use App\Traits\HasPlanLimits;
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
        'sale_type',
        'store_type',
        'category_id',
        'subcategory',
        'gender',
        'min_order',
        'whatsapp',
        'phone',
        'email',
        'website',
        'instagram',
        'facebook',
        'tiktok',
        'address',
        'city',
        'state',
        'zip_code',
        'latitude',
        'longitude',
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
            'featured' => 'boolean',
            'featured_until' => 'datetime',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'views_count' => 'integer',
            'whatsapp_clicks' => 'integer',
            'website_clicks' => 'integer',
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

    /**
     * Get the leads for the store.
     */
    public function leads(): HasMany
    {
        return $this->hasMany(StoreLead::class);
    }

    /**
     * Get the plan that the team belongs to.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function currentPlan(): ?Plan
    {
        $subscription = $this->activeSubscription();

        if (!$subscription) {
            return Plan::getDefaultPlan();
        }

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
        return $query->where('status', 'ativo');
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
     * Check if store can upload more photos based on plan limits.
     */
    public function canUploadPhotos(int $count = 1): bool
    {
        $currentCount = $this->photos()->count();
        $limit = $this->getPlanLimit('photos_per_vitrine', 3);

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
