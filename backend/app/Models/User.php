<?php

namespace App\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasRoles, HasUuids, Notifiable, SoftDeletes, BelongsToTenant;

    protected $table = 'iam_users';
    protected $guard_name = 'api';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'email',
        'password',
        'phone',
        'sede_id',
        'status',
        'mfa_enabled',
        'mfa_required',
        'client_id',
        'agency_id',
        'provider_id',
        'guide_id',
        'driver_id',
        'reservation_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'mfa_enabled' => 'boolean',
            'mfa_required' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function getPermissionsTeamId(): ?string
    {
        return $this->tenant_id;
    }

    /**
     * Asegurar que la relación pivote de Spatie incluya tenant_id
     */
    public function roles(): MorphToMany
    {
        $relation = $this->morphToMany(
            config('permission.models.role'),
            'model',
            config('permission.table_names.model_has_roles'),
            config('permission.column_names.model_morph_key'),
            app(PermissionRegistrar::class)->pivotRole
        );

        if (config('permission.teams')) {
            $relation->withPivot('tenant_id');
        }

        return $relation;
    }
}