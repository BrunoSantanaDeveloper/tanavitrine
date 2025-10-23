<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\ResourceLimitService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckResourceLimit
{
    public function __construct(
        private readonly ResourceLimitService $resourceLimitService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module, string $resource): Response
    {
        $user = Auth::user();
        $team = $user?->currentTeam;

        if (!$team) {
            return $this->unauthorized($request, 'Team not found');
        }

        $hasReachedLimit = $this->resourceLimitService->hasReachedLimit($team, $module, $resource);
        
        if ($hasReachedLimit) {
            $currentUsage = $this->resourceLimitService->getCurrentUsage($team, $module, $resource);
            $limit = $this->resourceLimitService->getLimit($team, $module, $resource);
            $planName = $team->plan?->name ?? 'Gratuito';

            Log::info('Resource limit reached', [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'module' => $module,
                'resource' => $resource,
                'current_usage' => $currentUsage,
                'limit' => $limit?->limit_value,
                'plan' => $planName,
            ]);

            $message = "Limite atingido: {$currentUsage}/{$limit->limit_value} {$resource}. Faça upgrade do seu plano para continuar.";

            if ($request->expectsJson() || $request->header('X-Inertia')) {
                return response()->json([
                    'message' => $message,
                    'errors' => [
                        'general' => [$message]
                    ],
                    'module' => $module,
                    'resource' => $resource,
                    'current_usage' => $currentUsage,
                    'limit' => $limit->limit_value,
                    'plan' => $planName,
                    'upgrade_url' => route('subscriptions.index'),
                    'upgrade_required' => true,
                ], 422);
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