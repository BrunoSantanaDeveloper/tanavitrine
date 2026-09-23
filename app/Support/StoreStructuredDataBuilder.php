<?php

declare(strict_types=1);

namespace App\Support;

use Stringable;
use App\Models\Team;

final class StoreStructuredDataBuilder
{
    /**
     * @param  list<string>  $sameAs
     * @return array<string, mixed>
     */
    public function build(
        Team $store,
        string $canonical,
        string $description,
        string $image,
        array $sameAs = [],
    ): array {
        $isVirtualOnly = mb_strtolower(trim((string) $store->store_type)) === 'virtual';
        $hasPhysicalAddress = filled($store->address) && filled($store->city) && filled($store->state);
        $type = $isVirtualOnly ? 'OnlineStore' : ($hasPhysicalAddress ? 'Store' : 'Organization');

        $schema = array_filter([
            '@type' => $type,
            '@id' => $canonical.'#store',
            'name' => $this->clean($store->name),
            'description' => $description,
            'url' => $canonical,
            'image' => $image,
            'sameAs' => array_values(array_unique(array_filter($sameAs))),
        ], static fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);

        if ($hasPhysicalAddress) {
            $schema['address'] = array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => trim(implode(', ', array_filter([
                    $this->clean($store->address),
                    $this->clean($store->address_number),
                ]))),
                'addressLocality' => $this->clean($store->city),
                'addressRegion' => $this->clean($store->state),
                'postalCode' => $this->clean($store->zip_code),
                'addressCountry' => 'BR',
            ], static fn (mixed $value): bool => $value !== null && $value !== '');
        }

        return $schema;
    }

    private function clean(mixed $value): ?string
    {
        $stringValue = (is_scalar($value) || $value instanceof Stringable)
            ? (string) $value
            : '';
        $clean = trim((string) preg_replace('/\s+/u', ' ', strip_tags($stringValue)));

        return $clean !== '' ? $clean : null;
    }
}
