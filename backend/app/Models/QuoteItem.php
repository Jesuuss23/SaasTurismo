<?php

namespace App\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteItem extends Model
{
    use HasUuids, BelongsToTenant;

    protected $table = 'quote_quote_items';

    protected $fillable = [
        'tenant_id',
        'quote_id',
        'product_id',
        'rate_id',
        'concept',
        'service_date',
        'pax',
        'quantity',
        'unit_cost',
        'unit_price',
        'total_cost',
        'total_price',
        'snapshot',
        'sort_order',
    ];

    protected $casts = [
        'service_date' => 'date',
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'total_price' => 'decimal:2',
        'snapshot' => 'array',
        'pax' => 'integer',
        'sort_order' => 'integer',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'quote_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}