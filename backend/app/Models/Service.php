<?php

namespace App\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasUuids, SoftDeletes, BelongsToTenant;

    protected $table = 'catalog_services';

    protected $fillable = [
        'tenant_id',
        'provider_id',
        'ref',
        'name',
        'type',
        'unit',
        'cost',
        'currency',
        'capacity',
        'valid_from',
        'valid_to',
        'status',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'capacity' => 'integer',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }
}