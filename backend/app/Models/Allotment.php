<?php

namespace App\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Allotment extends Model
{
    use HasUuids, SoftDeletes, BelongsToTenant;

    protected $table = 'catalog_allotments';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'provider_id',
        'service_id',
        'type',
        'release_days',
        'date_from',
        'date_to',
        'status',
        'notes',
    ];

    protected $casts = [
        'release_days' => 'integer',
        'date_from' => 'date',
        'date_to' => 'date',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function dates(): HasMany
    {
        return $this->hasMany(AllotmentDate::class, 'allotment_id')->orderBy('date');
    }
}