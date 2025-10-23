<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $team_id
 * @property string $name
 * @property string $path
 * @property string $type
 * @property float $size
 * @property string|null $description
 * @property int|null $duration
 * @property bool $is_generic
 * @property string|null $category
 * @property string|null $business_type
 * @property array|null $metadata
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read Team $team
 * @property-read string $url
 */
final class Media extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'name',
        'path',
        'type',
        'size',
        'description',
        'duration',
        'is_generic',
        'category',
        'business_type',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'float',
            'duration' => 'integer',
            'is_generic' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the team that owns the media.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the stores/teams that use this media through pivot table.
     */
    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_media')
            ->withPivot('order', 'is_primary')
            ->withTimestamps()
            ->orderBy('team_media.order');
    }

    /**
     * Get the full URL for the media file.
     */
    public function getUrlAttribute(): string
    {
        // If path is already a full URL, return it
        if (filter_var($this->path, FILTER_VALIDATE_URL)) {
            return $this->path;
        }

        // Otherwise, generate URL from storage
        return Storage::url($this->path);
    }

    /**
     * Scope a query to only include images.
     */
    public function scopeImages($query)
    {
        return $query->where('type', 'image');
    }

    /**
     * Scope a query to only include videos.
     */
    public function scopeVideos($query)
    {
        return $query->where('type', 'video');
    }

    /**
     * Scope a query to only include generic media.
     */
    public function scopeGeneric($query)
    {
        return $query->where('is_generic', true);
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Check if media is an image.
     */
    public function isImage(): bool
    {
        return $this->type === 'image';
    }

    /**
     * Check if media is a video.
     */
    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

    /**
     * Check if media is a document.
     */
    public function isDocument(): bool
    {
        return $this->type === 'document';
    }

    /**
     * Get human-readable file size.
     */
    public function getFormattedSizeAttribute(): string
    {
        $size = $this->size;

        if ($size >= 1024) {
            return number_format($size / 1024, 2) . ' GB';
        }

        return number_format($size, 2) . ' MB';
    }

    /**
     * Get human-readable duration for videos.
     */
    public function getFormattedDurationAttribute(): ?string
    {
        if (!$this->duration) {
            return null;
        }

        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;

        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}
