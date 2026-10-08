<?php

namespace App\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasUuids, SoftDeletes, BelongsToTenant;

    protected $table = 'crm_clients';

    protected $fillable = [
        'tenant_id',
        'code',
        'kind',
        'name',
        'doc_type',
        'doc_number',
        'email',
        'phone',
        'country',
        'city',
        'segment_code',
        'owner_id',
        'nps_last',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}