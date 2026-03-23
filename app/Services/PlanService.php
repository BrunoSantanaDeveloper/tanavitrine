<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Interval;

class PlanService
{
    private const STORE_LIMIT_FIELD_MAP = [
        'store_limit_photos_per_vitrine' => 'photos_per_vitrine',
        'store_limit_collections_per_vitrine' => 'collections_per_vitrine',
        'store_limit_photos_per_collection' => 'photos_per_collection',
        'store_limit_videos_per_collection' => 'videos_per_collection',
    ];

    public function createPlan(array $data): Plan
    {
        try {
            // Process features and analytics
            $features = $this->processFeatures($data);

            // Create plan
            $plan = Plan::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'stripe_product_id' => null,
                'currency' => $data['currency'],
                'features' => $features,
                'is_featured' => $data['is_featured'] ?? false,
                'is_active' => $data['is_active'] ?? true,
                'is_default' => $data['is_default'] ?? false,
                'sort_order' => $data['sort_order'] ?? 0,
                'metadata' => $data['metadata'] ?? [],
                'trial_days' => $data['trial_days'] ?? null,
                'new_user_discount_type' => $data['new_user_discount_type'] ?? 'none',
                'new_user_discount_value' => $data['new_user_discount_value'] ?? null,
                'new_user_discount_duration_value' => $data['new_user_discount_duration_value'] ?? null,
                'new_user_discount_duration_unit' => $data['new_user_discount_duration_unit'] ?? null,
            ]);

            \Log::info('Plan created: ' . $plan->id);
            \Log::info('PlanService Data: ' . json_encode($data));

            // Create intervals for the plan
            if (isset($data['intervals']) && is_array($data['intervals'])) {
                foreach ($data['intervals'] as $intervalData) {
                    if (!isset($intervalData['interval_id']) || !isset($intervalData['price'])) {
                        continue;
                    }

                    // Buscar o intervalo pelo ID
                    $interval = Interval::findOrFail($intervalData['interval_id']);

                    // Anexar o intervalo ao plano usando attach
                    $plan->intervals()->attach($interval->id, [
                        'price' => $intervalData['price'],
                        'stripe_price_id' => $this->normalizeStripePriceId($intervalData['stripe_price_id'] ?? null),
                    ]);
                }
            }

            $this->syncPlanLimits($plan, $data);

