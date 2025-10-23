<?php

namespace App\Traits;

use App\Models\Plan;
use App\Services\ResourceLimitService;

trait HasPlanLimits
{
    /**
     * Get the current plan for the team
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Check if team can create a new resource
     */
    public function canCreateResource(string $module, string $resource): bool
    {
        if (!$this->plan) {
            return false; // No plan assigned
        }

        $resourceLimitService = app(ResourceLimitService::class);
        return !$resourceLimitService->hasReachedLimit($this, $module, $resource);
    }

    /**
     * Check if team has a specific feature
     */
    public function hasFeature(string $feature): bool
    {
        if (!$this->plan) {
            return false;
        }

        $features = $this->plan->features ?? [];
        return in_array($feature, $features, true);
    }

    /**
     * Get current usage for a resource
     */
    public function getCurrentUsage(string $module, string $resource): int
    {
        $resourceLimitService = app(ResourceLimitService::class);
        return $resourceLimitService->getCurrentUsage($this, $module, $resource);
    }

    /**
     * Get remaining quota for a resource
     */
    public function getRemainingQuota(string $module, string $resource): int
    {
        $resourceLimitService = app(ResourceLimitService::class);
        return $resourceLimitService->getRemainingQuota($this, $module, $resource);
    }

    /**
     * Get usage summary for all resources
     */
    public function getUsageSummary(): array
    {
        if (!$this->plan) {
            return [];
        }

        $resourceLimitService = app(ResourceLimitService::class);
        $summary = [];

        // Digital signage resources
        $digitalSignageResources = ['playlists', 'media', 'displays', 'storage', 'playlist_items', 'video_duration', 'institutional_videos', 'monthly_content'];
        foreach ($digitalSignageResources as $resource) {
            $currentUsage = $this->getCurrentUsage('digital-signage', $resource);
            $limit = $this->plan->getModuleLimit('digital-signage', $resource);
            
            if ($limit > 0) {
                $percentage = ($currentUsage / $limit) * 100;
                $summary[] = [
                    'module' => 'digital-signage',
                    'resource' => $resource,
                    'current_usage' => $currentUsage,
                    'limit' => $limit,
                    'remaining' => max(0, $limit - $currentUsage),
                    'percentage' => min(100, $percentage),
                    'is_at_limit' => $currentUsage >= $limit,
                    'is_near_limit' => $percentage >= 80,
                ];
            }
        }

        // Team resources
        $teamResources = ['members'];
        foreach ($teamResources as $resource) {
            $currentUsage = $this->getCurrentUsage('team', $resource);
            $limit = $this->plan->getModuleLimit('team', $resource);
            
            if ($limit > 0) {
                $percentage = ($currentUsage / $limit) * 100;
                $summary[] = [
                    'module' => 'team',
                    'resource' => $resource,
                    'current_usage' => $currentUsage,
                    'limit' => $limit,
                    'remaining' => max(0, $limit - $currentUsage),
                    'percentage' => min(100, $percentage),
                    'is_at_limit' => $currentUsage >= $limit,
                    'is_near_limit' => $percentage >= 80,
                ];
            }
        }

        return $summary;
    }

    /**
     * Check if any resource is near its limit (default 80%)
     */
    public function isNearAnyLimit(int $threshold = 80): bool
    {
        $summary = $this->getUsageSummary();
        
        foreach ($summary as $item) {
            if ($item['percentage'] >= $threshold) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get resources that are near their limits
     */
    public function getResourcesNearLimit(int $threshold = 80): array
    {
        $summary = $this->getUsageSummary();
        
        return array_filter($summary, function ($item) use ($threshold) {
            return $item['percentage'] >= $threshold;
        });
    }
}