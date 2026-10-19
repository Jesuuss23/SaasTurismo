<?php

namespace App\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GatewayTransaction extends Model
{
    use HasUuids, BelongsToTenant;

    protected $table = 'fin_gateway_transactions';

    protected $fillable = [
        'tenant_id',
        'reservation_id',
        'gateway',
        'transaction_id',
        'amount',
        'currency',
        'status',
        'card_brand',
        'card_last4',
        'raw_payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'raw_payload' => 'array',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }
}