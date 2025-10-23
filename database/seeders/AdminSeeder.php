<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Models\Team;
use App\Models\Plan;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'name' => 'Gestor Tanavitrine',
            'email' => 'appmanager@tanavitrine.com.br',
            'password' => Hash::make('OXAg839SQ'),
        ]);

        // Atribuir o plano gratuito ao usuário admin por padrão
        $defaultPlan = Plan::where('is_default', true)->first();
        if ($defaultPlan && $user->personalTeam()) {
            $user->personalTeam()->update(['plan_id' => $defaultPlan->id]);
        }
    }
}
