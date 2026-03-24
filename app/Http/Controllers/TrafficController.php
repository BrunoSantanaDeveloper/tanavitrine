<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TrafficEvent;
use App\Models\TrafficPresence;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TrafficController extends Controller
{
    private const PAGE_VIEW_DEDUP_SECONDS = 20;

    public function track(Request $request): JsonResponse
    {
        $data = $request->validate([
            'visitor_id' => ['required', 'string', 'min:16', 'max:64'],
            'event_type' => ['required', 'string', 'in:page_view,heartbeat'],
            'path' => ['required', 'string', 'max:255'],
            'page_type' => ['nullable', 'string', 'max:40'],
            'team_slug' => ['nullable', 'string', 'max:255'],
        ]);

        $path = $this->normalizePath($data['path']);
        if (!$this->isTrackablePath($path)) {
            return response()->json(['tracked' => false, 'reason' => 'path_not_trackable']);
        }

        if ($this->isBot($request) || $this->isInternalTraffic($request)) {
            return response()->json(['tracked' => false, 'reason' => 'ignored']);
        }

        $now = CarbonImmutable::now();
        $pageType = $this->resolvePageType($path, $data['page_type'] ?? null);
        $deviceType = $this->resolveDeviceType((string) $request->userAgent());
        $ipHash = $this->hashIp((string) $request->ip());
        $teamId = $this->resolveTeamId($data['team_slug'] ?? null, $path);
        $visitorId = $this->sanitizeVisitorId($data['visitor_id']);

        if ($data['event_type'] === 'page_view' && !$this->isDuplicatePageView($visitorId, $path, $now)) {
            TrafficEvent::query()->create([
                'visitor_id' => $visitorId,
                'ip_hash' => $ipHash,
                'device_type' => $deviceType,
                'path' => $path,
                'page_type' => $pageType,
                'team_id' => $teamId,
                'occurred_at' => $now,
            ]);
        }

        $presence = TrafficPresence::query()->firstOrNew([
            'visitor_id' => $visitorId,
        ]);

        if (!$presence->exists) {
            $presence->first_seen_at = $now;
        }

        $presence->fill([
            'ip_hash' => $ipHash,
            'device_type' => $deviceType,
            'current_path' => $path,
            'page_type' => $pageType,
            'current_team_id' => $teamId,
            'last_seen_at' => $now,
        ]);

        $presence->save();

        return response()->json(['tracked' => true]);
    }

    private function sanitizeVisitorId(string $visitorId): string
    {
        $sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '', $visitorId);

        return substr((string) $sanitized, 0, 64);
    }

    private function normalizePath(string $path): string
    {
        $trimmed = trim($path);
        if ($trimmed === '') {
            return '/';
        }

        if (!str_starts_with($trimmed, '/')) {
            $trimmed = '/' . $trimmed;
        }

        $withoutQuery = explode('?', explode('#', $trimmed)[0])[0] ?? '/';

        return $withoutQuery !== '' ? $withoutQuery : '/';
    }

    private function isTrackablePath(string $path): bool
    {
        if ($path === '/') {
            return true;
        }

        if (in_array($path, ['/atacado', '/varejo', '/prices', '/about'], true)) {
            return true;
        }

        return str_starts_with($path, '/loja/');
    }

    private function resolvePageType(string $path, ?string $provided): string
    {
        if ($provided && in_array($provided, ['home', 'atacado', 'varejo', 'prices', 'about', 'store'], true)) {
            return $provided;
        }

        return match (true) {
            $path === '/' => 'home',
            $path === '/atacado' => 'atacado',
            $path === '/varejo' => 'varejo',
            $path === '/prices' => 'prices',
            $path === '/about' => 'about',
            str_starts_with($path, '/loja/') => 'store',
            default => 'other',
        };
    }

    private function resolveDeviceType(string $userAgent): string
    {
        $ua = strtolower($userAgent);

        if ($ua === '') {
            return 'unknown';
        }

        if (str_contains($ua, 'ipad') || str_contains($ua, 'tablet')) {
            return 'tablet';
        }

        if (str_contains($ua, 'mobi') || str_contains($ua, 'android')) {
            return 'mobile';
        }

        return 'desktop';
    }

    private function isBot(Request $request): bool
    {
        $ua = strtolower((string) $request->userAgent());

        if ($ua === '') {
            return true;
        }

        return (bool) preg_match('/bot|crawler|spider|slurp|curl|wget|headless|facebookexternalhit|preview|monitor|uptime|pingdom/', $ua);
    }

    private function isInternalTraffic(Request $request): bool
    {
        $user = $request->user();

        return (bool) ($user?->is_superadmin ?? false);
    }

    private function hashIp(string $ip): ?string
    {
        $trimmed = trim($ip);
        if ($trimmed === '') {
            return null;
        }

        return hash_hmac('sha256', $trimmed, (string) config('app.key'));
    }

    private function resolveTeamId(?string $teamSlug, string $path): ?int
    {
        $slug = $teamSlug;

        if (!$slug && str_starts_with($path, '/loja/')) {
            $slug = explode('/', trim(substr($path, 6), '/'))[0] ?? null;
        }

        if (!$slug) {
            return null;
        }

        return Team::query()
            ->where('slug', $slug)
            ->value('id');
    }

    private function isDuplicatePageView(string $visitorId, string $path, CarbonImmutable $now): bool
    {
        return TrafficEvent::query()
            ->where('visitor_id', $visitorId)
            ->where('path', $path)
            ->where('occurred_at', '>=', $now->subSeconds(self::PAGE_VIEW_DEDUP_SECONDS))
            ->exists();
    }
}
