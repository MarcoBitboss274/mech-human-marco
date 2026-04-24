<?php

namespace App\Models;

use App\Enums\PrescriptionStatusEnum;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class Prescription extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'operation_id',
        'building_id',
        'user_id',
        'status',
        'typology',
        'ref',
        'manual',
        'name',
        'surname',
        'age',
        'gender',
        'send_at',
        'expire_at',
        'confirmed_at',
        'company_name',
        'address',
        'city',
        'province',
        'cap',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'age' => 'integer',
            'manual' => 'boolean',
            'send_at' => 'datetime',
            'expire_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    /**
     * Sync confirmed_at when status transitions in/out of confirmed.
     */
    protected static function booted(): void
    {
        static::saving(function (Prescription $prescription): void {
            if (! $prescription->isDirty('status')) {
                return;
            }

            $confirmedValue = PrescriptionStatusEnum::CONFIRMED->value;

            if ($prescription->status === $confirmedValue) {
                if ($prescription->getOriginal('status') !== $confirmedValue) {
                    $prescription->confirmed_at = now();
                }

                return;
            }

            $prescription->confirmed_at = null;
        });
    }

    /**
     * Name attribute.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value): mixed {
                if ($value === null) {
                    return null;
                }

                try {
                    return Crypt::decryptString((string) $value);
                } catch (DecryptException) {
                    return $value;
                }
            },
            set: fn(mixed $value): ?string => $value === null
                ? null
                : Crypt::encryptString((string) $value),
        );
    }

    /**
     * Surname attribute.
     */
    protected function surname(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value): mixed {
                if ($value === null) {
                    return null;
                }

                try {
                    return Crypt::decryptString((string) $value);
                } catch (DecryptException) {
                    return $value;
                }
            },
            set: fn(mixed $value): ?string => $value === null
                ? null
                : Crypt::encryptString((string) $value),
        );
    }

    /**
     * Operation this prescription belongs to.
     */
    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class);
    }

    /**
     * Building this prescription belongs to.
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * User this prescription belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Protrusor details associated with this prescription.
     */
    public function protrusorDetails(): HasOne
    {
        return $this->hasOne(PrescriptionProtrusor::class);
    }

    /**
     * Lybra aligner details associated with this prescription.
     */
    public function lybraAlignerDetails(): HasOne
    {
        return $this->hasOne(PrescriptionLybraAligner::class);
    }

    /**
     * Guided surgery details associated with this prescription.
     */
    public function guidedSurgeryDetails(): HasOne
    {
        return $this->hasOne(PrescriptionGuidedSurgery::class);
    }

    /**
     * 3D mesh details associated with this prescription.
     */
    public function threeDMeshDetails(): HasOne
    {
        return $this->hasOne(PrescriptionThreeDMesh::class);
    }

    /**
     * Prosthesis details associated with this prescription.
     */
    public function prosthesisDetails(): HasOne
    {
        return $this->hasOne(PrescriptionProsthesis::class);
    }

    /**
     * Semi-finished prosthesis details associated with this prescription.
     */
    public function semiFinishedProsthesisDetails(): HasOne
    {
        return $this->hasOne(PrescriptionSemiFinishedProsthesis::class);
    }
}