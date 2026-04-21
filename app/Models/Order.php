<?php

namespace App\Models;

use App\Enums\OrderStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'operation_id',
        'code',
        'status',
        'amount',
        'description',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'confirmed_at' => 'datetime',
        ];
    }

    /**
     * Sync confirmed_at when status changes (first transition to confirmed sets timestamp; leaving confirmed clears it).
     */
    protected static function booted(): void
    {
        static::saving(function (Order $order): void {
            if (! $order->isDirty('status')) {
                return;
            }

            $confirmedValue = OrderStatusEnum::CONFIRMED->value;

            if ($order->status === $confirmedValue) {
                if ($order->getOriginal('status') !== $confirmedValue) {
                    $order->confirmed_at = now();
                }

                return;
            }

            $order->confirmed_at = null;
        });
    }

    /**
     * Operation this order belongs to.
     */
    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class);
    }
}
