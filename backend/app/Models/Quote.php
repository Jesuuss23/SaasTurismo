<?php

namespace App\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use HasUuids, SoftDeletes, BelongsToTenant;

    protected $table = 'quote_quotes';

    protected $fillable = [
        'tenant_id',
        'code',
        'client_id',
        'opportunity_id',
        'owner_id',
        'title',
        'status',
        'currency',
        'fx_rate',
        'valid_until',
        'pax',
        'subtotal',
        'discount',
        'discount_pct',
        'tax',
        'total',
        'cost_total',
        'profit',
        'margin_pct',
        'notes',
        'terms_and_conditions',
        'lock_version',
    ];

    protected $casts = [
        'valid_until' => 'date',
        'fx_rate' => 'decimal:6',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'discount_pct' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'cost_total' => 'decimal:2',
        'profit' => 'decimal:2',
        'margin_pct' => 'decimal:2',
        'pax' => 'integer',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class, 'opportunity_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class, 'quote_id')->orderBy('sort_order');
    }
}