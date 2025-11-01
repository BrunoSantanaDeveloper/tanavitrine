<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Interval;

class PlanService
{

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
                        'stripe_price_id' => null,
                    ]);
                }
            }

            // Create limits
            if (isset($data['limits']) && is_array($data['limits'])) {
                foreach ($data['limits'] as $limitData) {
                    if (!isset($limitData['module']) || !isset($limitData['resource'])) {
                        continue;
                    }

                    $plan->limits()->create([
                        'module' => $limitData['module'],
                        'resource' => $limitData['resource'],
                        'limit_type' => $limitData['limit_type'],
                        'limit_value' => $limitData['limit_value'],
                        'period' => $limitData['period'] ?? 'month',
                        'grace_period_days' => $limitData['grace_period_days'] ?? null,
                        'notification_threshold' => $limitData['notification_threshold'] ?? 80,
                        'is_hard_limit' => $limitData['is_hard_limit'] ?? true,
                        'notify_on_limit' => $limitData['notify_on_limit'] ?? true,
                        'metadata' => $limitData['metadata'] ?? [],
                    ]);
                }
            }

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
                // Remove old intervals
                $plan->intervals()->detach();

                // Add new intervals
                foreach ($data['intervals'] as $intervalData) {
                    $interval = Interval::findOrFail($intervalData['interval_id']);

                    // Anexar o intervalo com o novo preço
                    $plan->intervals()->attach($interval->id, [
                        'price' => $intervalData['price'],
                        'stripe_price_id' => null,
                    ]);
                }
            }

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

}
