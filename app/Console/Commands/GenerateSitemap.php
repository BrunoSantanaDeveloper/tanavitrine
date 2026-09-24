<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\Category;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

final class GenerateSitemap extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the sitemap for your file.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $sitemap = Sitemap::create();
        $baseUrl = $this->resolveBaseUrl();

        $staticPages = [
            ['path' => '/', 'frequency' => Url::CHANGE_FREQUENCY_DAILY, 'priority' => 1.0],
            ['path' => '/atacado', 'frequency' => Url::CHANGE_FREQUENCY_DAILY, 'priority' => 0.9],
            ['path' => '/varejo', 'frequency' => Url::CHANGE_FREQUENCY_DAILY, 'priority' => 0.9],
            ['path' => '/fabricantes', 'frequency' => Url::CHANGE_FREQUENCY_DAILY, 'priority' => 0.9],
            ['path' => '/prices', 'frequency' => Url::CHANGE_FREQUENCY_WEEKLY, 'priority' => 0.8],
            ['path' => '/about', 'frequency' => Url::CHANGE_FREQUENCY_WEEKLY, 'priority' => 0.7],
            ['path' => '/privacy-policy', 'frequency' => Url::CHANGE_FREQUENCY_MONTHLY, 'priority' => 0.5],
            ['path' => '/terms-of-service', 'frequency' => Url::CHANGE_FREQUENCY_MONTHLY, 'priority' => 0.5],
        ];

        foreach ($staticPages as $page) {
            $sitemap->add(
                Url::create($this->absoluteUrl($baseUrl, $page['path']))
                    ->setChangeFrequency($page['frequency'])
                    ->setPriority($page['priority'])
            );
        }

        $this->addCategoryPages($sitemap, $baseUrl);

        // Generate store detail URLs dynamically from active stores.
        if (Schema::hasTable('teams')) {
            $stores = Team::query()
                ->where('personal_team', false)
                ->active()
                ->whereNotNull('slug')
                ->where('slug', '!=', '')
                ->select(['slug', 'updated_at'])
                ->orderByDesc('updated_at')
                ->get();

            foreach ($stores as $store) {
                $url = Url::create($this->absoluteUrl($baseUrl, "/loja/{$store->slug}"))
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                    ->setPriority(0.8);

                if ($store->updated_at !== null) {
                    $url->setLastModificationDate($store->updated_at);
                }

                $sitemap->add($url);
            }
        }

        $sitemap->writeToFile(public_path('sitemap.xml'));
    }

    private function addCategoryPages(Sitemap $sitemap, string $baseUrl): void
    {
        if (
            ! (bool) config('seo.category_pages_enabled', false)
            || ! (bool) config('seo.category_indexing_enabled', false)
            || ! Schema::hasTable('categories')
            || ! Schema::hasTable('teams')
        ) {
            return;
        }

        $configuredMinimum = config('seo.category_min_stores', 3);
        $minimumStores = max(1, is_numeric($configuredMinimum) ? (int) $configuredMinimum : 3);
        $storeCounts = Team::query()
            ->active()
            ->where('personal_team', false)
            ->whereNotNull('category_id')
            ->select('category_id')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('category_id')
            ->get()
            ->mapWithKeys(static function (Team $store): array {
                $count = $store->getAttribute('aggregate');

                return [(int) $store->category_id => is_numeric($count) ? (int) $count : 0];
            });

        $categories = Category::query()
            ->active()
            ->parents()
            ->orderBy('sort_order')
            ->get()
            ->filter(static fn (Category $category): bool => ($storeCounts[$category->id] ?? 0) >= $minimumStores);

        if ($categories->isEmpty()) {
            return;
        }

        $sitemap->add(
            Url::create($this->absoluteUrl($baseUrl, '/categorias'))
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(0.8)
        );

        foreach ($categories as $category) {
            $url = Url::create($this->absoluteUrl($baseUrl, "/categoria/{$category->slug}"))
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                ->setPriority(0.8);

            if ($category->updated_at !== null) {
                $url->setLastModificationDate($category->updated_at);
            }

            $sitemap->add($url);
        }
    }

    private function resolveBaseUrl(): string
    {
        $configuredBaseUrl = trim((string) config('sitemap.base_url', ''));

        if ($configuredBaseUrl === '') {
            $configuredBaseUrl = trim((string) config('seo.site_url', config('app.url', '')));
        }

        $normalizedBaseUrl = $this->normalizeBaseUrl($configuredBaseUrl);
        if ($normalizedBaseUrl !== null) {
            return $normalizedBaseUrl;
        }

        $fallbackBaseUrl = 'https://tanavitrine.com.br';
        $this->warn("Sitemap base URL inválida ({$configuredBaseUrl}). Usando fallback {$fallbackBaseUrl}.");

        return $fallbackBaseUrl;
    }

    private function normalizeBaseUrl(string $baseUrl): ?string
    {
        $trimmed = rtrim(trim($baseUrl), '/');
        if ($trimmed === '' || ! filter_var($trimmed, FILTER_VALIDATE_URL)) {
            return null;
        }

        $host = parse_url($trimmed, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return null;
        }

        if (in_array(mb_strtolower($host), ['localhost', '127.0.0.1', '0.0.0.0'], true)) {
            return null;
        }

        return $trimmed;
    }

    private function absoluteUrl(string $baseUrl, string $path): string
    {
        $baseUrl = rtrim($baseUrl, '/');

        if ($path === '/') {
            return $baseUrl;
        }

        return $baseUrl.'/'.ltrim($path, '/');
    }
}
