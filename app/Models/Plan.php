<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Plan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'stripe_product_id',
        'stripe_price_id',
        'currency',
        'trial_days',
        'features',
        'is_featured',
        'show_on_map',
        'sort_order',
        'metadata',
        'is_active',
        'is_default',
        'new_user_discount_type',
        'new_user_discount_value',
        'new_user_discount_duration_value',
        'new_user_discount_duration_unit',
    ];

    protected $casts = [
        'features' => 'array',
        'metadata' => 'array',
        'is_featured' => 'boolean',
        'show_on_map' => 'boolean',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'stripe_price_id' => 'string',
        'new_user_discount_value' => 'decimal:2',
        'new_user_discount_duration_value' => 'integer',
    ];

    public static function getDefaultPlan(): ?self
    {
        return static::query()
            ->where('is_active', true)
            ->where('price', 0)
            ->whereJsonContains('metadata->is_default', true)
            ->first();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function (self $plan): void {
            // Verifica se o plano é o padrão
            if ($plan->metadata['is_default'] ?? false) {
                throw new \Exception('Cannot delete the default plan.');
            }

            // Se não houver stripe_product_id, permite deletar normalmente
            if (!$plan->stripe_product_id) {
                return;
            }

            try {
                \Stripe\Stripe::setApiKey(config('cashier.secret'));
                $stripe = \Laravel\Cashier\Cashier::stripe();

                // Busca todos os prices do produto
                $prices = $stripe->prices->all(['product' => $plan->stripe_product_id, 'limit' => 100]);
                $hasActiveSubscriptions = false;

                foreach ($prices->data as $price) {
                    // Busca assinaturas atreladas a esse price
                    $subscriptions = $stripe->subscriptions->all(['price' => $price->id, 'limit' => 1, 'status' => 'active']);
                    if (count($subscriptions->data) > 0) {
                        $hasActiveSubscriptions = true;
                        break;
                    }
                }

                if ($hasActiveSubscriptions) {
                    // Desativa o produto e impede exclusão real
                    $plan->is_active = false;
                    $plan->save();
                    $stripe->products->update($plan->stripe_product_id, ['active' => false]);
                    throw new \Exception('O plano foi desativado, pois há assinaturas ativas. Não pode ser excluído.');
                } else {
                    // Permite exclusão real e tenta deletar o produto no Stripe
                    try {
                        $stripe->products->delete($plan->stripe_product_id);
                    } catch (\Exception $e) {
                        if (str_contains($e->getMessage(), 'user-created prices')) {
                            // Fallback: desativa o produto
                            $stripe->products->update($plan->stripe_product_id, ['active' => false]);
                            \Log::warning('Produto não pôde ser deletado no Stripe, foi desativado: ' . $e->getMessage());
                        } else {
                            throw $e;
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Erro ao processar exclusão no Stripe: ' . $e->getMessage());
                throw $e;
            }
        });

        // Impedir desativação do plano padrão
        static::updating(function (self $plan): void {
            if ($plan->isDirty('is_active') &&
                $plan->metadata['is_default'] ?? false &&
                !$plan->is_active
            ) {
                throw new \Exception('Cannot deactivate the default plan.');
            }
        });
    }

    /**
     * Get all subscriptions for this plan.
     */
    public function subscriptions(): HasMany
    {
        // Usando nosso model customizado que tem o relacionamento coupon
        return $this->hasMany(\App\Models\Subscription::class, 'stripe_price', 'stripe_price_id');
    }

    /**
     * Get active subscriptions count for this plan.
     */
    public function activeSubscriptionsCount(): int
    {
        return $this->subscriptions()
            ->where('stripe_status', 'active')
            ->count();
    }

    /**
     * Check if plan is free.
     */
    public function isFree(): bool
    {
        return $this->price === 0.0;
    }

    /**
     * Check if plan has trial period.
     */
    public function hasTrial(): bool
    {
        return !is_null($this->trial_days) && $this->trial_days > 0;
    }

    /**
     * Get formatted price with currency.
     */
    public function getFormattedPriceAttribute(): string
    {
        if ($this->isFree()) {
            return 'Free';
        }

        return number_format($this->price, 2) . ' ' . strtoupper($this->currency);
    }

    /**
     * Get formatted billing interval.
     */
    public function getFormattedIntervalAttribute(): string
    {
        return match ($this->interval) {
            'month' => 'Monthly',
            'year' => 'Yearly',
            default => ucfirst($this->interval),
        };
    }

    public function validate(): bool
    {
        if ($this->is_default && $this->price > 0) {
            throw new \Exception('Default plan must be free');
        }

        if ($this->is_default && Plan::where('is_default', true)
            ->where('is_active', true)
            ->where('id', '!=', $this->id)
            ->exists()) {
            throw new \Exception('Another default plan already exists');
        }

        return true;
    }

    /**
     * Get the next billing date for this plan.
     */
    public function getNextBillingDate(): string
    {
        return now()->add($this->interval, 1)->format('Y-m-d');
    }

    /**
     * Get comparable plans for upsell/crosssell.
     */
    public function getComparablePlans(): Collection
    {
        return static::query()
            ->where('is_active', true)
            ->where('id', '!=', $this->id)
            ->where('price', '>', $this->price)
            ->orderBy('price')
            ->get();
    }

    /**
     * Get plan usage statistics.
     */
    public function getUsageStats(): array
    {
        return [
            'total_revenue' => $this->subscriptions()
                ->where('stripe_status', 'active')
                ->sum('stripe_price'),
            'conversion_rate' => $this->calculateConversionRate(),
            'churn_rate' => $this->calculateChurnRate(),
            'average_subscription_length' => $this->calculateAverageSubscriptionLength(),
        ];
    }

    /**
     * Check if plan can be modified.
     */
    public function canBeModified(): bool
    {
        return !$this->is_default && $this->subscriptions()->active()->count() === 0;
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'plan_roles')
            ->withTimestamps();
    }

    /**
     * Sync plan roles when subscription changes
     */
    public function syncRoles(): void
    {
        $this->roles()->sync(
            Role::whereIn('slug', ['basic-plan', 'pro-plan'])
                ->where('slug', $this->getPlanRoleSlug())
                ->pluck('id')
        );
    }

    private function getPlanRoleSlug(): string
    {
        return match ($this->stripe_price_id) {
            'price_basic' => 'basic-plan',
            'price_pro' => 'pro-plan',
            default => 'free-plan',
        };
    }

    public function limits(): HasMany
    {
        return $this->hasMany(PlanLimit::class);
    }

    public function getModuleLimit(string $moduleId, string $resource): int
    {
        $limit = $this->limits()
            ->where('module', $moduleId)
            ->where('resource', $resource)
            ->first();

        if (!$limit) {
            // Obter limite padrão do módulo
            $moduleConfig = $this->getModuleConfig($moduleId);
            $defaultLimit = collect($moduleConfig['limits'] ?? [])
                ->firstWhere('resource', $resource);

            return $defaultLimit['default'] ?? -1;
        }

        return (int) $limit->limit_value;
    }

    private function getModuleConfig(string $moduleId): array
    {
        $modulePath = base_path("Modules/{$moduleId}/module.json");
        if (!file_exists($modulePath)) {
            return [];
        }

        return json_decode(file_get_contents($modulePath), true) ?? [];
    }

    public function hasUnlimitedAccess(string $module, string $resource): bool
    {
        $limit = $this->getModuleLimit($module, $resource);
        return $limit === -1;
    }

    public function withinLimit(string $module, string $resource, int $currentUsage): bool
    {
        $limit = $this->getModuleLimit($module, $resource);

        if ($limit === null || $limit === -1) {
            return true;
        }

        return $currentUsage < $limit;
    }

    /**
     * Get the modules associated with the plan.
     */
    public function modules()
    {
        return $this->hasMany(ModulePermission::class)
            ->where('is_enabled', true);
    }

    /**
     * Get all module permissions (enabled and disabled).
     */
    public function modulePermissions()
    {
        return $this->hasMany(ModulePermission::class);
    }

    public function hasModuleAccess(string $moduleId): bool
    {
        return $this->modulePermissions()
            ->where('module_id', $moduleId)
            ->where('is_enabled', true)
            ->exists();
    }

    public function hasModuleFeature(string $moduleId, string $feature): bool
    {
        $permission = $this->modulePermissions()
            ->where('module_id', $moduleId)
            ->where('is_enabled', true)
            ->first();

        if (!$permission) {
            return false;
        }

        return in_array($feature, $permission->features ?? [], true);
    }

    public function getModuleFeatures(string $moduleId): Collection
    {
        return $this->limits()
            ->where('module', $moduleId)
            ->with('features')
            ->get();
    }

    public function subscribe(User $user)
    {
        // Atribuir permissões do plano ao usuário
        $permissions = $this->modules()->with('permissions')->get()
            ->pluck('permissions.*.key')
            ->flatten()
            ->toArray();

        $user->permissions()->syncWithoutDetaching($permissions);

        // Atualizar a role do usuário no time para 'manager'
        if ($user->currentTeam) {
            $user->currentTeam->users()->updateExistingPivot($user->id, [
                'role' => 'manager'
            ]);
        }
    }

    public function intervals(): BelongsToMany
    {
        return $this->belongsToMany(Interval::class, 'plan_intervals')
        ->withPivot(['id', 'price', 'stripe_price_id', 'created_at', 'updated_at', 'deleted_at'])
            ->withTimestamps();
    }

    public function getIntervalAttribute()
    {
        return $this->intervals()->where('id', $this->pivot->id)->first();
    }

    public function getIntervalsForFormAttribute()
    {
        return $this->intervals->map(function ($interval) {
            return [
                'interval_id' => $interval->id,
                'price' => $interval->pivot->price,
            ];
        })->toArray();
    }

    public function getLimitsForFormAttribute()
    {
        return $this->limits->map(function ($limit) {
            return [
                'module' => $limit->module,
                'resource' => $limit->resource,
                'limit_type' => $limit->limit_type,
                'limit_value' => $limit->limit_value,
                'period' => $limit->period,
                'is_hard_limit' => $limit->is_hard_limit,
                'grace_period_days' => $limit->grace_period_days,
                'notify_on_limit' => $limit->notify_on_limit,
                'notification_threshold' => $limit->notification_threshold,
                'metadata' => $limit->metadata,
            ];
        })->toArray();
    }

    /**
     * Check if plan has new user discount
     */
    public function hasNewUserDiscount(): bool
    {
        return $this->new_user_discount_type !== 'none' && $this->new_user_discount_type !== null;
    }

    /**
     * Get new user discount text for display
     */
    public function getNewUserDiscountText(): ?string
    {
        if (!$this->hasNewUserDiscount()) {
            return null;
        }

        $durationText = '';
        if ($this->new_user_discount_duration_value && $this->new_user_discount_duration_unit) {
            $unit = match($this->new_user_discount_duration_unit) {
                'days' => $this->new_user_discount_duration_value == 1 ? 'dia' : 'dias',
                'months' => $this->new_user_discount_duration_value == 1 ? 'mês' : 'meses',
                'years' => $this->new_user_discount_duration_value == 1 ? 'ano' : 'anos',
                default => '',
            };
            $durationText = " por {$this->new_user_discount_duration_value} {$unit}";
        }

        return match($this->new_user_discount_type) {
            'percentage' => "{$this->new_user_discount_value}% de desconto{$durationText}",
            'fixed' => "R$ {$this->new_user_discount_value} de desconto{$durationText}",
            'trial' => "{$this->new_user_discount_duration_value} {$unit} grátis",
            default => null,
        };
    }

    /**
     * Calculate trial days for new user discount
     */
    public function getNewUserTrialDays(): ?int
    {
        if ($this->new_user_discount_type !== 'trial') {
            return null;
        }

        if (!$this->new_user_discount_duration_value || !$this->new_user_discount_duration_unit) {
            return null;
        }

        return match($this->new_user_discount_duration_unit) {
            'days' => $this->new_user_discount_duration_value,
            'months' => $this->new_user_discount_duration_value * 30,
            'years' => $this->new_user_discount_duration_value * 365,
            default => null,
        };
    }

    /**
     * Check if plan has analytics access
     */
    public function hasAnalytics(): bool
    {
        $features = $this->features;

        if (!is_array($features)) {
            return false;
        }

        return isset($features['analytics']) && is_array($features['analytics']) && !empty($features['analytics']);
    }

    /**
     * Check if plan has access to a specific analytics metric
     */
    public function hasAnalyticsMetric(string $metric): bool
    {
        $features = $this->features;

        if (!is_array($features) || !isset($features['analytics'])) {
            return false;
        }

        $analytics = $features['analytics'];

        // Se analytics for true (acesso total), permite tudo
        if ($analytics === true) {
            return true;
        }

        // Se for array, verifica se a métrica específica está incluída
        if (is_array($analytics)) {
            return in_array($metric, $analytics, true);
        }

        return false;
    }

    /**
     * Get all available analytics metrics for this plan
     */
    public function getAnalyticsMetrics(): array
    {
        $features = $this->features;

        if (!is_array($features) || !isset($features['analytics'])) {
            return [];
        }

        $analytics = $features['analytics'];

        // Se for true, retorna todas as métricas disponíveis
        if ($analytics === true) {
            return [
                'views',
                'whatsapp_clicks',
                'website_clicks',
                'phone_clicks',
                'map_clicks',
                'shares',
                'leads',
                'instagram_clicks',
                'facebook_clicks',
                'tiktok_clicks',
            ];
        }

        // Se for array, retorna as métricas configuradas
        if (is_array($analytics)) {
            return $analytics;
        }

        return [];
    }
}
