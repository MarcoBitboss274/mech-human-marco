<?php

namespace App\Console\Commands\Bitboss\Setup;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AppSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup the application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            DB::beginTransaction();

            $this->call('roles:upgrade');

            $this->call('users:setup');

            DB::commit();
            $this->info('Application setup successfully!');
        } catch (Exception $ex) {
            DB::rollBack();
            $this->error('Error setting up the application: ' . $ex->getMessage());
        }
    }
}