<?php

namespace App\Models;

use App\Enums\CaseStatusEnum;
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
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Operation extends Model implements HasMedia
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use SoftDeletes;
    use LogsActivity;
    use InteractsWithMedia;

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

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('supplier_documents');
    }

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
        'can_be_deleted',
    ];

    /**
     * Stato lavorazione lato Fornitore — derivato dal pivot `operation_supplier`.
     *
     * Sorgente: `operation_supplier.supplier_completed_at` per il fornitore corrente
     * (null = Aperta, timestamp = Completata). L'attributo viene esposto via
     * `Operation::scopeWithSupplierPivot($supplierId)` (addSelect dal pivot) per
     * evitare N+1.
     */
    public function getCaseStatusAttribute(): string
    {
        return $this->getRawSupplierCompletedAt() !== null
            ? CaseStatusEnum::COMPLETED->value
            : CaseStatusEnum::OPEN->value;
    }

    private function getRawSupplierCompletedAt(): mixed
    {
        if (array_key_exists('supplier_completed_at', $this->attributes)) {
            return $this->attributes['supplier_completed_at'];
        }

        return null;
    }

    /**
     * Eager-load `supplier_completed_at` from `operation_supplier` pivot for the given supplier.
     * Use it in workspace supplier queries so the visible-status accessor can read the timestamp
     * without an N+1.
     */
    public function scopeWithSupplierPivot(Builder $query, int $supplierId): Builder
    {
        return $query->addSelect([
            'supplier_completed_at' => \DB::table('operation_supplier')
                ->select('supplier_completed_at')
                ->whereColumn('operation_supplier.operation_id', 'operations.id')
                ->where('operation_supplier.supplier_id', $supplierId)
                ->where('operation_supplier.selected', true)
                ->limit(1),
        ]);
    }

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
            ->withPivot(['status', 'selected', 'selected_at', 'supplier_completed_at'])
            ->withTimestamps();
    }

    /**
     * Selected supplier associated with this operation.
     */
    public function selectedSupplier(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class)
            ->withPivot(['status', 'selected', 'selected_at'])
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
     * Supplier chat messages (M&H ↔ Fornitore) associated with this operation.
     */
    public function supplierChatMessages(): HasMany
    {
        return $this->hasMany(OperationSupplierChatMessage::class);
    }

    /**
     * Supplier chat read markers associated with this operation.
     */
    public function supplierChatReads(): HasMany
    {
        return $this->hasMany(OperationSupplierChatRead::class);
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