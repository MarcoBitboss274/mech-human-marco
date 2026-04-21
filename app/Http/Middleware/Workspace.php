<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Building;
use Illuminate\Http\Request;
use App\Services\UserService;
use Symfony\Component\HttpFoundation\Response;

class Workspace
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $building = is_string($request->route('building'))
            ? Building::where('slug', $request->route('building'))->first()
            : $request->route('building');

        if (! $building instanceof Building) {
            return to_route('workspace.index');
        }

        if ($building->approved !== true) {
            return to_route('workspace.index');
        }

        $user = UserService::currentUser();

        if (! $user) {
            return abort(404);
        }

        if (! $user->canAccessBuilding($building)) {
            return abort(404);
        }

        return $next($request);
    }
}