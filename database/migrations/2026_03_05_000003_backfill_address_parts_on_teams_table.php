<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('teams')
            ->select(['id', 'address', 'address_number', 'address_complement'])
            ->whereNotNull('address')
            ->where('address', 'like', '%,%')
            ->orderBy('id')
            ->chunkById(200, function ($teams): void {
                foreach ($teams as $team) {
                    $address = trim((string) $team->address);
                    if ($address === '') {
                        continue;
                    }

                    $parts = array_values(array_filter(array_map('trim', explode(',', $address)), static fn ($value) => $value !== ''));
                    if (count($parts) < 2) {
                        continue;
                    }

                    $street = $parts[0] ?? null;
                    $secondPart = $parts[1] ?? null;

                    $number = $team->address_number;
                    if (empty($number) && $secondPart && preg_match('/^(s\\/n|\\d+[a-zA-Z0-9\\-\\/]*)$/i', $secondPart)) {
                        $number = $secondPart;
                    }

                    DB::table('teams')
                        ->where('id', $team->id)
                        ->update([
                            'address' => $street,
                            'address_number' => $number,
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Irreversível: migração de normalização de dados.
    }
};
