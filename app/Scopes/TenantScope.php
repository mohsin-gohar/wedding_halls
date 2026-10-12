<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (auth()->check()) {
            $user = auth()->user();
            if ($user && $user->tenant_id && ! $user->isSuperAdmin()) {
                $builder->where($model->getTable().'.tenant_id', $user->tenant_id);
            }
        }
    }
}
