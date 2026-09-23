<?php

declare(strict_types=1);

use Sentry\Laravel\Integration;
use Illuminate\Foundation\Application;
use App\Http\Middleware\ApplySeoRobotsHeader;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            ApplySeoRobotsHeader::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'stripe/*',
            'prism/*',
            'storage/*',
        ]);
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        Integration::handles($exceptions); // Intgrate Sentry with Application
        $exceptions->respond(function (SymfonyResponse $response): SymfonyResponse {
            $contentType = mb_strtolower((string) $response->headers->get('Content-Type'));

            if ($response->getStatusCode() >= 400 && str_contains($contentType, 'text/html')) {
                $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
            }

            return $response;
        });
    })->create();
