<?php

namespace App\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasUuids, SoftDeletes, BelongsToTenant;

    protected $table = 'catalog_product_categories';

    protected $fillable = [
        'tenant_id',
        'ref',
        'name',
    ];
}