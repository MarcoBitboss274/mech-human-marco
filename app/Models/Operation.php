<?php

namespace App\Models;

use App\Enums\OperationStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Operation extends Model
{
    use SoftDeletes;
    use LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'building_id',
        'typology',
        'status',
        'batch_number',
        'canceled_at',
        'archived_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'canceled_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'can_be_canceled',
        'can_be_archived',
        'can_be_reopened',
        'can_be_reactivated',
        'can_be_deleted'
    ];

    public function getCanBeCanceledAttribute(): bool
    {
        return $this->canBeCanceled();
    }

    public function getCanBeArchivedAttribute(): bool
    {
        return $this->canBeArchived();
    }

    public function getCanBeReopenedAttribute(): bool
    {
        return $this->canBeReopened();
    }

    public function getCanBeReactivatedAttribute(): bool
    {
        return $this->canBeReactivated();
    }

    public function getCanBeDeletedAttribute(): bool
    {
        return $this->canBeDeleted();
    }

    public function canBeDeleted(): bool
    {
        return in_array($this->status, [
            OperationStatusEnum::DRAFT->value
        ]);
    }

    public function canBeCanceled(): bool
    {
        return is_null($this->canceled_at) && !in_array($this->status, [
            OperationStatusEnum::DRAFT->value,
            OperationStatusEnum::COMPLETED->value,
        ]);
    }

    public function canBeArchived(): bool
    {
        return is_null($this->archived_at) && $this->status !== OperationStatusEnum::DRAFT->value;
    }

    public function canBeReopened(): bool
    {
        return ! is_null($this->archived_at);
    }

    public function canBeReactivated(): bool
    {
        return ! is_null($this->canceled_at);
    }

    public function scopeCanceled(Builder $query): Builder
    {
        return $query->whereNotNull('canceled_at');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    /**
     * Building this operation belongs to.
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * Prescriptions associated with this operation.
     */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    /**
     * Latest prescription associated with this operation.
     */
    public function latestPrescription(): HasOne
    {
        return $this->hasOne(Prescription::class)->latest();
    }

    /**
     * Quotes associated with this operation.
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    /**
     * Orders associated with this operation.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Productions associated with this operation.
     */
    public function productions(): HasMany
    {
        return $this->hasMany(Production::class);
    }

    /**
     * Invoices associated with this operation.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Suppliers associated with this operation.
     */
    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class)
            ->withPivot(['status', 'selected'])
            ->withTimestamps();
    }

    /**
     * Selected supplier associated with this operation.
     */
    public function selectedSupplier(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class)
            ->withPivot(['status', 'selected'])
            ->wherePivot('selected', true)
            ->withTimestamps();
    }

    /**
     * Chat messages associated with this operation.
     */
    public function chatMessages(): HasMany
    {
        return $this->hasMany(OperationChatMessage::class);
    }

    /**
     * Chat read markers associated with this operation.
     */
    public function chatReads(): HasMany
    {
        return $this->hasMany(OperationChatRead::class);
    }

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}