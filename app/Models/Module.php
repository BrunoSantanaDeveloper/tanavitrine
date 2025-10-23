<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Module extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'limit_types',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'limit_types' => 'array',
    ];

    /**
     * Get the limits for this module.
     */
    public function limits(): HasMany
    {
        return $this->hasMany(PlanLimit::class, 'module', 'code');
    }

    /**
     * Get all available limit types for this module.
     */
    public function getAvailableLimitTypes(): array
    {
        if (!$this->limit_types) {
            return [];
        }

        return collect($this->limit_types)
            ->mapWithKeys(function ($type, $key) {
                return [$key => $type['name']];
            })
            ->toArray();
    }

    /**
     * Get all available resources for a specific limit type.
     */
    public function getAvailableResources(string $limitType): array
    {
        return $this->limit_types[$limitType]['resources'] ?? [];
    }

    /**
     * Check if a limit type is available for this module.
     */
    public function hasLimitType(string $limitType): bool
    {
        return isset($this->limit_types[$limitType]);
    }

    /**
     * Check if a resource is available for a specific limit type.
     */
    public function hasResource(string $limitType, string $resource): bool
    {
        return isset($this->limit_types[$limitType]['resources'][$resource]);
    }
}
