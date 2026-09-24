<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Team;
use Illuminate\Database\Eloquent\Model;

final class CategoryStoreCardBuilder
{
    /** @return array<string, mixed> */
    public function build(Team $store): array
    {
        $photos = $store->photos
            ->values()
            ->map(static function (Model $photo): string {
                $url = $photo->getAttribute('url');

                return is_string($url) ? $url : '';
            })
            ->filter()
            ->all();

        return [
            'id' => $store->id,
            'code' => 'TV'.mb_str_pad((string) $store->id, 4, '0', STR_PAD_LEFT),
            'slug' => $store->slug,
            'url' => route('store.show', $store->slug),
            'badge' => ucfirst((string) $store->sale_type),
            'name' => $store->name,
            'category' => $store->category?->getAttribute('name'),
            'subcategory' => $store->subcategory,
            'gender' => $store->gender,
            'is_manufacturer' => (bool) $store->is_manufacturer,
            'description' => $store->description,
            'saleType' => ucfirst((string) $store->sale_type),
            'storeType' => ucfirst((string) $store->store_type),
            'minOrder' => StoreMinimumOrder::label($store->min_order),
            'location' => $store->city && $store->state ? "{$store->city} - {$store->state}" : null,
            'city' => $store->city,
            'state' => $store->state,
            'whatsapp' => $store->whatsapp,
            'logo' => $store->logo_path ? asset('storage/'.$store->logo_path) : null,
            'image' => $photos[0] ?? null,
            'images' => $photos,
            'is_verified' => $store->isVerified(),
            'featured' => $store->isFeatured(),
            'show_on_map' => false,
            'can_favorite' => auth()->check(),
            'is_favorited' => false,
        ];
    }
}
