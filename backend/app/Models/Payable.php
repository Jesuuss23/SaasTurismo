<?php

namespace App\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payable extends Model
{
    use HasUuids, SoftDeletes, BelongsToTenant;

    protected $table = 'fin_payables';

    protected $fillable = [
        'tenant_id',
        'code',
        'provider_id',
        'ops_service_id',
        'concept',
        'due_date',
        'currency',
        'amount',
        'paid_amount',
        'balance',
        'status',
        'invoice_number',
    ];

    protected $casts = [
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function opsService(): BelongsTo
    {
        return $this->belongsTo(OpsService::class, 'ops_service_id');
    }

    public function disbursements(): HasMany
    {
        return $this->hasMany(Disbursement::class, 'payable_id');
    }
}