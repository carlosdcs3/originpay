<?php

namespace App\Traits;

use App\Models\Scopes\TenantScope;

trait HasTenant
{
    /**
     * Boot the trait.
     */
    protected static function bootHasTenant(): void
    {
        static::addGlobalScope(new TenantScope);
    }
}