            return $plan;

        } catch (\Exception $e) {
            throw new \Exception('Error creating plan: ' . $e->getMessage());
        }
    }

    public function updatePlan(Plan $plan, array $data): Plan
    {
        \Log::info('UpdatePlan Data: ' . json_encode($data));
        try {
            // Process features and analytics
            $features = $this->processFeatures($data);

            // Update plan
            $plan->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'currency' => $data['currency'],
                'trial_days' => $data['trial_days'] ?? null,
                'features' => $features,
                'is_featured' => $data['is_featured'] ?? false,
                'is_active' => $data['is_active'] ?? true,
                'is_default' => $data['is_default'] ?? false,
                'sort_order' => $data['sort_order'] ?? 0,
                'metadata' => $data['metadata'] ?? [],
                'new_user_discount_type' => $data['new_user_discount_type'] ?? 'none',
                'new_user_discount_value' => $data['new_user_discount_value'] ?? null,
                'new_user_discount_duration_value' => $data['new_user_discount_duration_value'] ?? null,
                'new_user_discount_duration_unit' => $data['new_user_discount_duration_unit'] ?? null,
            ]);

            \Log::info('UpdatePlan Plan: ' . json_encode($plan));

            // Update intervals
            if (isset($data['intervals'])) {
                $existingStripePriceIds = $plan->intervals()
                    ->get()
                    ->mapWithKeys(fn ($interval) => [
                        (int) $interval->id => $this->normalizeStripePriceId($interval->pivot->stripe_price_id),
                    ])
                    ->toArray();

                // Remove old intervals
                $plan->intervals()->detach();

                // Add new intervals
                foreach ($data['intervals'] as $intervalData) {
                    $interval = Interval::findOrFail($intervalData['interval_id']);

                    // Anexar o intervalo com o novo preço
                    $plan->intervals()->attach($interval->id, [
                        'price' => $intervalData['price'],
                        'stripe_price_id' => $this->resolveStripePriceId(
                            $intervalData,
                            $existingStripePriceIds,
                            (int) $interval->id
                        ),
                    ]);
                }
            }

            $this->syncPlanLimits($plan, $data);

            return $plan;

        } catch (\Exception $e) {
            throw new \Exception('Error updating plan: ' . $e->getMessage());
        }
    }

    /**
     * Process features and analytics from form data
     */
    private function processFeatures(array $data): array
    {
        // Get features as array (either from string or array)
        $features = $data['features'] ?? [];
        if (is_string($features)) {
            $features = array_filter(array_map('trim', explode("\n", $features)));
        }

        // Convert to associative array for proper JSON storage
        $featuresArray = [];

        // Add analytics metrics if provided
        if (isset($data['analytics_metrics']) && is_array($data['analytics_metrics']) && !empty($data['analytics_metrics'])) {
            $featuresArray['analytics'] = $data['analytics_metrics'];
        }

        // Add other features (preserve non-analytics features)
        if (is_array($features)) {
            foreach ($features as $feature) {
                // Skip analytics features as they're already handled
                if (!str_starts_with($feature, 'Analytics:')) {
                    $featuresArray[] = $feature;
                }
            }
        }

        return $featuresArray;
    }

    private function resolveStripePriceId(array $intervalData, array $existingStripePriceIds, int $intervalId): ?string
    {
        if (array_key_exists('stripe_price_id', $intervalData)) {
            return $this->normalizeStripePriceId($intervalData['stripe_price_id']);
        }

        return $existingStripePriceIds[$intervalId] ?? null;
    }

    private function normalizeStripePriceId(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized !== '' ? $normalized : null;
    }

    /**
     * Persist plan limits from dedicated store limit fields and legacy payloads.
     */
    private function syncPlanLimits(Plan $plan, array $data): void
    {
        if (!isset($data['limits']) && !$this->hasStoreLimitFields($data)) {
            return;
        }

        $limits = $this->normalizeLimits($plan, $data);

        $plan->limits()->delete();

        foreach ($limits as $limitData) {
            $plan->limits()->create($limitData);
        }
    }

    private function hasStoreLimitFields(array $data): bool
    {
        foreach (array_keys(self::STORE_LIMIT_FIELD_MAP) as $field) {
            if (array_key_exists($field, $data)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeLimits(Plan $plan, array $data): array
    {
        $hasLegacyLimitsPayload = isset($data['limits']) && is_array($data['limits']);
        $limits = $hasLegacyLimitsPayload
            ? []
            : $plan->limits
                ->map(function ($limit): array {
                    return [
                        'module' => (string) $limit->module,
                        'resource' => (string) $limit->resource,
                        'limit_type' => (string) $limit->limit_type,
                        'limit_value' => (int) $limit->limit_value,
                        'period' => (string) $limit->period,
                        'grace_period_days' => $limit->grace_period_days,
                        'notification_threshold' => (int) $limit->notification_threshold,
                        'is_hard_limit' => (bool) $limit->is_hard_limit,
                        'notify_on_limit' => (bool) $limit->notify_on_limit,
                        'metadata' => is_array($limit->metadata) ? $limit->metadata : [],
                    ];
                })
                ->toArray();

        if ($hasLegacyLimitsPayload) {
            foreach ($data['limits'] as $limitData) {
                if (!isset($limitData['module'], $limitData['resource'])) {
                    continue;
                }

                $limits[] = [
                    'module' => (string) $limitData['module'],
                    'resource' => (string) $limitData['resource'],
                    'limit_type' => (string) ($limitData['limit_type'] ?? 'count'),
                    'limit_value' => (int) ($limitData['limit_value'] ?? 0),
                    'period' => (string) ($limitData['period'] ?? 'month'),
                    'grace_period_days' => isset($limitData['grace_period_days']) && $limitData['grace_period_days'] !== ''
                        ? (int) $limitData['grace_period_days']
                        : null,
                    'notification_threshold' => (int) ($limitData['notification_threshold'] ?? 80),
                    'is_hard_limit' => (bool) ($limitData['is_hard_limit'] ?? true),
                    'notify_on_limit' => (bool) ($limitData['notify_on_limit'] ?? true),
                    'metadata' => is_array($limitData['metadata'] ?? null) ? $limitData['metadata'] : [],
                ];
            }
        }

        foreach (self::STORE_LIMIT_FIELD_MAP as $field => $resource) {
            if (!array_key_exists($field, $data) || $data[$field] === '' || $data[$field] === null) {
                continue;
            }

            $limits = array_values(array_filter(
                $limits,
                fn (array $limit): bool => !($limit['module'] === 'store' && $limit['resource'] === $resource)
            ));

            $limits[] = [
                'module' => 'store',
                'resource' => $resource,
                'limit_type' => 'count',
                'limit_value' => (int) $data[$field],
                'period' => 'month',
                'grace_period_days' => null,
                'notification_threshold' => 80,
                'is_hard_limit' => true,
                'notify_on_limit' => true,
                'metadata' => [],
            ];
        }

        // Keep only one row per resource/period because the current DB unique index is
        // plan_id + resource + period (module is not part of the constraint).
        $deduplicated = [];
        foreach ($limits as $limit) {
            $key = "{$limit['resource']}|{$limit['period']}";
            $deduplicated[$key] = $limit;
        }

        return array_values($deduplicated);
    }

}
