<?php

namespace App\Http\Controllers\Api\V1\InputListItems;

use App\Http\Controllers\Controller;
use App\Http\Requests\InputListItems\MarkProcuredRequest;
use App\Http\Resources\InputListItemResource;
use App\Models\InputListItem;
use Carbon\Carbon;

/**
 * InputListItem rows are engine-generated. Farmers can only mark
 * procurement (a value they fill in when they buy the input). The full
 * row lives at /seasons/{id}/input-list (SeasonNestedController).
 */
class InputListItemController extends Controller
{
    public function show(InputListItem $inputListItem): InputListItemResource
    {
        return new InputListItemResource($inputListItem);
    }

    public function markProcured(MarkProcuredRequest $request, InputListItem $inputListItem): InputListItemResource
    {
        $inputListItem->update([
            'procured_quantity' => $request->input('procured_quantity'),
            'procured_at' => $request->input('procured_at') !== null
                ? Carbon::parse((string) $request->input('procured_at'))
                : Carbon::now(),
        ]);

        return new InputListItemResource($inputListItem);
    }
}
