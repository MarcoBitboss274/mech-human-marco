<?php

namespace App\Http\Controllers;

use App\Http\Requests\Quote\StoreQuoteRequest;
use App\Http\Requests\Quote\AcceptQuoteRequest;
use App\Http\Requests\Quote\CancelQuoteRequest;
use App\Http\Requests\Quote\RejectQuoteRequest;
use App\Models\Quote;
use App\Services\QuoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class QuoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return Inertia::render('quotes/Index', [
            'quotes' => QuoteService::search($request, true, [
                'operation' => fn($q) => $q->select('id', 'status', 'batch_number'),
            ]),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Quote $quote)
    {
        return Inertia::render('quotes/Show', QuoteService::getAdminShowData($quote));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreQuoteRequest $request)
    {
        QuoteService::save(null, $request->validated());

        return to_route('quotes.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreQuoteRequest $request, Quote $quote)
    {
        QuoteService::save($quote, $request->validated());

        return to_route('quotes.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Quote $quote)
    {
        QuoteService::delete($quote);

        return to_route('quotes.index');
    }

    /**
     * Send the specified quote.
     */
    public function send(Quote $quote)
    {
        Gate::authorize('update', $quote);

        QuoteService::send($quote);

        return back();
    }

    /**
     * Accept the specified quote.
     */
    public function accept(AcceptQuoteRequest $request, Quote $quote)
    {
        Gate::authorize('update', $quote);

        QuoteService::accept($quote);

        return back();
    }

    /**
     * Reject the specified quote.
     */
    public function reject(RejectQuoteRequest $request, Quote $quote)
    {
        Gate::authorize('update', $quote);

        QuoteService::reject($quote, $request->string('notes')->trim()->value());

        return back();
    }

    /**
     * Cancel the specified quote.
     */
    public function cancel(CancelQuoteRequest $request, Quote $quote)
    {
        Gate::authorize('update', $quote);

        QuoteService::cancel($quote, $request->string('notes')->trim()->value());

        return back();
    }
}
