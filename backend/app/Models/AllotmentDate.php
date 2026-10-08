<?php

namespace App\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AllotmentDate extends Model
{
    use HasUuids, BelongsToTenant;

    protected $table = 'catalog_allotment_dates';

    protected $fillable = [
        'tenant_id',
        'allotment_id',
        'date',
        'allocated_qty',
        'used_qty',
        'released_qty',
        'unit_cost',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'allocated_qty' => 'integer',
        'used_qty' => 'integer',
        'released_qty' => 'integer',
        'unit_cost' => 'decimal:2',
    ];

    public function allotment(): BelongsTo
    {
        return $this->belongsTo(Allotment::class, 'allotment_id');
    }

    public function getAvailableQtyAttribute(): int
    {
        return max(0, $this->allocated_qty - $this->used_qty - $this->released_qty);
    }
}