<?php

namespace App\Http\Controllers;

use App\Models\Operation;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
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

        $supported = [
            'operation' => [
                'model' => Operation::class,
                'permission' => 'operations.activity.view',
            ],
        ];

        if (! array_key_exists($modelType, $supported)) {
            return response()->json([
                'message' => 'Unsupported model type.',
            ], 422);
        }

        $permission = $supported[$modelType]['permission'];
        abort_unless($request->user()?->can($permission) === true, 403);

        /** @var class-string $subjectType */
        $subjectType = $supported[$modelType]['model'];

        $activities = Activity::query()
            ->where('subject_type', $subjectType)
            ->where('subject_id', $modelId)
            ->with('causer')
            ->latest('created_at')
            ->limit(200)
            ->get();

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

