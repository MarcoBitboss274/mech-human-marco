<?php

namespace App\Services;

use App\Enums\OperationStatusEnum;
use App\Enums\OrderStatusEnum;
use App\Enums\PrescriptionTypologyEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\Order;
use App\Models\Quote;
use App\Services\OperationService;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class QuoteService extends ModelService
{
    /**
     * Get the class of the model
     */
    protected static function getClass(): string
    {
        return Quote::class;
    }

    /**
     * Fetch model
     */
    public static function fetch(Request|null $request)
    {
        return static::getClass()::query()->with([
            'operation:id,typology,status',
        ]);
    }

    /**
     * Apply search
     */
    public static function applySearch(Builder $query, Request|null $request): Builder
    {
        if ($request['query'] ?? false) {
            QueryService::querySearch($query, $request['query'], ['status', 'notes']);
        }

        return $query;
    }

    /**
     * Apply sorts
     */
    public static function applySorts(Builder $query, Request|null $request): Builder
    {
        return $query->latest();
    }

    /**
     * Save quote with status side-effects.
     */
    public static function save(Model|null $model, array $data = [])
    {
        if ($model === null) {
            $data['status'] = QuoteStatusEnum::DRAFT->value;
        } else {
            unset($data['status'], $data['accepted_at']);
        }

        $data = static::normalizeStatusPayload($data);

        return parent::save($model, $data);
    }

    /**
     * Send quote.
     */
    public static function send(Quote $quote): void
    {
        $quote->fill(static::normalizeStatusPayload([
            'status' => QuoteStatusEnum::SENT->value,
        ]));
        $quote->save();

        if (OperationService::includeTypologies($quote->operation, [
            PrescriptionTypologyEnum::PROTRUSOR,
            PrescriptionTypologyEnum::PROSTHESIS,
            PrescriptionTypologyEnum::SEMI_FINISHED_PROSTHESES,
        ])) {

            if (! Order::query()->where('operation_id', $quote->operation_id)->exists()) {
                Order::create([
                    'operation_id' => $quote->operation_id,
                    'amount' => 99,
                    'code' => now()->timestamp,
                    'status' => OrderStatusEnum::CONFIRMED->value,
                    'description' => 'Ordine di produzione',
                ]);
            }
            static::accept($quote);
            // Per le tipologie ad accettazione automatica del preventivo, il preventivo
            // inviato implica anche la conferma della produzione: crea/aggiorna Production,
            // notifica il fornitore, log activity.
            OperationService::confirmProduction($quote->operation);
        } else {
            OperationService::updateStatus($quote->operation, OperationStatusEnum::WAITING_APPROVAL->value);
        }
    }

    /**
     * Accept quote from sent status.
     */
    public static function accept(Quote $quote): void
    {
        static::ensureSentStatus($quote);

        $quote->fill(static::normalizeStatusPayload([
            'status' => QuoteStatusEnum::ACCEPTED->value,
        ]));
        $quote->save();
    }

    /**
     * Reject quote from sent status.
     */
    public static function reject(Quote $quote, string $notes): void
    {
        static::ensureSentStatus($quote);

        $quote->fill(static::normalizeStatusPayload([
            'status' => QuoteStatusEnum::REJECTED->value,
            'rejected_notes' => $notes,
        ]));
        $quote->save();
    }

    /**
     * Cancel quote from sent status.
     */
    public static function cancel(Quote $quote, string $notes): void
    {
        static::ensureSentStatus($quote);

        $quote->fill(static::normalizeStatusPayload([
            'status' => QuoteStatusEnum::CANCELED->value,
            'canceled_notes' => $notes,
        ]));
        $quote->save();
    }

    /**
     * Get payload for admin quote show page
     */
    public static function getAdminShowData(Quote $quote): array
    {
        $quote->load([
            'operation:id,typology,status',
        ]);

        return [
            'quote' => $quote->toArray(),
        ];
    }

    /**
     * Apply accepted_at transition based on status.
     */
    public static function normalizeStatusPayload(array $payload): array
    {
        if (! array_key_exists('status', $payload)) {
            return $payload;
        }

        $status = $payload['status'];

        $payload['accepted_at'] = $status === QuoteStatusEnum::ACCEPTED->value ? now() : null;
        $payload['rejected_at'] = $status === QuoteStatusEnum::REJECTED->value ? now() : null;
        $payload['canceled_at'] = $status === QuoteStatusEnum::CANCELED->value ? now() : null;

        if ($status !== QuoteStatusEnum::REJECTED->value) {
            $payload['rejected_notes'] = null;
        } elseif (! array_key_exists('rejected_notes', $payload)) {
            $payload['rejected_notes'] = null;
        }

        if ($status !== QuoteStatusEnum::CANCELED->value) {
            $payload['canceled_notes'] = null;
        } elseif (! array_key_exists('canceled_notes', $payload)) {
            $payload['canceled_notes'] = null;
        }

        return $payload;
    }

    /**
     * Ensure quote can transition to terminal statuses.
     */
    private static function ensureSentStatus(Quote $quote): void
    {
        if ($quote->status !== QuoteStatusEnum::SENT->value) {
            throw ValidationException::withMessages([
                'status' => 'The quote status transition is not allowed.',
            ]);
        }
    }
}
