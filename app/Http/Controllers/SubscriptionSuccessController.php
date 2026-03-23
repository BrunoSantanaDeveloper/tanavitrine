<?php

namespace App\Http\Controllers;

use App\Models\PlanInterval;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionSuccessController extends Controller
{
    /**
     * Exibir página de sucesso após assinatura
     */
    public function show(Request $request): Response|RedirectResponse
    {
        $sessionId = $request->get('session_id');

        if (!$sessionId) {
            return redirect()->route('dashboard')
                ->with('error', 'Sessão inválida');
        }

        $user = auth()->user();

        // Get subscription from user
        $subscription = $user->subscriptions()->latest()->first();

        if (!$subscription) {
            return redirect()->route('dashboard')
                ->with('error', 'Assinatura não encontrada');
        }

        $planInterval = null;
        if ($subscription->stripe_price) {
            $planInterval = PlanInterval::query()
                ->with(['plan', 'interval'])
                ->where('stripe_price_id', $subscription->stripe_price)
                ->first();
        }

        $team = $user->currentTeam ?: $user->ownedTeams()->latest('id')->first();

        return Inertia::render('Subscriptions/Success', [
            'user' => $user,
            'subscription' => [
                'id' => $subscription->stripe_id,
                'plan_name' => $planInterval?->plan?->name ?? $subscription->type,
                'plan_interval_id' => $planInterval?->id,
                'price' => (float) ($planInterval?->price ?? $subscription->original_price ?? $subscription->final_price ?? 0),
                'interval' => $planInterval?->interval?->name ?? 'Mensal',
                'features' => $this->extractPlanFeatures($planInterval),
            ],
            'store' => $team ? [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'status' => $team->status,
                'is_featured' => (bool) $team->featured,
                'featured_until' => $team->featured_until?->format('d/m/Y'),
            ] : null,
            'local_mode' => true,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function extractPlanFeatures(?PlanInterval $planInterval): array
    {
        $rawFeatures = $planInterval?->plan?->features;
        if (!is_array($rawFeatures)) {
            return [];
        }

        $features = [];
        foreach ($rawFeatures as $key => $value) {
            if ($key === 'analytics') {
                continue;
            }

            if (is_string($value)) {
                $text = trim($value);
                if ($text !== '') {
                    $features[] = $text;
                }

                continue;
            }

            if (!is_array($value)) {
                continue;
            }

            foreach ($value as $nestedValue) {
                if (!is_string($nestedValue)) {
                    continue;
                }

                $text = trim($nestedValue);
                if ($text !== '') {
                    $features[] = $text;
                }
            }
        }

        return array_values(array_unique($features));
    }

    /**
     * Salvar endereço de entrega do Player
     */
    public function saveDeliveryAddress(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'use_clinic_address' => 'required|boolean',
            'delivery_address' => 'required_if:use_clinic_address,false|array',
            'delivery_address.cep' => 'required_if:use_clinic_address,false|string',
            'delivery_address.street' => 'required_if:use_clinic_address,false|string',
            'delivery_address.number' => 'required_if:use_clinic_address,false|string',
            'delivery_address.neighborhood' => 'required_if:use_clinic_address,false|string',
            'delivery_address.city' => 'required_if:use_clinic_address,false|string',
            'delivery_address.state' => 'required_if:use_clinic_address,false|string',
            'recipient_name' => 'required_if:use_clinic_address,false|string',
            'recipient_phone' => 'required_if:use_clinic_address,false|string',
        ]);

        $user = auth()->user();
        $onboardingData = $user->onboarding_data ?? [];

        if ($validated['use_clinic_address']) {
            $onboardingData['delivery_address'] = array_merge(
                $onboardingData['address'] ?? [],
                [
                    'use_clinic_address' => true,
                    'recipient_name' => $user->name,
                    'recipient_phone' => $onboardingData['phone'] ?? '',
                ]
            );
        } else {
            $onboardingData['delivery_address'] = array_merge(
                $validated['delivery_address'],
                [
                    'use_clinic_address' => false,
                    'recipient_name' => $validated['recipient_name'],
                    'recipient_phone' => $validated['recipient_phone'],
                ]
            );
        }

        $user->update([
            'onboarding_data' => $onboardingData,
            'onboarding_completed' => true,
            'player_order_status' => 'pending',
        ]);

        return response()->json(['success' => true]);
    }
}
