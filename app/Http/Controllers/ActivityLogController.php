<?php

namespace App\Http\Controllers;

use App\Models\Operation;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    /**
     * Eventi mostrati nello slideover Attività lato workspace fornitore.
     * Whitelist: il fornitore non deve vedere eventi amministrativi che non lo riguardano.
     */
    private const SUPPLIER_VISIBLE_EVENTS = [
        'supplier_assigned',
        'supplier_document_uploaded',
        'supplier_document_removed',
        'production_confirmed',
        'production_canceled',
        'production_completed',
        'production_reopened',
        'operation_canceled',
    ];

    /**
     * Return activity log entries for a specific model.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'model_type' => ['required', 'string'],
            'model_id' => ['required', 'integer'],
        ]);

        $modelType = (string) $validated['model_type'];
        $modelId = (int) $validated['model_id'];

        if ($modelType === 'operation') {
            return $this->respondForOperation($request, $modelId);
        }

        if ($modelType === 'supplier_operation') {
            return $this->respondForSupplierOperation($request, $modelId);
        }

        return response()->json(['message' => 'Unsupported model type.'], 422);
    }

    private function respondForOperation(Request $request, int $modelId)
    {
        abort_unless($request->user()?->can('operations.activity.view') === true, 403);

        $activities = Activity::query()
            ->where('subject_type', Operation::class)
            ->where('subject_id', $modelId)
            ->with('causer')
            ->latest('created_at')
            ->limit(200)
            ->get();

        return $this->jsonResponse($activities);
    }

    private function respondForSupplierOperation(Request $request, int $modelId)
    {
        abort_unless(Gate::check('supplierWorkspaceAbility', 'workspace.supplier.operations.view'), 403);

        $supplier = UserService::currentUser()?->suppliers()->first();
        abort_unless($supplier !== null, 403);

        $belongs = DB::table('operation_supplier')
            ->where('operation_id', $modelId)
            ->where('supplier_id', $supplier->id)
            ->where('selected', true)
            ->exists();
        abort_unless($belongs, 403);

        $activities = Activity::query()
            ->where('subject_type', Operation::class)
            ->where('subject_id', $modelId)
            ->whereIn('event', self::SUPPLIER_VISIBLE_EVENTS)
            ->with('causer')
            ->latest('created_at')
            ->limit(200)
            ->get();

        return $this->jsonResponse($activities);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection<int, Activity> $activities
     */
    private function jsonResponse($activities)
    {
        return response()->json([
            'activities' => $activities->map(fn (Activity $activity): array => [
                'id' => $activity->id,
                'created_at' => optional($activity->created_at)->toISOString(),
                'event' => $activity->event,
                'description' => $activity->description,
                'causer' => $activity->causer ? [
                    'id' => $activity->causer->id,
                    'name' => $activity->causer->name ?? null,
                    'surname' => $activity->causer->surname ?? null,
                ] : null,
            ])->values()->all(),
        ]);
    }
}

