<?php

namespace App\Http\Controllers;

use App\Http\Requests\Prescription\RequestRevisionRequest;
use App\Http\Requests\Prescription\StorePrescriptionRequest;
use App\Models\Prescription;
use App\Services\PrescriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class PrescriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return Inertia::render('prescriptions/Index', [
            'prescriptions' => PrescriptionService::search($request, true, [
                'operation' => fn($q) => $q->select('id', 'status', 'batch_number'),
            ]),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Prescription $prescription)
    {
        return Inertia::render('prescriptions/Show', PrescriptionService::getAdminShowData($prescription));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePrescriptionRequest $request)
    {
        PrescriptionService::save(null, $request->validated());

        return to_route('prescriptions.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StorePrescriptionRequest $request, Prescription $prescription)
    {
        PrescriptionService::save($prescription, $request->validated());

        return to_route('prescriptions.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Prescription $prescription)
    {
        PrescriptionService::delete($prescription);

        return to_route('prescriptions.index');
    }

    /**
     * Mark the prescription as sent.
     */
    public function send(Request $request, Prescription $prescription)
    {
        Gate::authorize('update', $prescription);

        PrescriptionService::sendPrescription($prescription, $request->user());

        return back();
    }

    /**
     * Mark the prescription as confirmed.
     */
    public function confirm(Request $request, Prescription $prescription)
    {
        Gate::authorize('update', $prescription);

        PrescriptionService::confirmPrescription($prescription, $request->user());

        return back();
    }

    /**
     * Request a revision of a SENT / CONFIRMED / REVISED prescription.
     */
    public function requestRevision(RequestRevisionRequest $request, Prescription $prescription)
    {
        PrescriptionService::requestRevision(
            $prescription,
            (string) $request->validated('reason'),
            $request->user(),
        );

        return back();
    }
}
