<?php

namespace App\Services;

use App\Enums\InvoiceStatusEnum;
use App\Enums\OrderStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\RoleEnum;
use App\Enums\WorkspaceAbilityEnum;
use App\Http\Resources\Workspace\InvoiceResource;
use App\Http\Resources\Workspace\OperationResource;
use App\Http\Resources\Workspace\OrderResource;
use App\Http\Resources\Workspace\PrescriptionResource;
use App\Http\Resources\Workspace\QuoteResource;
use App\Models\Address;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Operation;
use App\Models\Order;
use App\Models\Prescription;
use App\Models\Production;
use App\Models\Quote;
use App\Models\User;
use App\Notifications\Building\Invite;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Workspace-only orchestration service.
 *
 * @method static void sendPrescription(Building $building, Prescription $prescription)
 * @method static void acceptQuote(Building $building, Quote $quote)
 * @method static void rejectQuote(Building $building, Quote $quote, string $notes)
 */
class WorkspaceService
{
    /**
     * Get the current workspace
     */
    public static function getCurrentWorkspace()
    {
        if (! request()->route('building')) {
            return null;
        }

        $slug = (is_string(request()->route('building')))
            ? request()->route('building')
            : request()->route('building')->slug;

        return Building::where('slug', $slug)->first();
    }

    /**
     * Get paginated building members with search
     */
    public static function getBuildingMembers(Building $building, Request $request): LengthAwarePaginator
    {
        $perPage = (int) ($request->input('per_page') ?? 15);
        $query = $building->users()
            ->select('users.id', 'users.name', 'users.surname', 'users.email')
            ->withPivot(['role', 'accepted_at', 'created_at']);

        $search = $request->input('query');
        if ($search) {
            $searches = explode(' ', $search);
            $query->where(function ($q) use ($searches) {
                foreach (['name', 'surname', 'email'] as $field) {
                    $q->orWhere("users.{$field}", 'like', '%' . $searches[0] . '%');
                    if (count($searches) > 1) {
                        $q->orWhere("users.{$field}", 'like', '%' . $searches[1] . '%');
                    }
                }
            });
        }

        $paginator = $query->orderBy('building_user.created_at', 'desc')->paginate($perPage);

        $paginator->getCollection()->transform(function (User $user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'surname' => $user->surname,
                'email' => $user->email,
                'building_role' => $user->pivot->role ?? null,
                'joined_at' => $user->pivot->created_at?->toISOString(),
                'accepted_at' => $user->pivot->accepted_at?->toISOString(),
            ];
        });

