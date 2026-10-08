<?php

namespace App\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Destination extends Model
{
    use HasUuids, SoftDeletes, BelongsToTenant;

    protected $table = 'catalog_destinations';

    protected $fillable = [
        'tenant_id',
        'ref',
        'parent_id',
        'name',
        'type',
        'level',
        'lat',
        'lng',
        'description',
        'path',
    ];

    protected $casts = [
        'level' => 'integer',
        'lat' => 'decimal:6',
        'lng' => 'decimal:6',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Destination::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Destination::class, 'parent_id')->orderBy('name');
    }
}