<?php

namespace App\Models;

use App\Enums\ProductionStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Production extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'operation_id',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    /**
     * Sync confirmed_at/canceled_at when status changes.
     */
    protected static function booted(): void
    {
        static::saving(function (Production $production): void {
            if (! $production->isDirty('status')) {
                return;
            }

            $confirmedValue = ProductionStatusEnum::CONFIRMED->value;
            $canceledValue = ProductionStatusEnum::CANCELED->value;

            if ($production->status === $confirmedValue) {
                if ($production->getOriginal('status') !== $confirmedValue) {
                    $production->confirmed_at = now();
                }

                return;
            }

            if ($production->status === $canceledValue) {
                if ($production->getOriginal('status') !== $canceledValue) {
                    $production->canceled_at = now();
                }

                return;
            }

            $production->confirmed_at = null;
            $production->canceled_at = null;
        });
    }

    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class);
    }
}
