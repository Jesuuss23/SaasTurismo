<?php

namespace App\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Claim extends Model
{
    use HasUuids, SoftDeletes, BelongsToTenant;

    protected $table = 'crm_claims';

    protected $fillable = [
        'tenant_id',
        'code',
        'consumer_name',
        'doc_type',
        'doc_number',
        'email',
        'phone',
        'address',
        'claim_type',
        'contracted_good_service',
        'detail',
        'consumer_request',
        'status',
        'response_detail',
        'responded_at',
        'responded_by',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }
}