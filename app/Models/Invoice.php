<?php

namespace App\Models;

use App\Enums\InvoiceStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Invoice extends Model implements HasMedia
{
    use SoftDeletes;
    use InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'operation_id',
        'status',
        'code',
        'amount',
        'description',
    ];

    /**
     * The relations to eager load on every query.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'invoiceFile',
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
            'sent_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    /**
     * Sync sent_at and canceled_at when status changes.
     *
     * sent_at is preserved through paid and canceled transitions (the customer must
     * still see fatture annullate-after-invio). draft is the only state that resets both.
     */
    protected static function booted(): void
    {
        static::saving(function (Invoice $invoice): void {
            if (! $invoice->isDirty('status')) {
                return;
            }

            $sentValue = InvoiceStatusEnum::SENT->value;
            $canceledValue = InvoiceStatusEnum::CANCELED->value;
            $draftValue = InvoiceStatusEnum::DRAFT->value;

            if ($invoice->status === $sentValue) {
                if ($invoice->sent_at === null) {
                    $invoice->sent_at = now();
                }
                if ($invoice->getOriginal('status') === $canceledValue) {
                    $invoice->canceled_at = null;
                }

                return;
            }

            if ($invoice->status === $canceledValue) {
                if ($invoice->getOriginal('status') !== $canceledValue) {
                    $invoice->canceled_at = now();
                }

                return;
            }

            if ($invoice->status === $draftValue) {
                $invoice->sent_at = null;
                $invoice->canceled_at = null;

                return;
            }

            $invoice->canceled_at = null;
        });
    }

    /**
     * Operation this invoice belongs to.
     */
    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class);
    }

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('invoice_file')
            ->singleFile();
    }

    public function getInvoiceFileAttribute()
    {
        return $this->getFirstMedia('invoice_file');
    }
}