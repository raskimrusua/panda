<?php

namespace App\Http\Controllers\Api\V1\Billing;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionPlanResource;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Public plan catalogue — mirrors Shira's /api/v1/billing/plans/. No auth so
 * the marketing site can render live pricing from the API later.
 */
class SubscriptionPlanController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return SubscriptionPlanResource::collection(
            SubscriptionPlan::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
        );
    }
}
