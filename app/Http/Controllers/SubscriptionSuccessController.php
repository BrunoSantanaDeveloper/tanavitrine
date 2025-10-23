<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionSuccessController extends Controller
{
    /**
     * Exibir página de sucesso após pagamento
     */
    public function show(Request $request): Response|RedirectResponse
    {
        $sessionId = $request->get('session_id');

        if (!$sessionId) {
            return redirect()->route('dashboard')
                ->with('error', 'Sessão de checkout inválida');
        }

        $user = auth()->user();

        try {
            // Verificar se a sessão de checkout é válida
            $session = $user->stripe()->checkout->sessions->retrieve($sessionId);

            if ($session->payment_status !== 'paid') {
                return redirect()->route('dashboard')
                    ->with('error', 'Pagamento ainda não confirmado');
            }

            return Inertia::render('Onboarding/OnboardingSuccess', [
                'user' => $user,
                'subscription' => [
                    'plan_name' => $session->metadata->plan_name ?? 'N/A',
                    'price' => ($session->amount_total / 100) - 500, // Subtract implementation fee
                    'interval' => $session->metadata->interval ?? 'mês',
                ],
                'clinic_address' => $user->onboarding_data['address'] ?? null,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error retrieving checkout session', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'Erro ao verificar pagamento');
        }
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
