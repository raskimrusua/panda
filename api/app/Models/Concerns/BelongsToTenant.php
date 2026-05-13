<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Scope;

/**
 * BelongsToTenant — single-DB row-based tenant isolation.
 *
 * Apply to every business model. Two effects:
 *
 * 1. Global scope — every read query is filtered by `tenant_id = current
 *    tenant id`. Cross-tenant reads return nothing (404 from controllers,
 *    NEVER 403 — disclosure of resource existence is a tenancy leak).
 * 2. Creating event — `tenant_id` is auto-attached from the current tenant
 *    if the caller didn't set it. Calling code never has to remember.
 *
 * Mirrors Shira's `BaseFarmViewSet` + `FarmAwareModel` pattern (CLAUDE.md
 * Rule #1). The current tenant is set by Spatie's MakeTenantCurrentAction
 * in the NeedsTenant middleware via UserTenantFinder.
 *
 * Models using this trait MUST have a `tenant_id` ULID column.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (! $model->tenant_id && ($current = Tenant::current())) {
                $model->tenant_id = $current->id;
            }
        });
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

class TenantScope implements Scope
{
    /**
     * @param  Builder<Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        if ($current = Tenant::current()) {
            $builder->where($model->getTable().'.tenant_id', $current->id);
        }
    }
}
