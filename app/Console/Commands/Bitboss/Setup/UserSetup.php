<?php

namespace App\Console\Commands\Bitboss\Setup;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class UserSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup the users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating super admin user...');
        if (User::where('email', 'admin@bitboss.it')->exists()) {
            $this->info('Super admin user already exists...');
        } else {
            $user = User::create([
                'name' => 'Bitboss',
                'surname' => 'Bitboss',
                'email' => 'admin@bitboss.it',
                'password' => Hash::make('bitboss_'),
            ]);
            $user->email_verified_at = now();
            $user->save();
            $user->makeSuperadmin();
        }
    }
}