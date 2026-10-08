<?php

namespace App\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OpsService extends Model
{
    use HasUuids, SoftDeletes, BelongsToTenant;

    protected $table = 'ops_services';

    protected $fillable = [
        'tenant_id',
        'order_id',
        'service_id',
        'provider_id',
        'name',
        'date',
        'time',
        'meeting_point',
        'pax',
        'voucher_code',
        'status',
        'cost',
        'currency',
        'provider_confirmation_code',
        'provider_confirmed_at',
        'provider_notes',
        'rejection_reason',
        'rejected_at',
    ];

    protected $casts = [
        'date' => 'date',
        'cost' => 'decimal:2',
        'pax' => 'integer',
        'provider_confirmed_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(OpsOrder::class, 'order_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }
}