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

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
                'permissions' => Arr::pluck($user?->getAllPermissions() ?? [], 'name'),
                'workspacePermissions' => $workspacePermissions,
                'workspaceRole' => $role ?? null,
                'impersonating' => Session::has('impersonated_by'),
            ],
            'avatar' => $user?->getAvatarId() ?? null,
            'workspace' => $workspace ?? null,
            'user_meta' => $userMeta,
        ];
    }
}