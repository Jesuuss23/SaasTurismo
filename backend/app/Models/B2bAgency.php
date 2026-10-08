<?php

namespace App\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class B2bAgency extends Model
{
    use HasUuids, SoftDeletes, BelongsToTenant;

    protected $table = 'channels_b2b_agencies';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'commercial_name',
        'tax_id',
        'country',
        'city',
        'contact_name',
        'email',
        'phone',
        'commission_pct',
        'currency',
        'credit_limit',
        'credit_used',
        'credit_days',
        'status',
        'account_manager_id',
        'notes',
    ];

    protected $casts = [
        'commission_pct' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'credit_used' => 'decimal:2',
        'credit_days' => 'integer',
    ];

    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_manager_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'agency_id');
    }

    public function getAvailableCreditAttribute(): float
    {
        return max(0, (float)$this->credit_limit - (float)$this->credit_used);
    }
}