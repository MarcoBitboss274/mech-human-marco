<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Services\UserService;
use App\Http\Requests\User\StoreUserRequest;

class UserController extends Controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $with = [
            'buildings' => fn($q) => $q->select('buildings.id', 'buildings.name'),
            'managedBuildings' => fn($q) => $q->select('buildings.id', 'buildings.name', 'buildings.agent_id'),
            'suppliers' => fn($q) => $q->select('suppliers.id', 'suppliers.name'),
        ];

        return Inertia::render('users/Index', [
            'users' => UserService::search($request, true, $with),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        UserService::save(null, $request->validated());
        return to_route('users.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreUserRequest $request, User $user)
    {
        UserService::save($user, $request->validated());
        return to_route('users.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        UserService::delete($user);
        return to_route('users.index');
    }

    /**
     * Invite a user
     */
    public function invite(User $user)
    {
        UserService::sendWelcomeEmail($user);
        return to_route('users.index');
    }
}