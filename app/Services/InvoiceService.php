<?php

namespace App\Services;

use App\Enums\InvoiceStatusEnum;
use App\Enums\OperationStatusEnum;
use App\Models\Invoice;
use App\Services\OperationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class InvoiceService extends ModelService
{
    /**
     * Get the class of the model.
     */
    protected static function getClass(): string
    {
        return Invoice::class;
    }

    /**
     * Fetch model.
     */
    public static function fetch(Request|null $request)
    {
        return static::getClass()::query()->with([
            'operation:id,typology,status',
        ]);
    }

    /**
     * Apply search.
     */
    public static function applySearch(Builder $query, Request|null $request): Builder
    {
        if ($request['query'] ?? false) {
            QueryService::querySearch($query, $request['query'], ['code', 'status', 'description']);
        }

        return $query;
    }

    /**
     * Apply sorts.
     */
    public static function applySorts(Builder $query, Request|null $request): Builder
    {
        return $query->latest();
    }

    /**
     * Send invoice.
     */
    public static function send(Invoice $invoice): void
    {
        static::sendInvoice($invoice);
    }

    /**
     * Send invoice.
     */
    public static function sendInvoice(Invoice $invoice): void
    {
        $invoice->update([
            'status' => InvoiceStatusEnum::SENT->value,
        ]);

        OperationService::updateStatus($invoice->operation, OperationStatusEnum::COMPLETED->value);
    }

    /**
     * Get payload for admin invoice show page.
     */
    public static function getAdminShowData(Invoice $invoice): array
    {
        $invoice->load([
            'operation:id,typology,status',
            'media'
        ]);

        return [
            'invoice' => $invoice->toArray(),
        ];
    }
}