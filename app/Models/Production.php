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
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Sync confirmed_at/canceled_at/completed_at when status changes.
     *
     * Regole:
     * - status = confirmed → set confirmed_at (mantiene il valore originale se presente);
     *   azzera canceled_at e completed_at.
     * - status = completed → completed implica confermata: mantieni confirmed_at originale
     *   o impostalo a now() se mancante; set completed_at; azzera canceled_at.
     * - status = canceled → set canceled_at; azzera confirmed_at e completed_at.
     * - altri valori (incluso null) → azzera tutti i timestamp.
     */
    protected static function booted(): void
    {
        static::saving(function (Production $production): void {
            if (! $production->isDirty('status')) {
                return;
            }

            $confirmedValue = ProductionStatusEnum::CONFIRMED->value;
            $canceledValue = ProductionStatusEnum::CANCELED->value;
            $completedValue = ProductionStatusEnum::COMPLETED->value;
            $newStatus = $production->status;
            $originalConfirmedAt = $production->getOriginal('confirmed_at');
            $originalCompletedAt = $production->getOriginal('completed_at');

            $production->confirmed_at = null;
            $production->canceled_at = null;
            $production->completed_at = null;

            if ($newStatus === $confirmedValue) {
                $production->confirmed_at = $originalConfirmedAt ?? now();
                return;
            }

            if ($newStatus === $completedValue) {
                $production->confirmed_at = $originalConfirmedAt ?? now();
                $production->completed_at = $originalCompletedAt ?? now();
                return;
            }

            if ($newStatus === $canceledValue) {
                $production->canceled_at = now();
                return;
            }
        });
    }

    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class);
    }
}
