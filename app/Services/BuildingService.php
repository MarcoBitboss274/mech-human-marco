<?php

namespace App\Services;

use App\Enums\BuildingUserRoleEnum;
use App\Models\Building;
use App\Models\User;
use App\Notifications\Admin\NewBuildingForAdmin;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class BuildingService extends ModelService
{
    /**
     * Get the class of the model
     */
    protected static function getClass(): string
    {
        return Building::class;
    }

    /**
     * Fetch model
     */
    public static function fetch(?Request $request)
    {
        return static::getClass()::query()
            ->withCount('users')
            ->with([
                'agent' => fn($q) => $q->select('users.id', 'users.name', 'users.surname'),
            ]);
    }

    /**
     * Apply search
     */
    public static function applySearch(Builder $query, ?Request $request): Builder
    {
        if ($request['query'] ?? false) {
            QueryService::querySearch($query, $request['query'], ['name', 'vat']);
        }

        return $query;
    }

    /**
     * Apply filters
     */
    public static function applyFilters(Builder $query, ?Request $request): Builder
    {
        $agentId = $request?->input('agent_id');
        if ($agentId !== null && $agentId !== '') {
            $id = (int) $agentId;
            if ($id > 0) {
                $query->where('agent_id', $id);
            }
        }

        $typology = $request?->input('typology');
        if ($typology !== null && $typology !== '') {
            $tokens = is_array($typology)
                ? $typology
                : array_filter(explode(',', (string) $typology));
            $hasStudio = in_array('studio', $tokens, true);
            $hasLaboratory = in_array('laboratory', $tokens, true);
            if ($hasStudio || $hasLaboratory) {
                $query->where(function (Builder $q) use ($hasStudio, $hasLaboratory) {
                    if ($hasStudio && $hasLaboratory) {
                        $q->where('is_studio', true)->orWhere('is_laboratory', true);
                    } elseif ($hasStudio) {
                        $q->where('is_studio', true);
                    } else {
                        $q->where('is_laboratory', true);
                    }
                });
            }
        }

        $approved = $request?->input('approved');
        if ($approved !== null && $approved !== '') {
            $query->where('approved', $approved === '1' || $approved === 1 || $approved === true);
        }

        return $query;
    }

    /**
     * Apply sorts
     */
    public static function applySorts(Builder $query, ?Request $request): Builder
    {
        return $query->latest();
    }

    /**
     * Store model
     */
    public static function store(?Model $model, array $data = []): Model
    {
        $model = $model ?? static::newInstance();
        $model->fill(
            Arr::only($data, $model->getFillable())
        );

        $model
            ->generateSlug()
            ->save();

        return $model;
    }

    /**
     * Get payload for admin building show page (building + users with pivot role + addresses)
     */
    public static function getAdminShowData(Building $building): array
    {
        $building->loadCount('users');
        $building->load([
            'users' => fn($q) => $q->select('users.id', 'users.name', 'users.surname', 'users.email', 'users.created_at')->withPivot(['role', 'accepted_at']),
            'addresses',
            'agent' => fn($q) => $q->select('users.id', 'users.name', 'users.surname'),
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

        $addresses = $building->addresses->map(fn($addr) => [
            'id' => $addr->id,
            'building_id' => $addr->building_id,
            'street' => $addr->street,
            'cap' => $addr->cap,
            'city' => $addr->city,
            'province' => $addr->province,
            'country' => $addr->country,
            'is_default' => $addr->is_default,
        ])->values()->all();

        return [
            'building' => [
                'id' => $building->id,
                'agent_id' => $building->agent_id,
                'agent_full_name' => $building->agent_full_name,
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
     * Create a building for a workspace user (approved=false, attached via pivot with admin role)
     */
    public static function createForWorkspaceUser(User $user, array $data): Building
    {
        $data['approved'] = false;

        $building = static::store(null, $data);
        $user->buildings()->attach($building->id, [
            'role' => BuildingUserRoleEnum::ADMIN->value,
        ]);

        $user->setupSessionData();

        NotificationService::sendToAdmins(new NewBuildingForAdmin($building));

        return $building;
    }
}
