<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use App\Services\Security\TenantBypass;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (TenantBypass::isBypassed()) {
            return;
        }

        // Se não estiver com bypass, somos obrigados a filtrar por user_id = auth()->id().
        // Se auth()->id() for nulo (ex: job rodando sem bypass), vai forçar a falha intencionalmente (segurança estrita).
        $userId = auth()->id();
        
        $builder->where($model->getTable() . '.user_id', $userId);
    }
}
