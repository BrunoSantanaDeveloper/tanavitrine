<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('teams')
            ->select(['id'])
            ->where(function ($query): void {
                $query->whereNull('slug')
                    ->orWhereRaw("TRIM(slug) = ''");
            })
            ->orderBy('id')
            ->get()
            ->each(function (object $team): void {
                $baseSlug = 'loja-recuperada-'.$team->id;
                $slug = $baseSlug;
                $count = 1;

                while (DB::table('teams')
                    ->where('id', '!=', $team->id)
                    ->where('slug', $slug)
                    ->exists()) {
                    $slug = $baseSlug.'-'.$count;
                    $count++;
                }

                DB::table('teams')
                    ->where('id', $team->id)
                    ->update(['slug' => $slug]);
            });
    }

    public function down(): void
    {
        // Slugs inválidos não devem ser restaurados.
    }
};
