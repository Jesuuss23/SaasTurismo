<?php

namespace App\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductItem extends Model
{
    use HasUuids, BelongsToTenant;

    protected $table = 'catalog_product_items';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'service_id',
        'component_product_id',
        'provider_id',
        'quantity',
        'unit',
        'sort_order',
        'is_required',
        'is_included',
        'unit_cost_override',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost_override' => 'decimal:2',
        'is_required' => 'boolean',
        'is_included' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}