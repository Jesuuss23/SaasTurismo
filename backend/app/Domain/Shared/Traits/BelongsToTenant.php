<?php

namespace App\Domain\Shared\Traits;

use App\Domain\Shared\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model) {
            if (!$model->tenant_id && app()->bound('current_tenant_id')) {
                $model->tenant_id = app('current_tenant_id');
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}