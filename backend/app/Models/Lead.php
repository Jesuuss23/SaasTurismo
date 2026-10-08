<?php

namespace App\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasUuids, SoftDeletes, BelongsToTenant;

    protected $table = 'crm_leads';

    protected $fillable = [
        'tenant_id',
        'code',
        'full_name',
        'company',
        'email',
        'phone',
        'channel_code',
        'source_code',
        'interest_code',
        'destination_id',
        'product_id',
        'travel_date',
        'pax',
        'status',
        'owner_id',
        'score',
        'notes',
        'converted_client_id',
        'first_contact_at',
        'converted_at',
        'lock_version',
    ];

    protected $casts = [
        'travel_date' => 'date',
        'pax' => 'integer',
        'score' => 'integer',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class, 'destination_id');
    }
}