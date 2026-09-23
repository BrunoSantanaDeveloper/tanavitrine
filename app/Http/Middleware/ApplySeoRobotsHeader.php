<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use App\Support\SeoMeta;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApplySeoRobotsHeader
{
    public function __construct(private readonly SeoMeta $seoMeta) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);
        $contentType = mb_strtolower((string) $response->headers->get('Content-Type'));

        if (
            in_array($request->getMethod(), ['GET', 'HEAD'], true)
            && str_contains($contentType, 'text/html')
            && (! $response->isSuccessful() || ! $this->seoMeta->isRouteIndexable($request))
        ) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        }

        return $response;
    }
}
