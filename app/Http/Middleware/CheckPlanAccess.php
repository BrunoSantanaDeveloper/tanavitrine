<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckPlanAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = Auth::user();
        $team = $user?->currentTeam;

        if (!$team) {
            return $this->unauthorized($request, 'Team not found');
        }

        if (!$team->hasFeature($feature)) {
            $message = "Esta funcionalidade requer upgrade do plano.";

            if ($request->expectsJson() || $request->header('X-Inertia')) {
                return response()->json([
                    'upgrade_required' => true,
                    'feature' => $feature,
                    'message' => $message,
                    'upgrade_url' => route('subscriptions.index'),
                    'modal_props' => [
                        'title' => 'Funcionalidade Premium',
                        'description' => "O recurso {$feature} está disponível nos planos superiores",
                        'requiredFeature' => $feature
                    ]
                ], 402);
            }

            return redirect()->route('subscriptions.index')
                ->with('error', $message);
        }

        return $next($request);
    }

    private function unauthorized(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 401);
        }

        return redirect()->route('dashboard')->with('error', $message);
    }
}