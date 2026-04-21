<?php

namespace App\Console\Commands\Bitboss\Upgrade;

use App\Models\User;
use App\Enums\RoleEnum;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class RolesUpgrade extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roles:upgrade';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upgrade the roles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Upgrading roles...');
        foreach (RoleEnum::toArray() as $role) {
            if (!Role::where('name', $role)->exists()) {
                Role::create(['name' => $role]);
            }
        }

        $this->info('Roles upgraded successfully!');
    }
}