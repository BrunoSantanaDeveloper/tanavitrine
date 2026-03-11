<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TeamCollection extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'name',
        'description',
        'is_featured',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class)->where('type', 'image');
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Media::class)->where('type', 'video');
    }

    public function allMedia(): HasMany
    {
        return $this->hasMany(Media::class);
    }
}
