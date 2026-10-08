<?php

namespace App\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Approval extends Model
{
    use HasUuids, SoftDeletes, BelongsToTenant;

    protected $table = 'collab_approvals';

    protected $fillable = [
        'tenant_id',
        'code',
        'type',
        'target_entity',
        'target_id',
        'requester_id',
        'approver_id',
        'status',
        'reason',
        'resolution_notes',
        'snapshot',
        'resolved_at',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}