<?php

namespace App\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rate extends Model
{
    use HasUuids, BelongsToTenant;

    protected $table = 'catalog_rates';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'ref',
        'rate_type',
        'segment',
        'channel',
        'currency',
        'price_basis',
        'cost',
        'price',
        'valid_from',
        'valid_to',
        'season_id',
        'status',
        'lock_version',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'price' => 'decimal:2',
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class, 'season_id');
    }
}