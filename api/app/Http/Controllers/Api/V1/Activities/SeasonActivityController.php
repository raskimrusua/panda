<?php

namespace App\Http\Controllers\Api\V1\Activities;

use App\Http\Controllers\Controller;
use App\Http\Requests\Activities\LogDoneRequest;
use App\Http\Resources\SeasonActivityResource;
use App\Models\SeasonActivity;
use Carbon\Carbon;

/**
 * SeasonActivity surfaces beyond the basic CRUD lives in /seasons/{id}/timeline
 * (see SeasonNestedController). Here: only the log-done action — the most
 * frequent farmer interaction. Every other field stays engine-managed.
 */
class SeasonActivityController extends Controller
{
    public function logDone(LogDoneRequest $request, SeasonActivity $activity): SeasonActivityResource
    {
        $activity->update([
            'status' => SeasonActivity::STATUS_DONE,
            'completed_at' => $request->input('completed_at') !== null
                ? Carbon::parse((string) $request->input('completed_at'))
                : Carbon::now(),
            'completed_by' => $request->user()?->id,
            'completion_notes' => $request->input('completion_notes'),
        ]);

        return new SeasonActivityResource($activity);
    }
}
