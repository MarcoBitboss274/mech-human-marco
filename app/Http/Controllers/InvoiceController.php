<?php

namespace App\Http\Controllers;

use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return Inertia::render('invoices/Index', [
            'invoices' => InvoiceService::search($request, true, [
                'operation' => fn($q) => $q->select('id', 'status', 'batch_number'),
            ]),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice)
    {
        return Inertia::render('invoices/Show', InvoiceService::getAdminShowData($invoice));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInvoiceRequest $request)
    {
        InvoiceService::save(null, $request->validated());

        return to_route('invoices.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreInvoiceRequest $request, Invoice $invoice)
    {
        InvoiceService::save($invoice, $request->validated());

        return to_route('invoices.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice)
    {
        InvoiceService::delete($invoice);

        return to_route('invoices.index');
    }

    /**
     * Send the specified invoice.
     */
    public function send(Invoice $invoice)
    {
        Gate::authorize('update', $invoice);

        InvoiceService::sendInvoice($invoice);

        return back();
    }
}
