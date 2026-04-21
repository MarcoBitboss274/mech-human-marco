<?php

namespace App\Providers;

use App\Models\Building;
use App\Models\User;
use App\Policies\UserPolicy;
use App\Services\WorkspaceAuthorizationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Superadmin gate
        Gate::before(function ($user, $ability) {
            return $user->isSuperadmin() ? true : null;
        });

        Gate::define('workspaceAbility', function (User $user, Building $building, string $ability): bool {
            return app(WorkspaceAuthorizationService::class)->canInBuilding($user, $building, $ability);
        });

        // Setup Carbon
        Carbon::setLocale(config('app.locale', 'it'));
    }
}