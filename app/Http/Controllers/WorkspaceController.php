<?php

namespace App\Http\Controllers;

use App\Http\Requests\Workspace\InviteBuildingMemberRequest;
use App\Http\Requests\Workspace\StoreAddressRequest;
use App\Http\Requests\Workspace\StoreBuildingRequest;
use App\Http\Requests\Workspace\UpdateBuildingMemberRoleRequest;
use App\Http\Requests\Workspace\UpdateBuildingRequest;
use App\Http\Requests\Workspace\UpdateWorkspaceProfileRequest;
use App\Http\Requests\Quote\AcceptQuoteRequest;
use App\Models\Address;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Operation;
use App\Models\Order;
use App\Models\Prescription;
use App\Models\Quote;
use App\Models\User;
use App\Http\Requests\Quote\RejectQuoteRequest;
use App\Http\Requests\Operation\StoreOperationWithPrescriptionRequest;
use App\Http\Requests\Operation\UpdateOperationWithPrescriptionRequest;
use App\Enums\WorkspaceAbilityEnum;
use App\Enums\BuildingUserRoleEnum;
use App\Services\BuildingService;
use App\Services\OperationService;
use App\Services\PrescriptionService;
use App\Services\QuoteService;
use App\Services\UserService;
use App\Services\WorkspaceService;
use App\Services\WorkspaceAuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class WorkspaceController extends Controller
{
    /**
     * Redirect to the dashboard
     */
    public function index()
    {
        return to_route('workspace.dashboard');
    }

    /**
     * Show the dashboard
     */
    public function dashboard()
    {
        $user = UserService::currentUser();

        return Inertia::render('workspace/Dashboard', [
            'buildings' => $user->buildings,
        ]);
    }

    /**
     * Show the create building page
     */
    public function createBuilding()
    {
        return Inertia::render('workspace/CreateBuilding');
    }

    /**
     * Store a new building from workspace (awaiting admin approval)
     */
    public function storeBuilding(StoreBuildingRequest $request)
    {
        $user = UserService::currentUser();
        BuildingService::createForWorkspaceUser($user, $request->validated());

        return to_route('workspace.dashboard');
    }

    /**
     * Show the profile page
     */
    public function profile()
    {
        return Inertia::render('workspace/profile/Index');
    }

    /**
     * Update the profile
     */
    public function updateProfile(UpdateWorkspaceProfileRequest $request)
    {
        $user = UserService::currentUser();
        UserService::save($user, $request->validated());

        return to_route('workspace.profile.index');
    }

    /**
     * Show the building index page
     */
    public function buildingIndex(Building $building)
    {
        return Inertia::render('workspace/building/Index', WorkspaceService::getWorkspaceBuildingShowData($building));
    }

    /**
     * Update a workspace building.
     */
    public function updateBuilding(Building $building, UpdateBuildingRequest $request)
    {
        WorkspaceService::updateBuilding($building, $request->validated());

        return redirect()->back();
    }

    /**
     * Remove a member from the building
     */
    public function removeBuildingMember(Building $building, User $user)
    {
        Gate::authorize('workspaceAbility', [$building, WorkspaceAbilityEnum::BUILDING_MEMBERS_REMOVE->value]);

        WorkspaceService::removeBuildingMember($building, $user);

        return redirect()->back();
    }

    /**
     * Update the role of a building member.
     */
    public function updateBuildingMemberRole(Building $building, User $user, UpdateBuildingMemberRoleRequest $request)
    {
        WorkspaceService::updateBuildingMemberRole($building, $user, $request->validated());

        return redirect()->back();
    }

    /**
     * Invite a member to the building
     */
    public function inviteBuildingMember(Building $building, InviteBuildingMemberRequest $request)
    {
        WorkspaceService::inviteBuildingMember($building, $request->validated());

        return redirect()->back();
    }

    /**
     * Store a new address for the building.
     */
    public function storeAddress(Building $building, StoreAddressRequest $request)
    {
        WorkspaceService::storeAddress($building, $request->validated());

        return redirect()->back();
    }

    /**
     * Update a building address.
     */
    public function updateAddress(Building $building, Address $address, StoreAddressRequest $request)
    {
        abort_unless($address->building_id === $building->id, 404);

        WorkspaceService::updateAddress($building, $address, $request->validated());

        return redirect()->back();
    }

    /**
     * Delete a building address.
     */
    public function destroyAddress(Building $building, Address $address)
    {
        abort_unless($address->building_id === $building->id, 404);

        WorkspaceService::deleteAddress($address);

        return redirect()->back();
    }

    /**
     * Set a building address as default.
     */
    public function setDefaultAddress(Building $building, Address $address)
    {
        abort_unless($address->building_id === $building->id, 404);

        WorkspaceService::setDefaultAddress($building, $address);

        return redirect()->back();
    }

    /**
     * Display a listing of workspace prescriptions.
     */
    public function prescriptionsIndex(Building $building, Request $request)
    {
        return Inertia::render('workspace/prescriptions/Index', [
            'prescriptions' => WorkspaceService::getWorkspacePrescriptions($building, $request),
        ]);
    }

    /**
     * Display the workspace prescription details.
     */
    public function prescriptionsShow(Building $building, Prescription $prescription)
    {
        abort_unless($prescription->building_id === $building->id, 404);

        return Inertia::render('workspace/prescriptions/Show', WorkspaceService::getWorkspacePrescriptionShowData($building, $prescription));
    }

    /**
     * Mark the workspace prescription as sent.
     */
    public function prescriptionsSend(Building $building, Prescription $prescription)
    {
        Gate::authorize('workspaceAbility', [$building, WorkspaceAbilityEnum::PRESCRIPTIONS_SEND->value]);

        abort_unless((int) $prescription->building_id === (int) $building->id, 404);

        PrescriptionService::sendPrescription($prescription);

        return back();
    }

    /**
     * Display a listing of workspace quotes.
     */
    public function quotesIndex(Building $building, Request $request)
    {
        return Inertia::render('workspace/quotes/Index', [
            'quotes' => WorkspaceService::getWorkspaceQuotes($building, $request),
        ]);
    }

    /**
     * Display the workspace quote details.
     */
    public function quotesShow(Building $building, Quote $quote)
    {
        return Inertia::render('workspace/quotes/Show', WorkspaceService::getWorkspaceQuoteShowData($building, $quote));
    }

    /**
     * Accept the specified workspace quote.
     */
    public function quotesAccept(AcceptQuoteRequest $request, Building $building, Quote $quote)
    {
        Gate::authorize('workspaceAbility', [$building, WorkspaceAbilityEnum::QUOTES_ACCEPT->value]);

        $quote->loadMissing(['operation:id,building_id']);

        abort_unless(
            $quote->operation !== null && (int) $quote->operation->building_id === (int) $building->id,
            404
        );

        QuoteService::accept($quote);

        return back();
    }

    /**
     * Reject the specified workspace quote.
     */
    public function quotesReject(RejectQuoteRequest $request, Building $building, Quote $quote)
    {
        Gate::authorize('workspaceAbility', [$building, WorkspaceAbilityEnum::QUOTES_REJECT->value]);

        $quote->loadMissing(['operation:id,building_id']);

        abort_unless(
            $quote->operation !== null && (int) $quote->operation->building_id === (int) $building->id,
            404
        );

        QuoteService::reject($quote, $request->string('notes')->trim()->value());

        return back();
    }

    /**
     * Display a listing of workspace orders.
     */
    public function ordersIndex(Building $building, Request $request)
    {
        return Inertia::render('workspace/orders/Index', [
            'orders' => WorkspaceService::getWorkspaceOrders($building, $request),
        ]);
    }

    /**
     * Display the workspace order details.
     */
    public function ordersShow(Building $building, Order $order)
    {
        return Inertia::render('workspace/orders/Show', WorkspaceService::getWorkspaceOrderShowData($building, $order));
    }

    /**
     * Display a listing of workspace invoices.
     */
    public function invoicesIndex(Building $building, Request $request)
    {
        return Inertia::render('workspace/invoices/Index', [
            'invoices' => WorkspaceService::getWorkspaceInvoices($building, $request),
        ]);
    }

    /**
     * Display the workspace invoice details.
     */
    public function invoicesShow(Building $building, Invoice $invoice)
    {
        return Inertia::render('workspace/invoices/Show', WorkspaceService::getWorkspaceInvoiceShowData($building, $invoice));
    }

    /**
     * Display a listing of workspace operations.
     */
    public function operationsIndex(Building $building, Request $request)
    {
        return Inertia::render('workspace/operations/Index', [
            'operations' => WorkspaceService::getWorkspaceOperations($building, $request),
        ]);
    }

    /**
     * Display the workspace operation details.
     */
    public function operationsShow(Building $building, Operation $operation)
    {
        return Inertia::render('workspace/operations/Show', WorkspaceService::getWorkspaceOperationShowData($building, $operation));
    }

    /**
     * Display the create wizard page for operations in workspace.
     */
    public function operationsCreateWizard(Building $building)
    {
        $building->loadMissing([
            'addresses' => fn($query) => $query
                ->select(['id', 'building_id', 'street', 'cap', 'city', 'province', 'country', 'is_default'])
                ->orderByDesc('is_default')
                ->orderBy('id'),
        ]);

        return Inertia::render('workspace/operations/Edit', [
            'wizard' => [
                'mode' => 'create',
                'operationId' => null,
                'prescriptionId' => null,
                'initialForm' => null,
                'buildings' => [[
                    'id' => $building->id,
                    'name' => $building->name,
                    'vat' => $building->vat,
                    'legal_address' => $building->legal_address,
                    'addresses' => $building->addresses
                        ->map(fn($address): array => [
                            'id' => $address->id,
                            'street' => $address->street,
                            'cap' => $address->cap,
                            'city' => $address->city,
                            'province' => $address->province,
                            'country' => $address->country,
                            'is_default' => (bool) $address->is_default,
                        ])
                        ->values()
                        ->all(),
                ]],
            ],
        ]);
    }

    /**
     * Display the edit wizard page for one workspace operation and prescription.
     */
    public function operationsEditWizard(Building $building, Operation $operation)
    {
        abort_unless((int) $operation->building_id === (int) $building->id, 404);

        $building->loadMissing([
            'addresses' => fn($query) => $query
                ->select(['id', 'building_id', 'street', 'cap', 'city', 'province', 'country', 'is_default'])
                ->orderByDesc('is_default')
                ->orderBy('id'),
        ]);

        $wizard = OperationService::getEditWizardData($operation);

        return Inertia::render('workspace/operations/Edit', [
            'wizard' => [
                ...$wizard,
                'buildings' => [[
                    'id' => $building->id,
                    'name' => $building->name,
                    'vat' => $building->vat,
                    'legal_address' => $building->legal_address,
                    'addresses' => $building->addresses
                        ->map(fn($address): array => [
                            'id' => $address->id,
                            'street' => $address->street,
                            'cap' => $address->cap,
                            'city' => $address->city,
                            'province' => $address->province,
                            'country' => $address->country,
                            'is_default' => (bool) $address->is_default,
                        ])
                        ->values()
                        ->all(),
                ]],
            ],
        ]);
    }

    /**
     * Store operation with linked prescription (wizard flow) in workspace.
     */
    public function operationsStoreWizard(StoreOperationWithPrescriptionRequest $request, Building $building)
    {
        $validated = $request->validated();
        $validated['building_id'] = $building->id;

        $user = $request->user();
        if ($user) {
            $role = app(WorkspaceAuthorizationService::class)->roleInBuilding($user, $building);
            if ($role === BuildingUserRoleEnum::MEMBER) {
                $validated['user_id'] = $user->id;
            }
        }

        $operation = OperationService::createWithPrescription($validated);

        return to_route('workspace.operations.show', [
            'building' => $building->slug,
            'operation' => $operation->id,
        ]);
    }

    /**
     * Update operation with linked prescription (wizard flow) in workspace.
     */
    public function operationsUpdateWizard(UpdateOperationWithPrescriptionRequest $request, Building $building, Operation $operation)
    {
        abort_unless((int) $operation->building_id === (int) $building->id, 404);

        $validated = $request->validated();
        $validated['building_id'] = $building->id;

        $user = $request->user();
        if ($user) {
            $role = app(WorkspaceAuthorizationService::class)->roleInBuilding($user, $building);
            if ($role === BuildingUserRoleEnum::MEMBER) {
                $validated['user_id'] = $operation->latestPrescription()->value('user_id');
            }
        }

        OperationService::updateWithPrescription($operation, $validated);

        return to_route('workspace.operations.show', [
            'building' => $building->slug,
            'operation' => $operation->id,
        ]);
    }
}