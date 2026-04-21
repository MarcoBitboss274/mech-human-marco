<?php

namespace App\Http\Middleware;

use App\Services\UserService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class OnBoarding
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = UserService::currentUser();

        if ($user->isCustomer()) {
            $user->setupSessionData();
            $meta = Session::get('user_meta', null);

            if ((int) ($meta['customer_buildings_count'] ?? 0) === 0) {
                return to_route('workspace.create-building');
            }
        }

        return $next($request);
    }
}