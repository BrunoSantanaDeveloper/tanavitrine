<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Team;
use App\Models\Module;
use App\Models\PlanLimit;
use Illuminate\Support\Collection;

class ResourceLimitService
{
    /**
     * Check if a team has reached a specific resource limit
     */
    public function hasReachedLimit(Team $team, string $module, string $resource): bool
    {
        $limit = $this->getLimit($team, $module, $resource);
        if (!$limit) {
            return false;
        }

        $currentUsage = $this->getCurrentUsage($team, $module, $resource);
        return $currentUsage >= $limit->limit_value;
    }

    /**
     * Get the current usage of a resource
     */
    public function getCurrentUsage(Team $team, string $module, string $resource): int
    {
        return match($module) {
            'digital-signage' => $this->getDigitalSignageUsage($team, $resource),
            'store' => $this->getStoreUsage($team, $resource),
            'team' => $this->getTeamUsage($team, $resource),
            'storage' => $this->getStorageUsage($team, $resource),
            default => 0,
        };
    }

    /**
     * Get the limit for a specific resource
     */
    public function getLimit(Team $team, string $module, string $resource): ?PlanLimit
    {
        return $team->plan->limits()
            ->where('module', $module)
            ->where('resource', $resource)
            ->first();
    }

    /**
     * Get all limits for a team
     */
    public function getAllLimits(Team $team): Collection
    {
        return $team->plan->limits;
    }

    /**
     * Get remaining quota for a resource
     */
    public function getRemainingQuota(Team $team, string $module, string $resource): int
    {
        $limit = $this->getLimit($team, $module, $resource);
        if (!$limit) {
            return PHP_INT_MAX;
        }

        $currentUsage = $this->getCurrentUsage($team, $module, $resource);
        return max(0, $limit->limit_value - $currentUsage);
    }

    /**
     * Get digital signage usage
     */
    protected function getDigitalSignageUsage(Team $team, string $resource): int
    {
        return match($resource) {
            'playlists' => $team->playlists()->count(),
            'media' => $team->media()->count(),
            'displays' => $team->displays()->count(),
            'storage' => $team->media()->sum('size') ?? 0,
            'playlist_items' => $this->getTotalPlaylistItems($team),
            'video_duration' => $this->getTotalVideoDuration($team),
            'institutional_videos' => $this->getInstitutionalVideosCount($team),
            'monthly_content' => $this->getMonthlyContentCount($team),
            default => 0,
        };
    }

    /**
     * Get storage usage
     */
    protected function getStorageUsage(Team $team, string $resource): int
    {
        return match($resource) {
            'files' => $team->media()->sum('size') ?? 0,
            default => 0,
        };
    }

    /**
     * Get store showcase usage.
     */
    protected function getStoreUsage(Team $team, string $resource): int
    {
        return match($resource) {
            'photos_per_vitrine' => $team->media()
                ->where('type', 'image')
                ->where('is_active', true)
                ->whereNull('team_collection_id')
                ->where(function ($query) {
                    $query->whereNull('category')
                        ->orWhere('category', '!=', 'logo');
                })
                ->count(),
            'collections_per_vitrine' => $team->collections()->count(),
            // For per-collection limits, usage here represents the highest collection load.
            'photos_per_collection' => (int) $team->collections()
                ->withCount('media')
                ->get()
                ->max('media_count'),
            // For per-collection video limits, usage represents the highest collection load.
            'videos_per_collection' => (int) $team->collections()
                ->withCount('videos')
                ->get()
                ->max('videos_count'),
            default => 0,
        };
    }

    /**
     * Get team usage (members)
     */
    protected function getTeamUsage(Team $team, string $resource): int
    {
        return match($resource) {
            'members' => 1 + $team->users()->count(), // Owner + members
            default => 0,
        };
    }

    /**
     * Count total playlist items across all playlists
     */
    private function getTotalPlaylistItems(Team $team): int
    {
        return $team->playlists()
            ->withCount('items')
            ->get()
            ->sum('items_count');
    }

    /**
     * Get total video duration in seconds
     */
    private function getTotalVideoDuration(Team $team): int
    {
        return $team->media()
            ->where('type', 'video')
            ->sum('duration') ?? 0;
    }

    /**
     * Count institutional videos (videos with specific metadata)
     */
    private function getInstitutionalVideosCount(Team $team): int
    {
        return $team->media()
            ->where('type', 'video')
            ->whereJsonContains('metadata->type', 'institutional')
            ->count();
    }

    /**
     * Count content created this month
     */
    private function getMonthlyContentCount(Team $team): int
    {
        return $team->media()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->whereJsonContains('metadata->type', 'own_content')
            ->count();
    }
}
