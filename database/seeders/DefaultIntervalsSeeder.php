<?php

namespace Database\Seeders;

use App\Models\Interval;
use Illuminate\Database\Seeder;

class DefaultIntervalsSeeder extends Seeder
{
    public function run(): void
    {
        $intervals = [
            [
                'name' => 'Mensal',
                'code' => 'month',
                'description' => 'Cobrança mensal',
                'is_active' => true,
            ],
        ];

        foreach ($intervals as $interval) {
            Interval::updateOrCreate(
                ['code' => $interval['code']],
                $interval
            );
        }
    }
}
