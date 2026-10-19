<?php

namespace App\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasUuids, SoftDeletes, BelongsToTenant;

    protected $table = 'catalog_products';

    protected $fillable = [
        'tenant_id',
        'ref',
        'code',
        'sku',
        'name',
        'internal_name',
        'product_type_id',
        'category_id',
        'subcategory_id',
        'primary_destination_id',
        'main_provider_id',
        'status',
        'short_description',
        'long_description',
        'duration_value',
        'duration_unit',
        'modality',
        'minimum_passengers',
        'maximum_passengers',
        'minimum_age',
        'maximum_age',
        'is_bookable',
        'is_quoteable',
        'is_b2b',
        'is_ecommerce',
        'markup_pct',
        'availability_model',
        'slug',
        'lock_version',
    ];

    protected $casts = [
        'duration_value' => 'decimal:1',
        'markup_pct' => 'decimal:2',
        'is_bookable' => 'boolean',
        'is_quoteable' => 'boolean',
        'is_b2b' => 'boolean',
        'is_ecommerce' => 'boolean',
    ];

    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class, 'product_type_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class, 'primary_destination_id');
    }

    public function mainProvider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'main_provider_id');
    }

    public function rates(): HasMany
    {
        return $this->hasMany(\App\Models\Rate::class, 'product_id');
    }

}