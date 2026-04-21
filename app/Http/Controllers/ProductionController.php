<?php

namespace App\Http\Controllers;

use App\Http\Requests\Production\StoreProductionRequest;
use App\Models\Production;
use App\Services\ProductionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductionController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('productions/Index', [
            'productions' => ProductionService::search($request, true, [
                'operation' => fn($q) => $q->select('id', 'status', 'batch_number'),
            ]),
        ]);
    }

    public function show(Production $production)
    {
        return Inertia::render('productions/Show', ProductionService::getAdminShowData($production));
    }

    public function store(StoreProductionRequest $request)
    {
        ProductionService::save(null, $request->validated());

        return to_route('productions.index');
    }

    public function update(StoreProductionRequest $request, Production $production)
    {
        ProductionService::save($production, $request->validated());

        return to_route('productions.index');
    }

    public function destroy(Production $production)
    {
        ProductionService::delete($production);

        return to_route('productions.index');
    }
}

