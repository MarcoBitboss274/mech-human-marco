<?php

namespace App\Http\Middleware;

use App\Services\UserService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the authenticated supplier user is associated to a Supplier team.
 * If not (orphan), the user is redirected to the dedicated orphan page.
 */
class WorkspaceSupplier
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = UserService::currentUser();

        if (! $user) {
            return abort(404);
        }

        if (! $user->isSupplier()) {
            return abort(404);
        }

        $supplier = $user->suppliers()->first();
        if ($supplier === null) {
            // Skip the redirect if we are already heading to the orphan page (avoid infinite loop).
            if ($request->routeIs('workspace.supplier.orphan')) {
                return $next($request);
            }
            return redirect()->route('workspace.supplier.orphan');
        }

        return $next($request);
    }
}
