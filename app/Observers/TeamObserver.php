<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Team;
use App\Models\Plan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TeamObserver
{
    /**
     * Handle the Team "saving" event (before save).
     * This runs before both created and updated events.
     */
    public function saving(Team $team): void
    {
        // Only geocode if address fields are present and coordinates are missing
        if ($this->shouldGeocode($team)) {
            $this->geocodeAddress($team);
        }
    }

    /**
     * Handle the Team "updated" event.
     * Sync subscription when admin changes the plan.
     */
    public function updated(Team $team): void
    {
        if ($team->personal_team || $team->status === 'pendente') {
            return;
        }

        // Quando admin mudar o plan_id, sincronizar subscription
        if ($team->wasChanged('plan_id') && $team->plan_id) {
            $this->syncSubscriptionWithPlan($team);
        }
    }

    /**
     * Handle the Team "created" event.
     * Create subscription if plan is assigned during creation.
     */
    public function created(Team $team): void
    {
        if ($team->personal_team || $team->status === 'pendente') {
            return;
        }

        // Se admin criou loja com plano atribuído, criar subscription
        if ($team->plan_id && $team->owner) {
            $this->syncSubscriptionWithPlan($team);
        }
    }

    /**
     * Sync user's subscription with team's assigned plan.
     * This allows admin to manage plans directly via TeamResource.
     */
    private function syncSubscriptionWithPlan(Team $team): void
    {
        try {
            $plan = Plan::find($team->plan_id);
            $user = $team->owner;

            if (!$plan || !$user) {
                return;
            }

            // Pega o primeiro intervalo disponível (geralmente mensal)
            $planInterval = $plan->intervals()->first();
            if (!$planInterval) {
                Log::warning('Plan has no intervals configured', ['plan_id' => $plan->id]);
                return;
            }

            $stripePriceId = $planInterval->pivot->stripe_price_id ?? 'price_' . $planInterval->pivot->id;
            $price = (float) ($planInterval->pivot->price ?? 0);

            // Marca featured=true se o plano for "Destaque" e is_featured=true
            if ($plan->is_featured && !$team->featured) {
                $team->featured = true;
                // Destaque sem prazo por padrão.
                // Prazo opcional é configurado manualmente no painel admin.
                $team->featured_until = null;
                $team->saveQuietly(); // Usa saveQuietly para não disparar o observer novamente
            }

            // Remove featured se o plano NÃO for destaque
            if (!$plan->is_featured && $team->featured) {
                $team->featured = false;
                $team->featured_until = null;
                $team->saveQuietly();
            }

            // Verifica se user já tem subscription default
            $subscription = $user->subscription('default');

            if ($subscription) {
                // Atualizar subscription existente
                $subscription->update([
                    'name' => 'default',
                    'type' => $plan->name,
                    'stripe_price' => $stripePriceId,
                    'stripe_status' => 'active',
                    'original_price' => $price,
                    'final_price' => $price,
                ]);

                Log::info('Subscription updated by admin', [
                    'team_id' => $team->id,
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'subscription_id' => $subscription->id,
                    'price' => $price,
                ]);
            } else {
                // Criar nova subscription (modo local/admin)
                $subscription = $user->subscriptions()->create([
                    'name' => 'default',
                    'type' => $plan->name,
                    'stripe_id' => 'sub_admin_' . uniqid(),
                    'stripe_status' => 'active',
                    'stripe_price' => $stripePriceId,
                    'quantity' => 1,
                    'trial_ends_at' => null,
                    'ends_at' => null,
                    'original_price' => $price,
                    'final_price' => $price,
                ]);

                Log::info('Subscription created by admin', [
                    'team_id' => $team->id,
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'subscription_id' => $subscription->id,
                    'price' => $price,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to sync subscription with plan', [
                'team_id' => $team->id,
                'plan_id' => $team->plan_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Determine if we should geocode the address
     */
    private function shouldGeocode(Team $team): bool
    {
        // Check if we have the minimum required fields (city and state)
        if (empty($team->city) || empty($team->state)) {
            return false;
        }

        // If coordinates already exist and address hasn't changed, skip
        if ($team->latitude && $team->longitude && !$team->isDirty(['address', 'address_number', 'city', 'state', 'zip_code', 'google_maps_url'])) {
            return false;
        }

        return true;
    }

    /**
     * Geocode address using Nominatim API
     */
    private function geocodeAddress(Team $team): void
    {
        try {
            // Priority 1: if we have a Google Maps URL with embedded coordinates, use it directly.
            $coordinatesFromGoogleMaps = $this->extractCoordinatesFromGoogleMapsUrl($team->google_maps_url);
            if ($coordinatesFromGoogleMaps) {
                $team->latitude = $coordinatesFromGoogleMaps['lat'];
                $team->longitude = $coordinatesFromGoogleMaps['lng'];

                Log::info('Coordinates extracted from Google Maps URL for team', [
                    'team_id' => $team->id,
                    'lat' => $team->latitude,
                    'lng' => $team->longitude,
                ]);

                return;
            }

            // Priority 2: fallback to geocoding by address.
            // Build query with maximum detail available
            $query = $this->buildQuery($team);

            // Call Nominatim API
            $response = Http::timeout(5)
                ->withHeaders(['User-Agent' => 'TanaVitrine/1.0'])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'br',
                    'addressdetails' => 1,
                ]);

            if ($response->successful() && $data = $response->json()) {
                if (isset($data[0]['lat']) && isset($data[0]['lon'])) {
                    $team->latitude = (float) $data[0]['lat'];
                    $team->longitude = (float) $data[0]['lon'];

                    Log::info('Geocoded address for team', [
                        'team_id' => $team->id,
                        'query' => $query,
                        'lat' => $team->latitude,
                        'lng' => $team->longitude,
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Log error but don't fail the save operation
            Log::warning('Geocoding failed for team', [
                'team_id' => $team->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Try to extract lat/lng from a Google Maps URL.
     *
     * Supported patterns:
     * - .../@-16.6828852,-49.2065673,17z
     * - ...?q=-16.6828852,-49.2065673
     * - ...?query=-16.6828852,-49.2065673
     * - ...!3d-16.6828852!4d-49.2065673
     *
     * @return array{lat: float, lng: float}|null
     */
    private function extractCoordinatesFromGoogleMapsUrl(?string $url): ?array
    {
        if (!$url || !is_string($url)) {
            return null;
        }

        $cleanUrl = trim($url);
        if ($cleanUrl === '') {
            return null;
        }

        // Pattern: /@lat,lng
        if (preg_match('/@(-?\d{1,3}\.\d+),\s*(-?\d{1,3}\.\d+)/', $cleanUrl, $matches)) {
            return $this->normalizeCoordinates((float) $matches[1], (float) $matches[2]);
        }

        // Pattern: !3dlat!4dlng
        if (preg_match('/!3d(-?\d{1,3}\.\d+)!4d(-?\d{1,3}\.\d+)/', $cleanUrl, $matches)) {
            return $this->normalizeCoordinates((float) $matches[1], (float) $matches[2]);
        }

        // Patterns in query string: q=lat,lng or query=lat,lng
        $parts = parse_url($cleanUrl);
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $queryParams);
            foreach (['q', 'query'] as $key) {
                if (!empty($queryParams[$key]) && is_string($queryParams[$key])) {
                    if (preg_match('/(-?\d{1,3}\.\d+),\s*(-?\d{1,3}\.\d+)/', $queryParams[$key], $matches)) {
                        return $this->normalizeCoordinates((float) $matches[1], (float) $matches[2]);
                    }
                }
            }
        }

        return null;
    }

    /**
     * Validate latitude/longitude ranges.
     *
     * @return array{lat: float, lng: float}|null
     */
    private function normalizeCoordinates(float $lat, float $lng): ?array
    {
        if ($lat < -90 || $lat > 90) {
            return null;
        }

        if ($lng < -180 || $lng > 180) {
            return null;
        }

        return [
            'lat' => $lat,
            'lng' => $lng,
        ];
    }

    /**
     * Build geocoding query with available data
     */
    private function buildQuery(Team $team): string
    {
        $parts = [];

        // Add address (street + number)
        if (!empty($team->address)) {
            $parts[] = $team->address;
        }

        if (!empty($team->address_number)) {
            $parts[] = $team->address_number;
        }

        // Add CEP if available
        if (!empty($team->zip_code)) {
            $parts[] = $team->zip_code;
        }

        // Add city
        if (!empty($team->city)) {
            $parts[] = $team->city;
        }

        // Add state
        if (!empty($team->state)) {
            $parts[] = $team->state;
        }

        // Always append Brazil
        $parts[] = 'Brazil';

        return implode(', ', $parts);
    }

    /**
     * Handle the Team "deleted" event.
     */
    public function deleted(Team $team): void
    {
        //
    }

    /**
     * Handle the Team "restored" event.
     */
    public function restored(Team $team): void
    {
        //
    }

    /**
     * Handle the Team "force deleted" event.
     */
    public function forceDeleted(Team $team): void
    {
        //
    }
}
