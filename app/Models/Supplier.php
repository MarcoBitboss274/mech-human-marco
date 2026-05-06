<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'vat',
        'mail',
        'phone',
        'address',
        'cap',
        'city',
        'province',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [];
    }

    /**
     * Operations associated with this supplier.
     */
    public function operations(): BelongsToMany
    {
        return $this->belongsToMany(Operation::class)
            ->withPivot(['status', 'selected'])
            ->withTimestamps();
    }

    /**
     * Users (utenti supplier) belonging to this supplier's team.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'supplier_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Convenience: only admins of this supplier.
     */
    public function admins(): BelongsToMany
    {
        return $this->users()->wherePivot('role', \App\Enums\SupplierUserRoleEnum::ADMIN->value);
    }
}
