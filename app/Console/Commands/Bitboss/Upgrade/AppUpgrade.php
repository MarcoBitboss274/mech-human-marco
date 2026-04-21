<?php

namespace App\Console\Commands\Bitboss\Upgrade;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AppUpgrade extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:upgrade';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upgrade the application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            DB::beginTransaction();
            $this->call('roles:upgrade');
            $this->call('permissions:upgrade');
            DB::commit();
            $this->info('Application upgraded successfully!');
            return Command::SUCCESS;
        } catch (Exception $ex) {
            DB::rollBack();
            $this->error('Error upgrading the application: ' . $ex->getMessage());
            return Command::FAILURE;
        }
    }
}
