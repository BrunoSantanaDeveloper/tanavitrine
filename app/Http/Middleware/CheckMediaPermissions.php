<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMediaPermissions
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'Usuário não autenticado');
        }

        if (!$user->currentTeam) {
            abort(403, 'Usuário não está em um time');
        }

        return $next($request);
    }
}
