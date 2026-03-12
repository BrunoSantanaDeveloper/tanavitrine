<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

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
        $now = now();

        $staticPages = [
            ['path' => '/', 'frequency' => Url::CHANGE_FREQUENCY_DAILY, 'priority' => 1.0],
            ['path' => '/atacado', 'frequency' => Url::CHANGE_FREQUENCY_DAILY, 'priority' => 0.9],
            ['path' => '/varejo', 'frequency' => Url::CHANGE_FREQUENCY_DAILY, 'priority' => 0.9],
            ['path' => '/prices', 'frequency' => Url::CHANGE_FREQUENCY_WEEKLY, 'priority' => 0.8],
            ['path' => '/about', 'frequency' => Url::CHANGE_FREQUENCY_WEEKLY, 'priority' => 0.7],
            ['path' => '/privacy-policy', 'frequency' => Url::CHANGE_FREQUENCY_MONTHLY, 'priority' => 0.5],
            ['path' => '/terms-of-service', 'frequency' => Url::CHANGE_FREQUENCY_MONTHLY, 'priority' => 0.5],
        ];

        foreach ($staticPages as $page) {
            $sitemap->add(
                Url::create($this->absoluteUrl($page['path']))
                    ->setLastModificationDate($now)
                    ->setChangeFrequency($page['frequency'])
                    ->setPriority($page['priority'])
            );
        }

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
                $sitemap->add(
                    Url::create($this->absoluteUrl("/loja/{$store->slug}"))
                        ->setLastModificationDate($store->updated_at ?? $now)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                        ->setPriority(0.8)
                );
            }
        }

        $sitemap->writeToFile(public_path('sitemap.xml'));
    }

    private function absoluteUrl(string $path): string
    {
        $baseUrl = rtrim(config('app.url', 'https://tanavitrine.com.br'), '/');

        if ($path === '/') {
            return $baseUrl;
        }

        return $baseUrl . '/' . ltrim($path, '/');
    }
}