        return $paginator;
    }

    /**
     * Get paginated workspace prescriptions scoped by building.
     */
    public static function getWorkspacePrescriptions(Building $building, Request $request): array
    {
        $perPage = (int) ($request->input('per_page') ?? 15);
        $user = $request->user();
        $canViewAll = (bool) ($user?->can('workspaceAbility', [$building, WorkspaceAbilityEnum::OPERATIONS_VIEW_ALL->value]) ?? false);
        $requesterUserId = $user?->id;
        $query = Prescription::query()
            ->where('building_id', $building->id)
            ->with([
                'building:id,name',
                'operation:id,status,batch_number',
                'user:id,name,surname',
            ]);

        if (! $canViewAll && $requesterUserId) {
            $query->where('user_id', $requesterUserId);
        }

        if ($request->input('query')) {
            QueryService::querySearch($query, (string) $request->input('query'), ['name', 'surname', 'ref', 'typology']);
        }

        $paginator = $query->latest()->paginate($perPage);

        return [
            'data' => PrescriptionResource::collection($paginator->items())->resolve(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    /**
     * Get payload for workspace prescription show page.
     */
    public static function getWorkspacePrescriptionShowData(Building $building, Prescription $prescription): array
    {
        abort_unless($prescription->building_id === $building->id, 404);

        $user = request()->user();
        $canViewAll = (bool) ($user?->can('workspaceAbility', [$building, WorkspaceAbilityEnum::OPERATIONS_VIEW_ALL->value]) ?? false);
        if (! $canViewAll && $user) {
            abort_unless((int) $prescription->user_id === (int) $user->id, 404);
        }

        $prescription->load([
            'operation:id,status',
            'building:id,name',
            'user:id,name,surname',
            'protrusorDetails',
            'lybraAlignerDetails',
        ]);

        return [
            'prescription' => PrescriptionResource::make($prescription)->resolve(),
        ];
    }

    /**
     * Get paginated workspace quotes scoped by building (via operation).
     */
    public static function getWorkspaceQuotes(Building $building, Request $request): array
    {
        $perPage = (int) ($request->input('per_page') ?? 15);
        $user = $request->user();
        $canViewAll = (bool) ($user?->can('workspaceAbility', [$building, WorkspaceAbilityEnum::OPERATIONS_VIEW_ALL->value]) ?? false);
        $requesterUserId = $user?->id;
        $query = Quote::query()
            ->whereNotIn('status', [QuoteStatusEnum::DRAFT->value])
            ->whereHas('operation', fn($q) => $q->where('building_id', $building->id))
            ->with([
                'operation:id,typology,status,batch_number',
            ]);

        if (! $canViewAll && $requesterUserId) {
            $query->whereHas('operation.latestPrescription', fn($q) => $q->where('user_id', $requesterUserId));
        }

        if ($request->input('query')) {
            QueryService::querySearch($query, (string) $request->input('query'), ['status', 'notes']);
        }

        $paginator = $query->latest()->paginate($perPage);

        return [
            'data' => QuoteResource::collection($paginator->items())->resolve(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    /**
     * Get payload for workspace quote show page.
     */
    public static function getWorkspaceQuoteShowData(Building $building, Quote $quote): array
    {
        $quote->load([
            'operation:id,typology,status,building_id',
        ]);

        abort_unless(
            $quote->operation !== null && (int) $quote->operation->building_id === (int) $building->id,
            404
        );

        $user = request()->user();
        $canViewAll = (bool) ($user?->can('workspaceAbility', [$building, WorkspaceAbilityEnum::OPERATIONS_VIEW_ALL->value]) ?? false);
        if (! $canViewAll && $user) {
            $isRequester = Operation::query()
                ->whereKey($quote->operation_id)
                ->whereHas('latestPrescription', fn($q) => $q->where('user_id', $user->id))
                ->exists();

            abort_unless($isRequester, 404);
        }

        return [
            'quote' => QuoteResource::make($quote)->resolve(),
        ];
    }

    /**
     * Get paginated workspace orders scoped by building (via operation).
     */
    public static function getWorkspaceOrders(Building $building, Request $request): array
    {
        $perPage = (int) ($request->input('per_page') ?? 15);
        $user = $request->user();
        $canViewAll = (bool) ($user?->can('workspaceAbility', [$building, WorkspaceAbilityEnum::OPERATIONS_VIEW_ALL->value]) ?? false);
        $requesterUserId = $user?->id;
        $query = Order::query()
            ->whereIn('status', [OrderStatusEnum::CONFIRMED->value])
            ->whereHas('operation', fn($q) => $q->where('building_id', $building->id))
            ->with([
                'operation:id,typology,status,batch_number',
            ]);

        if (! $canViewAll && $requesterUserId) {
            $query->whereHas('operation.latestPrescription', fn($q) => $q->where('user_id', $requesterUserId));
        }

        if ($request->input('query')) {
            QueryService::querySearch($query, (string) $request->input('query'), ['code', 'status', 'description']);
        }

        $paginator = $query->latest()->paginate($perPage);

        return [
            'data' => OrderResource::collection($paginator->items())->resolve(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    /**
     * Get payload for workspace order show page.
     */
    public static function getWorkspaceOrderShowData(Building $building, Order $order): array
    {
        $order->load([
            'operation:id,typology,status,building_id',
        ]);

        abort_unless(
            $order->operation !== null && (int) $order->operation->building_id === (int) $building->id,
            404
        );

        $user = request()->user();
        $canViewAll = (bool) ($user?->can('workspaceAbility', [$building, WorkspaceAbilityEnum::OPERATIONS_VIEW_ALL->value]) ?? false);
        if (! $canViewAll && $user) {
            $isRequester = Operation::query()
                ->whereKey($order->operation_id)
                ->whereHas('latestPrescription', fn($q) => $q->where('user_id', $user->id))
                ->exists();

            abort_unless($isRequester, 404);
        }

        return [
            'order' => OrderResource::make($order)->resolve(),
        ];
    }

    /**
     * Get paginated workspace invoices scoped by building (via operation).
     */
    public static function getWorkspaceInvoices(Building $building, Request $request): array
    {
        $perPage = (int) ($request->input('per_page') ?? 15);
        $user = $request->user();
        $canViewAll = (bool) ($user?->can('workspaceAbility', [$building, WorkspaceAbilityEnum::OPERATIONS_VIEW_ALL->value]) ?? false);
        $requesterUserId = $user?->id;
        $query = Invoice::query()
            ->whereIn('status', [InvoiceStatusEnum::SENT->value])
            ->whereHas('operation', fn($q) => $q->where('building_id', $building->id))
            ->with([
                'operation:id,typology,status,batch_number',
            ]);

        if (! $canViewAll && $requesterUserId) {
            $query->whereHas('operation.latestPrescription', fn($q) => $q->where('user_id', $requesterUserId));
        }

        if ($request->input('query')) {
            QueryService::querySearch($query, (string) $request->input('query'), ['code', 'status', 'description']);
        }

        $paginator = $query->latest()->paginate($perPage);

        return [
            'data' => InvoiceResource::collection($paginator->items())->resolve(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    /**
     * Get payload for workspace invoice show page.
     */
    public static function getWorkspaceInvoiceShowData(Building $building, Invoice $invoice): array
    {
        $invoice->load([
            'operation:id,typology,status,building_id',
        ]);

        abort_unless(
            $invoice->operation !== null && (int) $invoice->operation->building_id === (int) $building->id,
            404
        );

        $user = request()->user();
        $canViewAll = (bool) ($user?->can('workspaceAbility', [$building, WorkspaceAbilityEnum::OPERATIONS_VIEW_ALL->value]) ?? false);
        if (! $canViewAll && $user) {
            $isRequester = Operation::query()
                ->whereKey($invoice->operation_id)
                ->whereHas('latestPrescription', fn($q) => $q->where('user_id', $user->id))
                ->exists();

            abort_unless($isRequester, 404);
        }

        return [
            'invoice' => InvoiceResource::make($invoice)->resolve(),
        ];
    }

    /**
     * Get paginated workspace operations scoped by building.
     */
    public static function getWorkspaceOperations(Building $building, Request $request): array
    {
        $perPage = (int) ($request->input('per_page') ?? 15);
        $user = $request->user();
        $canViewAll = (bool) ($user?->can('workspaceAbility', [$building, WorkspaceAbilityEnum::OPERATIONS_VIEW_ALL->value]) ?? false);
        $requesterUserId = $user?->id;
        $query = OperationService::fetch($request);
        $query->select([
            'operations.id',
            'operations.building_id',
            'operations.typology',
            'operations.status',
            'operations.batch_number',
            'operations.created_at',
        ]);
        $query->addSelect([
            'latest_quote_status' => Quote::query()
                ->select('status')
                ->whereColumn('quotes.operation_id', 'operations.id')
                ->latest('created_at')
                ->latest('id')
                ->limit(1),
        ]);
        $query->where('operations.building_id', $building->id);
        $query->with([
            'latestPrescription' => fn($q) => $q
                ->select(['id', 'operation_id', 'user_id', 'typology', 'ref', 'created_at', 'expire_at', 'send_at'])
                ->with(['user:id,name,surname']),
        ]);

        if (! $canViewAll && $requesterUserId) {
            $query->whereHas('latestPrescription', fn($q) => $q->where('user_id', $requesterUserId));
        }

        OperationService::applySearch($query, $request);
        OperationService::applyFilters($query, $request);

        $paginator = $query->latest()->paginate($perPage);

        return [
            'data' => OperationResource::collection($paginator->items())->resolve(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    /**
     * Get payload for workspace operation show page.
     */
    public static function getWorkspaceOperationShowData(Building $building, Operation $operation): array
    {
        abort_unless((int) $operation->building_id === (int) $building->id, 404);

        $user = request()->user();
        $canViewAll = (bool) ($user?->can('workspaceAbility', [$building, WorkspaceAbilityEnum::OPERATIONS_VIEW_ALL->value]) ?? false);
        if (! $canViewAll && $user) {
            $isRequester = $operation->latestPrescription()
                ->where('user_id', $user->id)
                ->exists();

            abort_unless($isRequester, 404);
        }

        $operation->load([
            'building:id,name',
            'prescriptions' => fn($q) => $q->latest()->with(['user:id,name,surname,email', 'building:id,name']),
            'quotes' => fn($q) => $q->latest()->whereNotIn('status', [QuoteStatusEnum::DRAFT->value]),
            'orders' => fn($q) => $q->latest()->whereIn('status', [OrderStatusEnum::CONFIRMED->value]),
            'productions' => fn($q) => $q->oldest(),
            'invoices' => fn($q) => $q->latest()->whereIn('status', [
                InvoiceStatusEnum::SENT->value,
                InvoiceStatusEnum::CANCELED->value,
            ]),
            'latestPrescription',
        ]);

        return [
            'operation' => OperationResource::make($operation)->resolve(),
            'overview' => OperationService::buildOverviewPayload($operation, asCustomer: true),
        ];
    }

    /**
     * Mark a workspace prescription as sent.
     */
    public static function sendPrescription(Building $building, Prescription $prescription): void
    {
        abort_unless((int) $prescription->building_id === (int) $building->id, 404);

        PrescriptionService::sendPrescription($prescription);
    }

    /**
     * Accept a workspace quote.
     */
    public static function acceptQuote(Building $building, Quote $quote): void
    {
        $quote->loadMissing(['operation:id,building_id']);

        abort_unless(
            $quote->operation !== null && (int) $quote->operation->building_id === (int) $building->id,
            404
        );

        QuoteService::accept($quote);
    }

    /**
     * Reject a workspace quote.
     */
    public static function rejectQuote(Building $building, Quote $quote, string $notes): void
    {
        $quote->loadMissing(['operation:id,building_id']);

        abort_unless(
            $quote->operation !== null && (int) $quote->operation->building_id === (int) $building->id,
            404
        );

        QuoteService::reject($quote, $notes);
    }

    /**
     * Get payload for workspace building show page.
     */
    public static function getWorkspaceBuildingShowData(Building $building): array
    {
        $building->loadCount('users');
        $building->load([
            'users' => fn($q) => $q->select('users.id', 'users.name', 'users.surname', 'users.email', 'users.created_at')->withPivot(['role', 'accepted_at']),
            'addresses',
        ]);

        $users = $building->users->map(fn(User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'surname' => $user->surname,
            'email' => $user->email,
            'building_role' => $user->pivot->role ?? null,
            'created_at' => $user->created_at?->toISOString(),
            'accepted_at' => $user->pivot->accepted_at?->toISOString(),
        ])->values()->all();

        $addresses = $building->addresses->map(fn(Address $address) => [
            'id' => $address->id,
            'building_id' => $address->building_id,
            'street' => $address->street,
            'cap' => $address->cap,
            'city' => $address->city,
            'province' => $address->province,
            'country' => $address->country,
            'is_default' => $address->is_default,
        ])->values()->all();

        return [
            'building' => [
                'id' => $building->id,
                'name' => $building->name,
                'vat' => $building->vat,
                'is_studio' => $building->is_studio,
                'customer_code' => $building->customer_code,
                'is_laboratory' => $building->is_laboratory,
                'headquarter_address' => $building->headquarter_address,
                'legal_address' => $building->legal_address,
                'approved' => $building->approved,
                'fiscal_code' => $building->fiscal_code,
                'sdi_code' => $building->sdi_code,
                'users_count' => $building->users_count,
                'created_at' => $building->created_at?->toISOString(),
                'updated_at' => $building->updated_at?->toISOString(),
            ],
            'users' => $users,
            'addresses' => $addresses,
        ];
    }

    /**
     * Update building data from workspace.
     */
    public static function updateBuilding(Building $building, array $data): Building
    {
        $building->fill([
            'name' => $data['name'],
            'vat' => $data['vat'],
            'is_studio' => $data['is_studio'] ?? null,
            'customer_code' => $data['customer_code'] ?? null,
            'is_laboratory' => $data['is_laboratory'] ?? null,
            'headquarter_address' => $data['headquarter_address'],
            'legal_address' => $data['legal_address'],
            'fiscal_code' => $data['fiscal_code'] ?? null,
            'sdi_code' => $data['sdi_code'] ?? null,
        ]);

        $building
            ->generateSlug()
            ->save();

        return $building;
    }

    /**
     * Remove a member from the building
     */
    public static function removeBuildingMember(Building $building, User $user): bool
    {
        $building->users()->detach($user->id);

        return true;
    }

    /**
     * Update a building member role.
     */
    public static function updateBuildingMemberRole(Building $building, User $user, array $data): bool
    {
        $building->users()->updateExistingPivot($user->id, [
            'role' => $data['role'],
        ]);

        return true;
    }

    /**
     * Store a new address for the building.
     */
    public static function storeAddress(Building $building, array $data): Address
    {
        $address = new Address;
        $address->building_id = $building->id;
        $address->street = $data['street'];
        $address->cap = $data['cap'];
        $address->city = $data['city'];
        $address->province = $data['province'];
        $address->country = $data['country'];
        $address->is_default = $data['is_default'];

        if ($address->is_default) {
            $building->addresses()->update(['is_default' => false]);
        }

        $address->save();

        return $address;
    }

    /**
     * Update an existing address for the building.
     */
    public static function updateAddress(Building $building, Address $address, array $data): Address
    {
        if ($data['is_default']) {
            $building->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->fill([
            'street' => $data['street'],
            'cap' => $data['cap'],
            'city' => $data['city'],
            'province' => $data['province'],
            'country' => $data['country'],
            'is_default' => $data['is_default'],
        ]);
        $address->save();

        return $address;
    }

    /**
     * Delete a building address.
     */
    public static function deleteAddress(Address $address): void
    {
        $address->delete();
    }

    /**
     * Set the address as default for the building.
     */
    public static function setDefaultAddress(Building $building, Address $address): void
    {
        $building->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);
    }

    /**
     * Invite a member to the building by email
     *
     * @throws ValidationException
     */
    public static function inviteBuildingMember(Building $building, array $data): User
    {
        $email = $data['email'];
        $role = $data['role'];
        $inviteToken = Str::uuid()->toString();

        $user = User::where('email', $email)->first();

        if ($user && $building->users()->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['Questo utente è già membro della struttura.'],
            ]);
        }

        if ($user) {
            $building->users()->attach($user->id, [
                'role' => $role,
                'is_new' => false,
                'invite_token' => $inviteToken,
            ]);
        } else {
            $newUser = UserService::store(null, [
                'name' => '',
                'surname' => '',
                'email' => $email,
                'role' => RoleEnum::CUSTOMER->value,
            ]);
            $building->users()->attach($newUser->id, [
                'role' => $role,
                'is_new' => true,
                'invite_token' => $inviteToken,
            ]);
            $user = $newUser;
        }

        $url = route('invitation.index', ['token' => $inviteToken]);
        $user->notify(new Invite($building, $url));

        return $user;
    }
}