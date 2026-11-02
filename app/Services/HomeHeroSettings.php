<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

final class HomeHeroSettings
{
    public const KEY = 'home_hero';

    public function defaults(): array
    {
        return [
            'badge' => 'Fornecedores Verificados',
            'title' => 'Encontre fornecedores',
            'highlight' => 'em poucos cliques',
            'description' => 'Tá na Vitrine é o maior catálogo de fornecedores de moda do Brasil, com atacado e varejo e contato direto com quem vende.',
            'button_label' => 'Buscar lojas',
            'background_type' => 'color',
            'image_path' => null,
            'video_path' => null,
            'poster_path' => null,
            'background_color' => '#ffffff',
            'title_color' => '#042f2e',
            'highlight_color' => '#0f766e',
            'description_color' => '#475569',
            'button_color' => '#115e59',
            'button_text_color' => '#ffffff',
            'overlay_enabled' => true,
            'overlay_color' => '#000000',
            'overlay_opacity' => 40,
        ];
    }

    public function get(): array
    {
        $saved = Setting::getValueByKey(self::KEY, []);

        return array_replace($this->defaults(), is_array($saved) ? array_intersect_key($saved, $this->defaults()) : []);
    }

    public function save(array $data): void
    {
        $current = $this->get();
        $settings = array_replace($this->defaults(), array_intersect_key($data, $this->defaults()));
        $settings['poster_path'] = $settings['video_path'] === $current['video_path'] ? $current['poster_path'] : null;

        if ($settings['background_type'] === 'video' && $settings['video_path']) {
            $settings['poster_path'] = app(HomeHeroPoster::class)->generate($settings['video_path']);
        }

        Setting::setValueByKey(self::KEY, $settings, 'appearance', 'json');
    }

    public function forFrontend(): array
    {
        $settings = $this->get();

        foreach (['image', 'video', 'poster'] as $type) {
            $path = $settings[$type.'_path'];
            $settings[$type.'_url'] = $path ? Storage::disk('public')->url($path) : null;
            unset($settings[$type.'_path']);
        }

        return $settings;
    }
}
