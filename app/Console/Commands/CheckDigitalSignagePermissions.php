<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Permission;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckDigitalSignagePermissions extends Command
{
    protected $signature = 'digital-signage:check-permissions';
    protected $description = 'Check and fix media management permissions';

    public function handle()
    {
        $this->info('Checking media management permissions...');

        // Permissões necessárias para gerenciamento de mídia
        $requiredPermissions = [
            'media:manage',
            'media:view',
            'media:create',
            'media:update',
            'media:delete'
        ];

        // Verifica e cria permissões se necessário
        foreach ($requiredPermissions as $permission) {
            $perm = Permission::firstOrCreate(
                ['slug' => $permission],
                [
                    'name' => ucwords(str_replace(':', ' ', $permission)),
                    'description' => 'Permission to ' . str_replace(':', ' ', $permission) . ' media'
                ]
            );

            $this->info("Permission {$permission} checked");
        }

        // Atribui permissões a todos os times
        $teams = Team::all();
        foreach ($teams as $team) {
            foreach ($requiredPermissions as $permission) {
                $perm = Permission::where('slug', $permission)->first();
                if ($perm) {
                    DB::table('team_permissions')->updateOrInsert(
                        [
                            'team_id' => $team->id,
                            'permission_id' => $perm->id
                        ],
                        [
                            'created_at' => now(),
                            'updated_at' => now()
                        ]
                    );
                }
            }
            $this->info("Permissions assigned to team {$team->name}");
        }

        $this->info('Media management permissions check completed');
    }
}
