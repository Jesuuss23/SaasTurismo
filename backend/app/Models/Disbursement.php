<?php

namespace App\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Disbursement extends Model
{
    use HasUuids, SoftDeletes, BelongsToTenant;

    protected $table = 'fin_disbursements';

    protected $fillable = [
        'tenant_id',
        'payable_id',
        'code',
        'method',
        'currency',
        'amount',
        'bank_reference',
        'payment_date',
        'paid_by',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function payable(): BelongsTo
    {
        return $this->belongsTo(Payable::class, 'payable_id');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}