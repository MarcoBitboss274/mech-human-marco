<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Enums\RoleEnum;
use Illuminate\Http\Request;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Show the home page.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        if (! Auth::check()) return to_route('login');

        return to_route('dashboard');
    }

    /**
     * Show the dashboard page.
     *
     * @return \Illuminate\Http\Response
     */
    public function dashboard()
    {
        $user = UserService::currentUser();

        if ($user->isCustomer()) {
            return to_route('workspace.index');
        }

        if ($user->isSupplier()) {
            return to_route('workspace.supplier.index');
        }

        return to_route('operations.index', ['mode' => 'active']);
    }
}