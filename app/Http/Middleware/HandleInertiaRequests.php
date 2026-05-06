<?php

namespace App\Http\Middleware;

use App\Models\Building;
use Inertia\Middleware;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Services\WorkspaceAuthorizationService;
use App\Services\WorkspacePermissionMap;
use App\Services\WorkspaceService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Session;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $workspace = WorkspaceService::getCurrentWorkspace();
        $userMeta = Session::get('user_meta', null);

        if (is_null($userMeta)) {
            if ($user) {
                $user->setupSessionData();
            }
            $userMeta = Session::get('user_meta', null);
        }

        $workspacePermissions = null;
        if ($user?->isCustomer() && $workspace instanceof Building) {
            $role = app(WorkspaceAuthorizationService::class)->roleInBuilding($user, $workspace);
            $workspacePermissions = $role ? WorkspacePermissionMap::abilitiesFor($role) : [];
        }

        $supplierWorkspacePermissions = null;
        $supplierRole = null;
        if ($user?->isSupplier()) {
            $supplier = $user->suppliers()->first();
            if ($supplier !== null) {
                $authService = app(\App\Services\SupplierWorkspaceAuthorizationService::class);
                $supplierEnumRole = $authService->roleForUser($user, $supplier);
                if ($supplierEnumRole !== null) {
                    $supplierRole = $supplierEnumRole->value;
                    $supplierWorkspacePermissions = \App\Services\SupplierWorkspacePermissionMap::abilitiesFor($supplierEnumRole);
                }
            }
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
                'permissions' => Arr::pluck($user?->getAllPermissions() ?? [], 'name'),
                'workspacePermissions' => $workspacePermissions,
                'workspaceRole' => $role ?? null,
                'supplierWorkspacePermissions' => $supplierWorkspacePermissions,
                'supplier_role' => $supplierRole,
                'impersonating' => Session::has('impersonated_by'),
            ],
            'avatar' => $user?->getAvatarId() ?? null,
            'workspace' => $workspace ?? null,
            'user_meta' => $userMeta,
        ];
    }
}