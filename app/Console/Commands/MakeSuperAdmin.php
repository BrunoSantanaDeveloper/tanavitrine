<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:superadmin {email : The email of the user to promote}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Promote a user to superadmin to access Filament admin panel';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User with email {$email} not found.");
            return self::FAILURE;
        }

        if ($user->is_superadmin) {
            $this->info("User {$user->name} ({$email}) is already a superadmin.");
            return self::SUCCESS;
        }

        $user->is_superadmin = true;
        $user->save();

        $this->info("User {$user->name} ({$email}) has been promoted to superadmin.");
        $this->info("They can now access the admin panel at /admin");

        return self::SUCCESS;
    }
}
