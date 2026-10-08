<?php

namespace App\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Provider extends Model
{
    use HasUuids, SoftDeletes, BelongsToTenant;

    protected $table = 'catalog_providers';

    protected $fillable = [
        'tenant_id',
        'ref',
        'name',
        'type',
        'ruc',
        'city',
        'stars',
        'contact_name',
        'phone',
        'email',
        'contract_start',
        'contract_end',
        'currency',
        'payment_terms',
        'status',
        'rating',
        'rating_note',
    ];

    protected $casts = [
        'contract_start' => 'date',
        'contract_end' => 'date',
        'stars' => 'integer',
        'rating' => 'decimal:1',
    ];

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'provider_id');
    }
}