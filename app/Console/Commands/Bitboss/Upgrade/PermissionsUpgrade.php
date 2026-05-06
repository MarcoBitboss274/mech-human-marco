<?php

namespace App\Console\Commands\Bitboss\Upgrade;

use App\Enums\RoleEnum;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionsUpgrade extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:upgrade';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upgrade the permissions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Upgrading permissions...');

        app('Spatie\Permission\PermissionRegistrar')->forgetCachedPermissions();

        // clean permissions
        DB::table('model_has_permissions')->delete();
        DB::table('role_has_permissions')->delete();
        Permission::query()->delete();

        $permissions = [

            // Users
            'users.index',
            'users.create',
            'users.edit',
            'users.destroy',
            'users.invite',
            'users.impersonate',

            // Buildings
            'buildings.index',
            'buildings.create',
            'buildings.edit',
            'buildings.destroy',
            'buildings.members.invite',
            'buildings.members.edit',
            'buildings.members.delete',

            // Addresses
            'addresses.index',
            'addresses.create',
            'addresses.edit',
            'addresses.destroy',

            // Operations
            'operations.index',
            'operations.index.supplier',
            'operations.index.sent_at',
            'operations.view-all',
            'operations.create',
            'operations.edit',
            'operations.destroy',
            'operations.supplier.view',
            'operations.supplier.manage',
            'operations.quote.view',
            'operations.quote.manage',
            'operations.order.view',
            'operations.order.manage',
            'operations.production.view',
            'operations.production.manage',
            'operations.invoice.view',
            'operations.invoice.manage',
            'operations.activity.view',
            'operations.cancel',
            'operations.archive',
            'operations.export',

            // Chat
            'chat.read',
            'chat.send',

            // Prescriptions
            'prescriptions.index',
            'prescriptions.create',
            'prescriptions.edit',
            'prescriptions.destroy',

            // Quotes
            'quotes.index',
            'quotes.create',
            'quotes.edit',
            'quotes.destroy',

            // Orders
            'orders.index',
            'orders.create',
            'orders.edit',
            'orders.destroy',

            // Invoices
            'invoices.index',
            'invoices.create',
            'invoices.edit',
            'invoices.destroy',

            // Suppliers
            'suppliers.index',
            'suppliers.show',
            'suppliers.create',
            'suppliers.edit',
            'suppliers.destroy',
            'suppliers.members.manage',

            // Productions
            'productions.index',
            'productions.create',
            'productions.edit',
            'productions.destroy',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Admin
        $admin = Role::query()->where('name', RoleEnum::ADMIN->value)->first();
        if ($admin) {
            $admin->givePermissionTo(array_diff($permissions, []));
        }

        // Agent
        $agent = Role::query()->where('name', RoleEnum::AGENT->value)->first();
        if ($agent) {
            $agent->givePermissionTo([
                'operations.index',
                'operations.index.sent_at',
            ]);
        }

        // Customer
        $customer = Role::query()->where('name', RoleEnum::CUSTOMER->value)->first();
        if ($customer) {
            $customer->givePermissionTo([
                'chat.read',
                'chat.send',
            ]);
        }

        $this->info('Permissions upgraded successfully!');
    }
}
