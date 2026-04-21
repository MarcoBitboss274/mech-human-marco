<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Services\UserService;
use App\Http\Requests\Profile\StoreProfileRequest;

class ProfileController extends Controller
{
    /**
     * Show the profile page
     *
     */
    public function index()
    {
        return Inertia::render('profile/Index');
    }

    /**
     * Update the profile
     *
     */
    public function update(StoreProfileRequest $request)
    {
        $user = UserService::currentUser();
        UserService::save($user, $request->validated());

        return to_route('profile.index');
    }
}
