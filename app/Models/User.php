<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\RoleEnum;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Session;

class User extends Authenticatable implements HasMedia, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;
    use HasRoles;
    use SoftDeletes;
    use InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'surname',
        'email',
        'password',
        'role',
        'active',
        'last_login_at',
        'invited_at',
        'odontoiatra',
        'odontotecnico',
        'roll_number',
        'roll_province',
        'email_verified_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'roles',
        'permissions',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'full_name',
        'impersonable',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'last_login_at' => 'datetime',
            'invited_at' => 'datetime',
            'odontoiatra' => 'boolean',
            'odontotecnico' => 'boolean',
        ];
    }

    /**
     * Get the full name of the user.
     * 
     */
    public function getFullNameAttribute(): string
    {
        return implode(' ', [
            $this->name,
            $this->surname,
        ]);
    }

    /**
     * Get the impersonable attribute
     * 
     */
    public function getImpersonableAttribute(): bool
    {
        return $this->canBeImpersonated();
    }

    /**
     * Assign role to user
     *
     */
    public function assignRoleToUser(string $role)
    {
        $role = Role::query()->where('name', $role)->first();
        if ($role) {
            $this->syncRoles($role);
            $this->update([
                'role' => $role->name,
            ]);
        }
    }

    /**
     * Check if the user has one of the roles
     *
     * @param array $roles
     * @return bool
     */
    public function isRole(array $roles)
    {
        return in_array($this->role, $roles);
    }

    /**
     * Make user superadmin
     *
     */
    public function makeSuperadmin()
    {
        $this->assignRoleToUser(RoleEnum::SUPERADMIN->value);
    }

    /**
     * Make user admin
     *
     */
    public function makeAdmin()
    {
        $this->assignRoleToUser(RoleEnum::ADMIN->value);
    }

    /**
     * Make user agent
     *
     */
    public function makeAgent()
    {
        $this->assignRoleToUser(RoleEnum::AGENT->value);
    }

    /**
     * Make user customer
     *
     */
    public function makeCustomer()
    {
        $this->assignRoleToUser(RoleEnum::CUSTOMER->value);
    }

    /**
     * Make user supplier
     *
     */
    public function makeSupplier()
    {
        $this->assignRoleToUser(RoleEnum::SUPPLIER->value);
    }

    /**
     * Check if the user is superadmin
     *
     * @return bool
     */
    public function isSuperadmin()
    {
        return in_array($this->role, [
            RoleEnum::SUPERADMIN->value,
        ]);
    }

    /**
     * Check if the user is admin
     *
     * @return bool
     */
    public function isAdmin()
    {
        return in_array($this->role, [
            RoleEnum::ADMIN->value,
            RoleEnum::SUPERADMIN->value,
        ]);
    }

    /**
     * Check if the user is agent
     *
     * @return bool
     */
    public function isAgent()
    {
        return in_array($this->role, [
            RoleEnum::AGENT->value,
        ]);
    }

    /**
     * Check if the user is customer
     *
     * @return bool
     */
    public function isCustomer()
    {
        return in_array($this->role, [
            RoleEnum::CUSTOMER->value,
        ]);
    }

    /**
     * Check if the user is supplier
     *
     * @return bool
     */
    public function isSupplier()
    {
        return in_array($this->role, [
            RoleEnum::SUPPLIER->value,
        ]);
    }

    /**
     * Verify email
     *
     */
    public function verifyEmail(bool $verified = true)
    {
        if ($verified && $this->hasVerifiedEmail()) {
            return;
        }

        $this->update([
            'email_verified_at' => $verified ? now() : null,
        ]);
    }

    /**
     * Determine if the user has verified their email address.
     *
     * @return bool
     */
    public function hasVerifiedEmail()
    {
        return ! is_null($this->email_verified_at);
    }

    /**
     * Check if the user can impersonate
     *
     * @return bool
     */
    public function canImpersonate()
    {
        return $this->can('users.impersonate');
    }

    /**
     * Check if the user can be impersonated
     *
     * @return bool
     */
    public function canBeImpersonated()
    {
        return $this->isRole([
            RoleEnum::AGENT->value,
            RoleEnum::CUSTOMER->value,
            RoleEnum::SUPPLIER->value,
        ]);
    }

    /**
     * Set the last login at
     *
     */
    public function setLastLoginAt()
    {
        $this->update([
            'last_login_at' => now(),
        ]);
    }

    /**
     * Buildings the user is associated with.
     */
    public function buildings(): BelongsToMany
    {
        return $this->belongsToMany(Building::class)
            ->using(BuildingUser::class)
            ->withPivot(['role', 'accepted_at', 'invite_token', 'is_new'])
            ->withTimestamps();
    }

    /**
     * Buildings managed by the agent.
     */
    public function managedBuildings(): HasMany
    {
        return $this->hasMany(Building::class, 'agent_id');
    }

    /**
     * Prescriptions associated with the user.
     */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    /**
     * Operation chat messages authored by the user.
     */
    public function operationChatMessages(): HasMany
    {
        return $this->hasMany(OperationChatMessage::class);
    }

    /**
     * Operation chat read markers owned by the user.
     */
    public function operationChatReads(): HasMany
    {
        return $this->hasMany(OperationChatRead::class);
    }

    /**
     * Register media collections
     *
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile();
    }

    /**
     * Get avatar id
     *
     */
    public function getAvatarId()
    {
        return $this->getFirstMedia('avatar')?->id;
    }

    /**
     * Check if the user can access the building
     *
     * @param Building $building
     * @return bool
     */
    public function canAccessBuilding(Building $building)
    {
        return $this->buildings()->where('building_id', $building->id)->exists();
    }

    /**
     * Check if the user can access the operation
     *
     * @param Operation $operation
     * @return bool
     */
    public function canAccessOperation(Operation $operation)
    {
        return $this->buildings()->where('building_id', $operation->building_id)->exists();
    }

    /**
     * Setup session data
     *
     */
    public function setupSessionData()
    {
        $meta = Session::get('user_meta', null);

        if (is_null($meta) || empty($meta) || count($meta) === 0) {
            $meta = match ($this->role) {
                RoleEnum::CUSTOMER->value => [
                    'customer_buildings_count' => $this->buildings()->count(),
                ],
                default => [],
            };
        }

        Session::put('user_meta', $meta);
    }
}