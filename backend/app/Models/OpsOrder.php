<?php

namespace App\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OpsOrder extends Model
{
    use HasUuids, SoftDeletes, BelongsToTenant;

    protected $table = 'ops_orders';

    protected $fillable = [
        'tenant_id',
        'code',
        'reservation_id',
        'status',
        'execution_date',
        'notes',
        'lock_version',
    ];

    protected $casts = [
        'execution_date' => 'date',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(OpsService::class, 'order_id');
    }
}