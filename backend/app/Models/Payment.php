<?php

namespace App\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasUuids, SoftDeletes, BelongsToTenant;

    protected $table = 'fin_payments';

    protected $fillable = [
        'tenant_id',
        'receivable_id',
        'code',
        'method',
        'currency',
        'amount',
        'fx_rate',
        'transaction_reference',
        'payment_date',
        'received_by',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fx_rate' => 'decimal:6',
        'payment_date' => 'date',
    ];

    public function receivable(): BelongsTo
    {
        return $this->belongsTo(Receivable::class, 'receivable_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}