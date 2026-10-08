<?php

namespace App\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Opportunity extends Model
{
    use HasUuids, SoftDeletes, BelongsToTenant;

    protected $table = 'crm_opportunities';

    protected $fillable = [
        'tenant_id',
        'code',
        'client_id',
        'title',
        'stage',
        'amount',
        'currency',
        'probability',
        'expected_close_date',
        'closed_at',
        'owner_id',
        'destination_label',
        'pax',
        'lead_id',
        'product_id',
        'origin',
        'notes',
        'lost_reason_code',
        'lost_detail',
        'lost_at_stage',
        'lock_version',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'probability' => 'integer',
        'expected_close_date' => 'date',
        'closed_at' => 'date',
        'pax' => 'integer',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}