<?php

namespace App\Http\Controllers;

use App\Http\Requests\Building\InviteBuildingMemberRequest;
use App\Models\Building;
use App\Models\User;
use App\Services\BuildingService;
use App\Http\Requests\Building\StoreBuildingRequest;
use App\Http\Requests\Building\UpdateBuildingMemberRoleRequest;
use App\Services\WorkspaceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class BuildingController extends Controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->authorizeResource(Building::class, 'building');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return Inertia::render('buildings/Index', [
            'buildings' => BuildingService::search($request, true),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Building $building)
    {
        return Inertia::render('buildings/Show', [
            'building' => $building,
            'users' => $building->users()->get(),
            'addresses' => $building->addresses()->get(),
        ]);
    }

    /**
     * Display the my buildings page.
     */
    public function my(Request $request)
    {
        $this->authorize('viewMy', Building::class);

        return Inertia::render('buildings/My', [
            'buildings' => $request->user()->managedBuildings()->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBuildingRequest $request)
    {
        BuildingService::save(null, $request->validated());

        return to_route('buildings.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreBuildingRequest $request, Building $building)
    {
        BuildingService::save($building, $request->validated());

        return to_route('buildings.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Building $building)
    {
        BuildingService::delete($building);

        return to_route('buildings.index');
    }

    /**
     * Invite a member to the building.
     */
    public function inviteMember(Building $building, InviteBuildingMemberRequest $request)
    {
        Gate::authorize('inviteMember', $building);

        WorkspaceService::inviteBuildingMember($building, $request->validated());

        return redirect()->back();
    }

    /**
     * Update a building member role.
     */
    public function updateMemberRole(Building $building, User $user, UpdateBuildingMemberRoleRequest $request)
    {
        Gate::authorize('editMember', $building);

        WorkspaceService::updateBuildingMemberRole($building, $user, $request->validated());

        return redirect()->back();
    }

    /**
     * Remove a member from the building.
     */
    public function removeMember(Building $building, User $user)
    {
        Gate::authorize('deleteMember', $building);

        WorkspaceService::removeBuildingMember($building, $user);

        return redirect()->back();
    }
}