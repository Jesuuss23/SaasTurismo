<?php

namespace App\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PipelineStage extends Model
{
    use HasUuids, BelongsToTenant;

    protected $table = 'crm_pipeline_stages';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'default_probability',
        'sort_order',
        'is_closed',
    ];

    protected $casts = [
        'default_probability' => 'integer',
        'sort_order' => 'integer',
        'is_closed' => 'boolean',
    ];
}