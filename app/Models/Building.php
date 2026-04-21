<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Building extends Model
{
    use SoftDeletes;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'agent_full_name',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'agent_id',
        'name',
        'vat',
        'is_studio',
        'customer_code',
        'is_laboratory',
        'headquarter_address',
        'legal_address',
        'approved',
        'fiscal_code',
        'sdi_code',
        'slug',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_studio' => 'boolean',
            'is_laboratory' => 'boolean',
            'approved' => 'boolean',
        ];
    }

    /**
     * Addresses associated with the building.
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Users associated with the building.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(BuildingUser::class)
            ->withPivot(['role', 'accepted_at', 'invite_token', 'is_new'])
            ->withTimestamps();
    }

    /**
     * Agent responsible for this building.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * Get the agent full name.
     */
    public function getAgentFullNameAttribute(): ?string
    {
        $fullName = $this->agent?->full_name;

        return ! empty($fullName) ? $fullName : null;
    }

    /**
     * Prescriptions associated with the building.
     */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    /**
     * Generate slug
     * 
     * @return $this
     */
    public function generateSlug()
    {
        $this->slug = Str::slug($this->name ?? '');

        return $this;
    }
}
